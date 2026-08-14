<?php

namespace Tualo\ConnectAI\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tualo\ConnectAI\Exception\ConnectException;

class Ollama implements AIProvider
{
    private string $baseUrl = 'http://localhost:11434';
    private string $model = 'llama3';
    private ?Client $httpClient = null;
    private array $options = [];

    public function __construct(array $config = [])
        {
              foreach ($config as $key => $value) {
                 $this->{$key} = $value;
             }
         }

    public function getName(): string { return 'ollama'; }

    public function getApiKey(): ?string { return null; }
    public function setApiKey(string $key): self { return $this; }

    public function getModel(): string { return $this->model; }
    public function setModel(string $model): self { $this->model = $model; return $this; }

    public function setBaseUrl(string $url): self {
             $trimmed = rtrim($url, '/');
             $this->baseUrl = $trimmed;
        return $this;
        }

    public function getBaseUrl(): string { return $this->baseUrl; }

    public function setOptions(array $options): self { $this->options = $options; return $this; }
    public function getOptions(): array { return $this->options; }

    public function available(): array
        {
        try {
              $client = $this->getHttpClient();
             $response = $client->request('GET', '/api/tags');

                $models = json_decode($response->getBody(), true)['models'] ?? [];
            return array_map(function($m) { return $m['name']; }, $models);
            } catch (GuzzleException $e) {
             throw new ConnectException('Could not connect to Ollama at ' . $this->baseUrl);
             }
         }

     protected function getHttpClient(): Client
         {
             if (!$this->httpClient) {
                 $this->httpClient = new Client([
                     'base_uri' => $this->baseUrl . '/',
                     'timeout' => 300,
                  ]);
             }
             return $this->httpClient;
         }

    public function chat(array $messages, array $options = []): AIResponse
        {
             $mergedOptions = array_merge([
                 'model' => $this->model,
                 'stream' => false,
                 'format' => null,
                  'tools' => null,
              ], $this->options, $options);

            $body = [
                 'model' => $mergedOptions['model'],
                'messages' => $messages,
                  'stream' => false,
             ];

            if ($mergedOptions['format']) {
                $body['format'] = $mergedOptions['format'];
            }

            if ($mergedOptions['tools']) {
                  $body['tools'] = $mergedOptions['tools'];
             }

            try {
                 $response = $this->getHttpClient()->request('POST', '/api/chat', [
                    'json' => $body,
                 ]);

                $data = json_decode($response->getBody(), true);
                 $message = $data['message'] ?? [];
                 $content = $message['content'] ?? '';
                  $model = $data['model'] ?? $mergedOptions['model'];

                 return new AIResponse($content, $model, 'stop', 0, $data);
                 } catch (GuzzleException $e) {
                 throw new ConnectException('Ollama Chat Error: ' . $e->getMessage());
             }
         }

    public function stream(array $messages, array $options = []): \Generator
        {
             $mergedOptions = array_merge([
                'model' => $this->model,
                'stream' => true,
                'format' => null,
             ], $this->options, $options);

              $body = [
                  'model' => $mergedOptions['model'],
                 'messages' => $messages,
                  'stream' => true,
             ];

            if ($mergedOptions['format']) {
                 $body['format'] = $mergedOptions['format'];
             }

            try {
                  $response = $this->getHttpClient()->request('POST', '/api/chat', [
                     'json' => $body,
                      'stream' => true,
                 ]);

                  $content = '';
                 foreach ($response->getBody() as $chunk) {
                       $data = json_decode($chunk, true);
                        $delta = $data['message']['content'] ?? '';
                        $content .= $delta;
                        yield new AIResponse($delta, $mergedOptions['model']);
                    }
                  yield new AIResponse($content, $mergedOptions['model']);
                 } catch (GuzzleException $e) {
                 throw new ConnectException('Ollama Stream Error: ' . $e->getMessage());
             }
         }

    public function generate(string $prompt, array $options = []): AIResponse
        {
             $mergedOptions = array_merge([
                'model' => $this->model,
                 'stream' => false,
            ], $this->options, $options);

             $body = [
                 'model' => $mergedOptions['model'],
                 'prompt' => $prompt,
                 'stream' => false,
            ];

             try {
                  $response = $this->getHttpClient()->request('POST', '/api/generate', [
                     'json' => $body,
                  ]);

                  $data = json_decode($response->getBody(), true);
                  $text = $data['response'] ?? '';
                   $model = $data['model'] ?? $mergedOptions['model'];

                 return new AIResponse($text, $model, 'stop', 0, $data);
                 } catch (GuzzleException $e) {
                  throw new ConnectException('Ollama Generate Error: ' . $e->getMessage());
              }
         }
}
