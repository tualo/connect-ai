<?php

namespace Tualo\Connect\AI;

class AIResponse
{
    private string $content;
    private string $model;
    private string $finishReason;
    private int $totalTokens;
    private array $rawResponse;
    private ?\DateTimeImmutable $createdAt;

    public function __construct(
        string $content = '',
        string $model = '',
        string $finishReason = 'stop',
        int $totalTokens = 0,
        array $rawResponse = [],
        ?\DateTimeImmutable $createdAt = null
    ) {
         $this->content = $content;
         $this->model = $model;
         $this->finishReason = $finishReason;
         $this->totalTokens = $totalTokens;
         $this->rawResponse = $rawResponse;
         $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getContent(): string { return $this->content; }
    public function getRawContent(): string { return $this->content; }

    public function getModel(): string { return $this->model; }
    public function getFinishReason(): string { return $this->finishReason; }
    public function getTotalTokens(): int { return $this->totalTokens; }
    public function getRawResponse(): array { return $this->rawResponse; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function toArray(): array
       {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'finish_reason' => $this->finishReason,
            'total_tokens' => $this->totalTokens,
            'raw' => $this->rawResponse,
        ];
    }

    public function toJson(): string { return json_encode($this->toArray(), JSON_PRETTY_PRINT); }
}
