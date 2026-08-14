<?php

namespace Tualo\ConnectAI\DataSource;

class QueryResult
{
    private array $rows = [];
    private array $columns = [];
    private int $rowCount;
    private string $error;
    private bool $success;
    private int $executionTime;

    public function __construct(
        array $rows = [],
        array $columns = [],
        int $rowCount = 0,
        string $error = '',
        bool $success = true,
        int $executionTime = 0
    ) {
        $this->rows = $rows;
        $this->columns = $columns;
        $this->rowCount = $rowCount;
        $this->error = $error;
        $this->success = $success;
        $this->executionTime = $executionTime;
    }

    public function getRows(): array { return $this->rows; }
    public function getColumns(): array { return $this->columns; }
    public function getRowCount(): int { return $this->rowCount; }
    public function getError(): string { return $this->error; }
    public function isSuccess(): bool { return $this->success; }
    public function getExecutionTime(): int { return $this->executionTime; }

    public function first(): ?array {
        return $this->rows[0] ?? null;
    }

    public function last(): ?array {
        return $this->rows[$this->rowCount - 1] ?? null;
    }

    public function keyBy(string $column): array {
        $result = [];
        foreach ($this->rows as $row) {
            if (isset($row[$column])) {
                $result[$row[$column]] = $row;
            }
        }
        return $result;
    }

    public function pluck(string $column): array {
        return array_column($this->rows, $column);
    }

    public function toArray(): array {
        return [
            'success' => $this->success,
            'rows' => $this->rows,
            'count' => $this->rowCount,
            'columns' => $this->columns,
            'error' => $this->error,
            'execution_time' => $this->executionTime,
        ];
    }

    public function toJson(): string {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public function toArrayWithNames(): array {
        $result = [];
        foreach ($this->rows as $row) {
            $mappedRow = [];
            foreach ($row as $key => $value) {
                $mappedRow[$this->columns[$key] ?? $key] = $value;
            }
            $result[] = $mappedRow;
        }
        return $result;
    }
}
