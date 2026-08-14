<?php

namespace Tualo\ConnectAI;

class Result
{
    private bool $success;
    private mixed $data;
    private string $error;
    private array $metadata;

    public function __construct(
        bool $success = true,
        mixed $data = null,
        string $error = "",
        array $metadata = []
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
        $this->metadata = $metadata;
    }

    public static function ok(mixed $data = null, array $metadata = []): self
    {
        return new self(true, $data, "", $metadata);
    }

    public static function error(string $message, mixed $data = null, array $metadata = []): self
    {
        return new self(false, $data, $message, $metadata);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
