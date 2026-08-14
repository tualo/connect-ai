<?php

namespace Tualo\ConnectAI;

use Tualo\ConnectAI\Exception\ConnectException;

class Config
{
    private array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function all(): array
    {
        return $this->data;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConnectException("Invalid JSON: " . json_last_error_msg());
        }
        return new self($decoded);
    }

    public static function fromFile(string $file, bool $strict = true): self
    {
        if (!file_exists($file)) {
            if ($strict) {
                throw new ConnectException("Config file not found: {$file}");
            }
            return new self([]);
        }

        $content = file_get_contents($file);
        return self::fromJson($content);
    }

    public function merge(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }
}
