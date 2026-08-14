<?php

namespace Tualo\Connect\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tualo\Connect\Exception\ConnectException;

class Azure implements AIProvider
{
    private string $apiKey = '';
    private string $endpoint = '';
    private string $model = 'gpt-4';
    private string $apiVersion = '2024-02-15-preview';
    private ?Client $httpClient = null;
    private array $options = [];

    public function __construct(array $config = [])
    {
        if (isset($config['apiKey'])) $this->apiKey = $config['apiKey'];
        if (isset($config['endpoint'])) $this->endpoint = $config['endpoint'];
        if (isset($config['model'])) $this->model = $config['model'];
        if (isset($config['apiVersion'])) $this->apiVersion = $config['apiVersion'];
    }

    public function getName(): string { return 'azure'; }

    public function getApiKey(): ?string { return $this->apiKey; }
    public function setApiKey(string $key): self { $this->apiKey = $key; return $this; }

    public function getModel(): string { return $this->model; }
    public function setModel(string $model): self { $this->model = $model; return $this; }

    public function setBaseUrl(string $url): self { return $this; }
    public function getBaseUrl(): string { return $this->endpoint; }

    public function setOptions(array $options): self { $this->options = $options; return $this; }
    public function getOptions(): array { return $this->options; }

    protected function getHttpClient(): Client
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client([
                'base_uri' => $this->endpoint,
                'timeout' => 120,
                'headers' => [
                    'api-key' => $this->apiKey,
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
        ], $this->options, $options);

        $body = [
            'model' => $mergedOptions['model'],
            'messages' => $messages,
            'temperature' => $mergedOptions['temperature'] ?? 0.7,
            'max_tokens' => $mergedOptions['max_tokens'] ?? 4000,
        ];

        try {
            $response = $this->getHttpClient()->request('POST', '/openai/deployments/' . $this->model . '/chat/completions?api-version=' . $this->apiVersion, [
                'json' => $body,
            ]);

            $responseBody = json_decode($response->getBody(), true);
            $choices = $responseBody['choices'] ?? [];

            if (empty($choices)) {
                throw new ConnectException('No choices returned from Azure OpenAI');
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
            throw new ConnectException('Azure OpenAI API Error: ' . $e->getMessage());
        }
    }

    public function stream(array $messages, array $options = []): \Generator
    {
        // Similar to OpenAI stream but with Azure endpoint
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
            $response = $this->getHttpClient()->request('POST', '/openai/deployments/' . $this->model . '/chat/completions?api-version=' . $this->apiVersion, [
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
            throw new ConnectException('Azure OpenAI Stream Error: ' . $e->getMessage());
        }
    }
}