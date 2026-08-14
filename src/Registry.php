<?php

namespace Tualo\ConnectAI;

use Tualo\ConnectAI\Exception\ConnectException;

class Registry
{
    private array $aiProviders = [];
    private array $dataSources = [];

    public function registerAI(string $name, object $provider): void
    {
        if (!method_exists($provider, 'getName')) {
            throw new ConnectException("Object must implement AIProvider interface");
        }
        $this->aiProviders[$name] = $provider;
    }

    public function registerDataSource(string $name, object $source): void
    {
        if (!method_exists($source, 'getName')) {
            throw new ConnectException("Object must implement DataSource interface");
        }
        $this->dataSources[$name] = $source;
    }

    public function getAI(string $name): object
    {
        if (!isset($this->aiProviders[$name])) {
            throw new ConnectException("AI Provider '{$name}' not registered");
        }
        return $this->aiProviders[$name];
    }

    public function getDataSource(string $name): object
    {
        if (!isset($this->dataSources[$name])) {
            throw new ConnectException("Data Source '{$name}' not registered");
        }
        return $this->dataSources[$name];
    }

    public function hasAI(string $name): bool
    {
        return isset($this->aiProviders[$name]);
    }

    public function hasDataSource(string $name): bool
    {
        return isset($this->dataSources[$name]);
    }

    public function listAIProviders(): array
    {
        return array_keys($this->aiProviders);
    }

    public function listDataSources(): array
    {
        return array_keys($this->dataSources);
    }

    public function allAIProviders(): array
    {
        return $this->aiProviders;
    }

    public function allDataSources(): array
    {
        return $this->dataSources;
    }

    public function reset(): void
    {
        $this->aiProviders = [];
        $this->dataSources = [];
    }
}
