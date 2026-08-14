<?php

namespace Tualo\Connect;

use Tualo\ConnectAI\AI\AIProvider;
use Tualo\ConnectAI\AI\AIResponse;
use Tualo\ConnectAI\AI\AIPrompt;
use Tualo\ConnectAI\DataSource\QueryResult;
use Tualo\ConnectAI\Exception\ConnectException;

class Pipeline
{
    private Registry $registry;
    private ?string $dataSourceName = null;
    private array $dataSourceParams = [];
    private ?string $aiName = null;
    private ?string $aiPrompt = null;
    private ?QueryResult $dataSourceResult = null;
    private ?AIResponse $aiResponse = null;

    public function __construct(Registry $registry)
        {
             $this->registry = $registry;
            }

    public function dataSource(string $name, array $params = []): self
        {
            $this->dataSourceName = $name;
             $this->dataSourceParams = $params;
            return $this;
            }

    public function ai(string $name, string|array $prompt = ''): self
        {
            $this->aiName = $name;
            if (is_string($prompt)) {
                $this->aiPrompt = $prompt;
            } else {
                $this->aiPrompt = $prompt;
            }
            return $this;
            }

    public function result(): mixed
        {
            if ($this->dataSourceResult && $this->aiName) {
                return $this->executeAIQuery();
            }

            if ($this->dataSourceName) {
                $this->executeDataSourceQuery();
                 return $this->dataSourceResult;
            }

            if ($this->aiName) {
                return $this->executeAIQuery();
            }

            throw new ConnectException("No data source or AI provider set on pipeline");
            }

     protected function executeDataSourceQuery(): void
        {
            if (!$this->dataSourceName || !$this->registry->hasDataSource($this->dataSourceName)) {
                return;
            }

            $source = $this->registry->getDataSource($this->dataSourceName);
            $query = is_array($this->dataSourceParams) ? ($this->dataSourceParams[0] ?? null) : $this->dataSourceParams;
            $params = is_array($this->dataSourceParams) ? ($this->dataSourceParams[1] ?? []) : [];

            if ($query) {
                 $this->dataSourceResult = $source->query($query, $params);
            } else {
                 throw new ConnectException("No query string provided");
            }
            }

    protected function executeAIQuery(): AIResponse
        {
             $provider = $this->registry->getAI($this->aiName);

            if (!$this->dataSourceResult && !$this->aiPrompt) {
                throw new ConnectException("No input data for AI provider");
            }

             $prompt = $this->buildPrompt();
            return $this->aiResponse = $provider->chat($prompt);
            }

     protected function buildPrompt(): array
        {
            $prompt = new AIPrompt();

            if ($this->dataSourceResult) {
                $prompt->addUser(
                    "Hier sind die Daten aus der Datenbank:\n\n" .
                    $this->dataSourceResult->toJson() . "\n\nBitte analysiere diese Daten und erstelle eine Zusammenfassung."
                );
            } elseif (is_string($this->aiPrompt)) {
                $prompt->addUser($this->aiPrompt);
            }

             return $prompt->build();
            }
}
