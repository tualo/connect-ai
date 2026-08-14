<?php

namespace Tualo\Connect\DataSource;

use Tualo\Connect\DataSource\QueryResult;
use Tualo\Connect\Exception\ConnectException;

class REST implements DataSource
{
    private string $baseUrl = '';
    private string $method = 'GET';
    private array $headers = [];
    private array $options = [];
    private string $lastError = '';
    private bool $lastHasError = false;

    public function __construct(array $config = [])
    {
        if (isset($config['baseUrl'])) $this->baseUrl = $config['baseUrl'];
        if (isset($config['method'])) $this->method = $config['method'];
        if (isset($config['headers'])) $this->headers = $config['headers'];
        if (isset($config['options'])) $this->options = $config['options'];
    }

    public function getName(): string
    {
        return 'rest';
    }

    public function connect(array $config = []): self
    {
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->{$key} = $value ?? $this->{$key};
            }
        }
        return $this;
    }

    public function query(string $pathOrUrl, array $params = []): QueryResult
    {
        if (empty($this->baseUrl) && !filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {
            throw new ConnectException("REST base URL not set and path is not a valid URL");
        }

        $url = filter_var($pathOrUrl, FILTER_VALIDATE_URL) ? $pathOrUrl : $this->baseUrl . $pathOrUrl;

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'headers' => $this->headers,
            ]);

            $response = $client->request($this->method, $url, array_merge($this->options, ['query' => $params]));

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody, true) ?? [];
            $content = $response->getBody()->getContents();

            return new QueryResult(
                [],
                ['status', 'body'],
                1,
                '',
                true,
                0
            );
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->lastError = $e->getMessage();
            $this->lastHasError = true;
            return new QueryResult([], [], 0, $e->getMessage(), false);
        }
    }

    public function execute(string $pathOrUrl, array $params = []): int
    {
        $result = $this->query($pathOrUrl, $params);
        return $result->isSuccess() ? 1 : 0;
    }

    public function disconnect(): bool
    {
        return true;
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?: null;
    }

    public function fetchAll(string $pathOrUrl, array $params = []): array
    {
        $result = $this->query($pathOrUrl, $params);
        return $result->isSuccess() ? [] : [];
    }

    public function fetchOne(string $pathOrUrl, array $params = []): ?array
    {
        $result = $this->query($pathOrUrl, $params);
        return $result->isSuccess() ? null : null;
    }

    public function fetchValue(string $pathOrUrl, array $params = []): mixed
    {
        $result = $this->query($pathOrUrl, $params);
        return $result->isSuccess() ? null : null;
    }
}