<?php

namespace Tualo\ConnectAI\DataSource;

use Tualo\ConnectAI\DataSource\QueryResult;
use Tualo\ConnectAI\Exception\ConnectException;

class SOAP implements DataSource
{
    private string $location = '';
    private string $action = '';
    private array $headers = [];
    private string $lastError = '';
    private bool $lastHasError = false;

    public function __construct(array $config = [])
    {
        if (isset($config['location'])) $this->location = $config['location'];
        if (isset($config['action'])) $this->action = $config['action'];
        if (isset($config['headers'])) $this->headers = $config['headers'];
    }

    public function getName(): string
    {
        return 'soap';
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

    public function query(string $wsdlOrOperation, array $params = []): QueryResult
    {
        if (empty($this->location)) {
            throw new ConnectException("SOAP location not set");
        }

        try {
            $options = [
                'location' => $this->location,
                'uri' => '',
                'style' => SOAP_DOCUMENT,
                'use' => SOAP_LITERAL,
            ];

            if (!empty($this->action)) {
                $options['soapaction'] = $this->action;
            }

            $client = new \SoapClient($wsdlOrOperation ?? null, $options);

            $result = $client->__soapCall($this->action ?? 'execute', [$params]);

            return new QueryResult(
                [],
                ['result'],
                is_array($result) ? count($result) : 1,
                '',
                true,
                0
            );
        } catch (\SoapFault $e) {
            $this->lastError = $e->getMessage();
            $this->lastHasError = true;
            return new QueryResult([], [], 0, $e->getMessage(), false);
        }
    }

    public function execute(string $wsdlOrOperation, array $params = []): int
    {
        $result = $this->query($wsdlOrOperation, $params);
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

    public function fetchAll(string $wsdlOrOperation, array $params = []): array
    {
        $result = $this->query($wsdlOrOperation, $params);
        return $result->isSuccess() ? [] : [];
    }

    public function fetchOne(string $wsdlOrOperation, array $params = []): ?array
    {
        $result = $this->query($wsdlOrOperation, $params);
        return $result->isSuccess() ? null : null;
    }

    public function fetchValue(string $wsdlOrOperation, array $params = []): mixed
    {
        $result = $this->query($wsdlOrOperation, $params);
        return $result->isSuccess() ? null : null;
    }
}