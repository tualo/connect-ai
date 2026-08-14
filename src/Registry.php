<?php

namespace Tualo\Connect;

use Tualo\Connect\AI\AIProvider;
use Tualo\Connect\DataSource\DataSource;
use Tualo\Connect\Exception\ConnectException;

class Registry
{
    private array $aiProviders = [];
    private array $dataSources = [];

    public function registerAI(string $name, AIProvider $provider): void
     {
        $this->aiProviders[$name] = $provider;
     }

    public function registerDataSource(string $name, DataSource $source): void
     {
        $this->dataSources[$name] = $source;
     }

    public function getAI(string $name): AIProvider
     {
        if (!isset($this->aiProviders[$name])) {
            throw new ConnectException("AI Provider '$name' not registered");
         }
        return $this->aiProviders[$name];
     }

    public function getDataSource(string $name): DataSource
     {
        if (!isset($this->dataSources[$name])) {
            throw new ConnectException("Data Source '$name' not registered");
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

    public function allAIProviders(): array
     {
        return $this->aiProviders;
     }

    public function allDataSources(): array
     {
        return $this->dataSources;
     }

    public function aiProviders(): array
     {
        return array_keys($this->aiProviders);
     }

    public function dataSources(): array
     {
        return array_keys($this->dataSources);
     }
}
