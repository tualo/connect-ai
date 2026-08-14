<?php

namespace Tualo\Connect\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tualo\Connect\Exception\ConnectException;

class Anthropic implements AIProvider
{
    private string $apiKey = '';
    private string $model = 'claude-3-5-sonnet-20241022';
    private string $baseUrl = 'https://api.anthropic.com';
    private ?Client $httpClient = null;
    private array $options = [];

    public function __construct(array $config = [])
       {
           foreach ($config as $key => $value) {
             $this->$key = $value;
         }
      }

    public function getName(): string { return 'anthropic'; }

    public function getApiKey(): ?string { return $this->apiKey; }
    public function setApiKey(string $key): self { $this->apiKey = $key; return $this; }

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

    protected function getHttpClient(): Client
      {
        if (!$this->httpClient) {
              $this->httpClient = new Client([
             'base_uri' => $this->baseUrl . '/',
             'timeout' => 120,
              'headers' => [
               'x-api-key' => $this->apiKey,
                  'anthropic-version' => '2023-06-01',
                  'Content-Type' => 'application/json',
             ],
              ]);
           }
         return $this->httpClient;
         }

    protected function messagesToAnthropic(array $messages): array
      {
          $system = '';
         $anthropicMessages = [];

          foreach ($messages as $msg) {
              $role = $msg['role'] ?? '';
              $content = $msg['content'] ?? '';

              if ($role === 'system') {
                  $system = $content;
               } elseif ($role === 'user') {
                  $anthropicMessages[] = ['role' => 'user', 'content' => $content];
                  } elseif ($role === 'assistant') {
                     $anthropicMessages[] = ['role' => 'assistant', 'content' => $content];
              }
         }

         return ['system' => $system, 'messages' => $anthropicMessages];
      }

    public function chat(array $messages, array $options = []): AIResponse
       {
           $converted = $this->messagesToAnthropic($messages);

            $mergedOptions = array_merge([
                  'model' => $this->model,
                 'max_tokens' => 4000,
                  'temperature' => 0.7,
                  'stream' => false,
                  ], $this->options, $options);

             $body = [
                  'model' => $mergedOptions['model'],
              'max_tokens' => $mergedOptions['max_tokens'] ?? 4000,
                  'temperature' => $mergedOptions['temperature'] ?? 0.7,
                  'messages' => $converted['messages'],
                   ];

            if ($converted['system']) {
                  $body['system'] = $converted['system'];
             }

             try {
                  $response = $this->getHttpClient()->request('POST', '/v1/messages', [
                      'json' => $body,
                       ]);

                 $data = json_decode($response->getBody(), true);
                 $content = $data['content'][0]['text'] ?? '';
                  $model = $data['model'] ?? $mergedOptions['model'];

                   $inputTokens = $data['usage']['input_tokens'] ?? 0;
                  $outputTokens = $data['usage']['output_tokens'] ?? 0;

                 return new AIResponse(
                       $content,
                     $model,
                      $data['stop_reason'] ?? 'stop',
                    $inputTokens + $outputTokens,
                     $data
                 );
               } catch (GuzzleException $e) {
                   throw new ConnectException('Anthropic API Error: ' . $e->getMessage());
                }
             }

    public function stream(array $messages, array $options = []): \Generator
       {
           $converted = $this->messagesToAnthropic($messages);
             $mergedOptions = array_merge([
                  'model' => $this->model,
                 'max_tokens' => 4000,
                  'temperature' => 0.7,
                  'stream' => true,
             ], $this->options, $options);

              $body = [
                  'model' => $mergedOptions['model'],
                  'max_tokens' => $mergedOptions['max_tokens'] ?? 4000,
                  'temperature' => $mergedOptions['temperature'] ?? 0.7,
               'messages' => $converted['messages'],
                  'stream' => true,
              ];

            if ($converted['system']) {
                   $body['system'] = $converted['system'];
             }

           try {
                  $response = $this->getHttpClient()->request('POST', '/v1/messages', [
                      'json' => $body,
                     'stream' => true,
                 ]);

                $fullText = '';
                 foreach ($response->getBody() as $chunk) {
                      $lines = explode("\n", trim($chunk));
                      foreach ($lines as $line) {
                          if (strpos($line, 'data: ') === 0) {
                             $json = json_decode(substr($line, 6), true);
                            if ($json && $json['type'] === 'content_block_delta') {
                                  $text = $json['delta']['text'] ?? '';
                               $fullText .= $text;
                                   yield new AIResponse($text, $mergedOptions['model']);
                              }
                         }
                      }
                      yield new AIResponse($fullText, $mergedOptions['model']);
                  }
             } catch (GuzzleException $e) {
                 throw new ConnectException('Anthropic Stream Error: ' . $e->getMessage());
              }
          }
}
