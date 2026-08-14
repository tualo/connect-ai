<?php

namespace Tualo\Connect\DataSource;

use Tualo\Connect\DataSource\QueryResult;
use Tualo\Connect\Exception\ConnectException;

class Spreadsheet implements DataSource
{
    private string $filePath = '';
    private string $format = 'csv'; // csv or xlsx
    private array $headers = [];
    private string $lastError = '';
    private bool $lastHasError = false;

    public function __construct(array $config = [])
    {
        if (isset($config['filePath'])) $this->filePath = $config['filePath'];
        if (isset($config['format'])) $this->format = $config['format'];
        if (isset($config['headers'])) $this->headers = $config['headers'];
    }

    public function getName(): string
    {
        return 'spreadsheet';
    }

    public function connect(array $config = []): self
    {
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $this->{$key} = $value ?? $this->{$key};
            }
        }

        if (!file_exists($this->filePath)) {
            throw new ConnectException("Spreadsheet file not found: {$this->filePath}");
        }

        return $this;
    }

    public function query(string $sheetName = '', array $params = []): QueryResult
    {
        if (empty($this->filePath)) {
            throw new ConnectException("Spreadsheet file path not set");
        }

        try {
            $rows = [];
            $columns = [];

            if ($this->format === 'csv') {
                if (($handle = fopen($this->filePath, 'r')) !== false) {
                    $i = 0;
                    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                        if ($i === 0) {
                            $columns = $data;
                        } else {
                            $rows[] = array_combine($columns, $data);
                        }
                        $i++;
                    }
                    fclose($handle);
                }
            } elseif ($this->format === 'xlsx') {
                // PHPSpreadsheet optional, fall back to basic CSV-like handling
                // In production, add require 'vendor/autoload.php' and use PHPSpreadsheet
                throw new ConnectException("XLSX format requires PHPSpreadsheet library");
            }

            return new QueryResult(
                $rows,
                $columns,
                count($rows),
                '',
                true,
                0
            );
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->lastHasError = true;
            return new QueryResult([], [], 0, $e->getMessage(), false);
        }
    }

    public function execute(string $sheetName = '', array $params = []): int
    {
        $result = $this->query($sheetName, $params);
        return $result->isSuccess() ? 1 : 0;
    }

    public function disconnect(): bool
    {
        return true;
    }

    public function isConnected(): bool
    {
        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?: null;
    }

    public function fetchAll(string $sheetName = '', array $params = []): array
    {
        $result = $this->query($sheetName, $params);
        return $result->isSuccess() ? $result->getRows() : [];
    }

    public function fetchOne(string $sheetName = '', array $params = []): ?array
    {
        $result = $this->query($sheetName, $params);
        return $result->isSuccess() ? $result->first() : null;
    }

    public function fetchValue(string $sheetName = '', array $params = []): mixed
    {
        $row = $this->fetchOne($sheetName, $params);
        return $row ? reset($row) : null;
    }
}