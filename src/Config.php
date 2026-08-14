<?php

namespace Tualo\Connect;

use Tualo\Connect\Exception\ConnectException;

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

    public function set(string $key, mixed $value): void
      {
        $this->data[$key] = $value;
      }

    public function has(string $key): bool
      {
        return isset($this->data[$key]);
      }

    public function all(): array
      {
        return $this->data;
      }

    public function merge(array $data): self
      {
        $this->data = array_merge($this->data, $data);
        return $this;
      }

    public static function fromArray(array $data): self
      {
        return new self($data);
      }

    public static function fromJson(string $json): self
      {
        $data = json_decode($json, true);
        if ($data === false) {
            throw new ConnectException("Invalid JSON config");
          }
        return new self($data);
      }

    public static function fromFile(string $file): self
      {
        if (!file_exists($file)) {
            throw new ConnectException("Config file not found: $file");
          }
        $json = file_get_contents($file);
        return self::fromJson($json);
      }
}
