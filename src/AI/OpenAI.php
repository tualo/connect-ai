<?php

namespace Tualo\Connect\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tualo\Connect\Exception\ConnectException;

class OpenAI implements AIProvider
{
    private string $apiKey = '';
    private string $model = 'gpt-3.5-turbo';
    private string $baseUrl = 'https://api.openai.com/v1';
    private ?Client $httpClient = null;
    private array $options = [];

    public function __construct(array $config = [])
    {
        if (isset($config['apiKey'])) $this->apiKey = $config['apiKey'];
        if (isset($config['model'])) $this->model = $config['model'];
        if (isset($config['baseUrl'])) $this->baseUrl = $config['baseUrl'];
    }

    public function getName(): string { return 'openai'; }

    public function getApiKey(): ?string { return $this->apiKey; }
    public function setApiKey(string $key): self { $this->apiKey = $key; return $this; }

    public function getModel(): string { return $this->model; }
    public function setModel(string $model): self { $this->model = $model; return $this; }

    public function setBaseUrl(string $url): self { $this->baseUrl = $url; return $this; }
    public function getBaseUrl(): string { return $this->baseUrl; }

    public function setOptions(array $options): self { $this->options = $options; return $this; }
    public function getOptions(): array { return $this->options; }

    protected function getHttpClient(): Client
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 120,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        return $this->httpClient;
    }

    public function chat(array $messages, array $options = []): AIResponse
    {
        $mergedOptions = array_merge([
            'model' => $this->model,
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ], $this->options, $options);

        $body = [
            'model' => $mergedOptions['model'],
            'messages' => $messages,
            'temperature' => $mergedOptions['temperature'] ?? 0.7,
            'max_tokens' => $mergedOptions['max_tokens'] ?? 4000,
        ];

        try {
            $response = $this->getHttpClient()->request('POST', '/chat/completions', [
                'json' => $body,
            ]);

            $responseBody = json_decode($response->getBody(), true);
            $choices = $responseBody['choices'] ?? [];

            if (empty($choices)) {
                throw new ConnectException('No choices returned from OpenAI');
            }

            $message = $choices[0]['message'] ?? [];
            $content = $message['content'] ?? '';
            $finishReason = $choices[0]['finish_reason'] ?? 'stop';

            $usage = $responseBody['usage'] ?? [];
            $totalTokens = $usage['total_tokens'] ?? 0;

            return new AIResponse(
                $content,
                $responseBody['model'] ?? $mergedOptions['model'],
                $finishReason,
                $totalTokens,
                $responseBody
            );
        } catch (GuzzleException $e) {
            throw new ConnectException('OpenAI API Error: ' . $e->getMessage());
        }
    }

    public function stream(array $messages, array $options = []): \Generator
    {
        $mergedOptions = array_merge([
            'model' => $this->model,
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'stream' => true,
        ], $this->options, $options);

        $body = [
            'model' => $mergedOptions['model'],
            'messages' => $messages,
            'temperature' => $mergedOptions['temperature'] ?? 0.7,
            'max_tokens' => $mergedOptions['max_tokens'] ?? 4000,
            'stream' => true,
        ];

        try {
            $response = $this->getHttpClient()->request('POST', '/chat/completions', [
                'json' => $body,
                'stream' => true,
            ]);

            $content = '';
            foreach ($response->getBody() as $chunk) {
                $lines = explode("\n", trim($chunk));
                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $data = substr($line, 6);
                        if ($data === '[DONE]') {
                            yield new AIResponse($content, $mergedOptions['model']);
                            return;
                        }
                        $json = json_decode($data, true);
                        $delta = $json['choices'][0]['delta']['content'] ?? '';
                        $content .= $delta;
                        yield new AIResponse($delta, $mergedOptions['model']);
                    }
                }
            }
            yield new AIResponse($content, $mergedOptions['model']);
        } catch (GuzzleException $e) {
            throw new ConnectException('OpenAI Stream Error: ' . $e->getMessage());
        }
    }
}