<?php

namespace Tualo\ConnectAI\DataSource;

interface DataSource
{
    public function getName(): string;

    public function connect(array $config = []): self;

    public function query(string $query, array $params = []): QueryResult;

    public function execute(string $query, array $params = []): int;

    public function disconnect(): bool;

    public function isConnected(): bool;

    public function getLastError(): ?string;

    public function fetchAll(string $query, array $params = []): array;

    public function fetchOne(string $query, array $params = []): ?array;

    public function fetchValue(string $query, array $params = []): mixed;

    public function __construct(array $config = []);
}
