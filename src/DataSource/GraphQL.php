<?php

namespace Tualo\Connect\DataSource;

use Tualo\Connect\DataSource\QueryResult;
use Tualo\Connect\Exception\ConnectException;

class GraphQL implements DataSource
{
    private string $endpoint = '';
    private string $method = 'POST';
    private array $headers = [];
    private array $options = [];
    private string $lastError = '';
    private bool $lastHasError = false;

    public function __construct(array $config = [])
    {
        if (isset($config['endpoint'])) $this->endpoint = $config['endpoint'];
        if (isset($config['headers'])) $this->headers = $config['headers'];
        if (isset($config['options'])) $this->options = $config['options'];
    }

    public function getName(): string
    {
        return 'graphql';
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

    public function query(string $query, array $params = []): QueryResult
    {
        if (empty($this->endpoint)) {
            throw new ConnectException("GraphQL endpoint not set");
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'headers' => array_merge($this->headers, [
                    'Content-Type' => 'application/json',
                ]),
            ]);

            $body = json_encode([
                'query' => $query,
                'variables' => $params,
            ]);

            $response = $client->request($this->method, $this->endpoint, [
                'body' => $body,
            ]);

            $data = json_decode($response->getBody, true) ?? [];

            return new QueryResult(
                [],
                ['data', 'errors'],
                isset($data['data']) ? count($data['data']) : 0,
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

    public function execute(string $query, array $params = []): int
    {
        $result = $this->query($query, $params);
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

    public function fetchAll(string $query, array $params = []): array
    {
        $result = $this->query($query, $params);
        return $result->isSuccess() ? [] : [];
    }

    public function fetchOne(string $query, array $params = []): ?array
    {
        $result = $this->query($query, $params);
        return $result->isSuccess() ? null : null;
    }

    public function fetchValue(string $query, array $params = []): mixed
    {
        $result = $this->query($query, $params);
        return $result->isSuccess() ? null : null;
    }
}