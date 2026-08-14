<?php

namespace Tualo\ConnectAI\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tualo\ConnectAI\Exception\ConnectException;

class Gemini implements AIProvider
{
    private string $apiKey = '';
    private string $model = 'gemini-2.0-flash';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private ?Client $httpClient = null;
    private array $options = [];

    public function __construct(array $config = [])
       {
          foreach ($config as $key => $value) {
           $this->$key = $value;
           }
        }

    public function getName(): string { return 'gemini'; }

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
               'base_uri' => $this->baseUrl,
               'timeout' => 120,
               'headers' => [
                   'Content-Type' => 'application/json',
               ],
            ]);
            }
        return $this->httpClient;
    }

    protected function messagesToGemini(array $messages): array
     {
        $systemPrompt = '';
        $contents = [];
        $isUser = true;

       foreach ($messages as $msg) {
             $role = $msg['role'] ?? '';
           $content = $msg['content'] ?? '';

           if ($role === 'system') {
               $systemPrompt = $content;
              } elseif ($role === 'user') {
               $role = 'user';
              } elseif ($role === 'assistant') {
               $role = 'model';
              } else {
               continue;
            }

            $contents[] = [
               'role' => $role,
              'parts' => [['text' => $content]],
            ];
         }

        return [
            'system_instruction' => $systemPrompt,
           'contents' => $contents,
           ];
         }

    public function chat(array $messages, array $options = []): AIResponse
      {
           $converted = $this->messagesToGemini($messages);

          $mergedOptions = array_merge([
              'temperature' => 0.7,
              'maxOutputTokens' => 4000,
             ], $this->options, $options);

             $body = [
                'contents' => $converted['contents'],
               'generationConfig' => [
                  'temperature' => $mergedOptions['temperature'] ?? 0.7,
                  'maxOutputTokens' => $mergedOptions['maxOutputTokens'] ?? 4000,
                ],
              ];

            if ($converted['system_instruction']) {
               $body['system_instruction'] = [
                    'parts' => [['text' => $converted['system_instruction']]]
                  ];
                }

        try {
             $response = $this->getHttpClient()->request('POST', "/models/{$this->model}:generateContent?key={$this->apiKey}", [
               'json' => $body,
             ]);

           $data = json_decode($response->getBody(), true);
            $candidates = $data['candidates'] ?? [];

            if (empty($candidates)) {
                 throw new ConnectException('No candidates returned from Gemini');
               }

           $candidate = $candidates[0];
            $content = $candidate['content']['parts'][0]['text'] ?? '';
            $finishReason = $candidate['finishReason'] ?? 'STOP';

            $usage = $data['usageMetadata'] ?? [];
            $totalTokens = ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0);

            return new AIResponse(
                $content,
                $this->model,
                $finishReason,
                $totalTokens,
                $data
            );
        } catch (GuzzleException $e) {
             throw new ConnectException('Gemini API Error: ' . $e->getMessage());
            }
       }

    public function stream(array $messages, array $options = []): \Generator
     {
         $converted = $this->messagesToGemini($messages);

        $mergedOptions = array_merge([
             'temperature' => 0.7,
            'maxOutputTokens' => 4000,
            ], $this->options, $options);

             $body = [
                'contents' => $converted['contents'],
                'generationConfig' => [
                    'temperature' => $mergedOptions['temperature'] ?? 0.7,
                   'maxOutputTokens' => $mergedOptions['maxOutputTokens'] ?? 4000,
                   ],
             ];

        try {
             $response = $this->getHttpClient()->request('POST', "/models/{$this->model}:streamGenerateContent?key={$this->apiKey}", [
                'json' => $body,
                'stream' => true,
            ]);

            $fullText = '';
            while (!$response->getBody()->eof()) {
                $chunk = $response->getBody()->read(8192);
                $lines = explode("\n", trim($chunk));

                foreach ($lines as $line) {
                    if (strpos($line, '{') === 0) {
                        $json = json_decode($line, true);
                        $parts = $json['candidates'][0]['content']['parts'] ?? [];
                        foreach ($parts as $part) {
                            $text = $part['text'] ?? '';
                            $fullText .= $text;
                             yield new AIResponse($text, $this->model);
                             }
                          }
                     }
                }
            } catch (GuzzleException $e) {
                throw new ConnectException('Gemini Stream Error: ' . $e->getMessage());
            }
        }
    }
