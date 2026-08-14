<?php

namespace Tualo\Connect\DataSource;

use Tualo\Connect\DataSource\QueryResult;
use Tualo\Connect\Exception\ConnectException;
use Tualo\Office\Basic\TualoApplication as App;

class MySQL implements DataSource
{
    private ?\Tualo\Office\Basic\MYSQL\Database $db = null;
    private string $lastError = '';
    private bool $connected = false;

    public function __construct(array $config = [])
    {
        $session = App::get('session');
        $this->db = $session->getDB();
    }

    public function getName(): string
    {
        return 'tualodb';
    }

    public function connect(array $config = []): self
    {
        return $this;
    }

    public function query(string $query, array $params = []): QueryResult
    {
        if (is_null($this->db)) {
            throw new ConnectException("DB not connected. Call connect() first.");
        }

        try {
            $startTime = microtime(true);
            $rows = $this->db->direct($query, $params);
            $rowCount = count($rows);
            $executionTime = microtime(true) - $startTime;

            return new QueryResult(
                $rows,
                array_keys($rows[0] ?? []),
                $rowCount,
                '',
                true,
                (int)($executionTime * 1000)
            );
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return new QueryResult([], [], 0, $e->getMessage(), false);
        }
    }

    public function execute(string $query, array $params = []): int
    {
        if (is_null($this->db)) {
            throw new ConnectException("DB not connected. Call connect() first.");
        }

        try {
            $startTime = microtime(true);
            $rowCount = $this->db->direct($query, $params);
            return $rowCount;
        } catch (\Exception $e) {
            throw new ConnectException("DB execute failed: {$e->getMessage()}");
        }
    }

    public function disconnect(): bool
    {
        if ($this->db) {
            $this->db = null;
        }
        $this->connected = false;
        return true;
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->db !== null;
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?: null;
    }

    public function fetchAll(string $query, array $params = []): array
    {
        return $this->query($query, $params)->getRows();
    }

    public function fetchOne(string $query, array $params = []): ?array
    {
        $rows = $this->query($query, $params)->getRows();
        return $rows ? $rows[0] : null;
    }

    public function fetchValue(string $query, array $params = []): mixed
    {
        $row = $this->fetchOne($query, $params);
        return $row ? reset($row) : null;
    }
}
