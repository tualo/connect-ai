<?php

namespace Tualo\Connect\DataSource;

use Tualo\Connect\DataSource\QueryResult;
use Tualo\Connect\Exception\ConnectException;

class PostgreSQL implements DataSource
{
    private string $host = 'localhost';
    private int $port = 5432;
    private string $dbname = '';
    private string $username = '';
    private string $password = '';
    private ?\PDO $pdo = null;
    private string $lastError = '';
    private bool $connected = false;

    public function __construct(array $config = [])
    {
        if (isset($config['host'])) $this->host = $config['host'];
        if (isset($config['port'])) $this->port = $config['port'];
        if (isset($config['dbname'])) $this->dbname = $config['dbname'];
        if (isset($config['username'])) $this->username = $config['username'];
        if (isset($config['password'])) $this->password = $config['password'];
    }

    public function getName(): string
    {
        return 'postgresql';
    }

    public function connect(array $config = []): self
    {
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->{$key} = $value ?? $this->{$key};
            }
        }

        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            $this->pdo = new \PDO($dsn, $this->username, $this->password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->connected = true;
            $this->lastError = '';
        } catch (\PDOException $e) {
            $this->connected = false;
            $this->lastError = $e->getMessage();
            throw new ConnectException("PostgreSQL connection failed: {$e->getMessage()}");
        }

        return $this;
    }

    public function query(string $query, array $params = []): QueryResult
    {
        if (!$this->connected) {
            throw new ConnectException("PostgreSQL not connected. Call connect() first.");
        }

        try {
            $startTime = microtime(true);
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $executionTime = microtime(true) - $startTime;

            $columns = $stmt->getColumnMeta(0) ?? [];
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $rowCount = $stmt->rowCount();

            return new QueryResult(
                $rows,
                array_keys($rows[0] ?? []),
                $rowCount,
                '',
                true,
                (int)($executionTime * 1000)
            );
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return new QueryResult([], [], 0, $e->getMessage(), false);
        }
    }

    public function execute(string $query, array $params = []): int
    {
        if (!$this->connected) {
            throw new ConnectException("PostgreSQL not connected. Call connect() first.");
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new ConnectException("PostgreSQL execute failed: {$e->getMessage()}");
        }
    }

    public function disconnect(): bool
    {
        if ($this->pdo) {
            $this->pdo = null;
        }
        $this->connected = false;
        return true;
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->pdo !== null;
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