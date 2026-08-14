<?php

namespace Tualo\ConnectAI;

use Tualo\ConnectAI\Exception\ConnectException;

class Factory
{
    public static function create(string $type, array $config = []): object
    {
        $type = strtolower($type);

        if (str_starts_with($type, 'ai/')) {
            $provider = str_replace('ai/', '', $type);
            return self::createAI($provider, $config);
        }

        if (str_starts_with($type, 'ds/')) {
            $source = str_replace('ds/', '', $type);
            return self::createDataSource($source, $config);
        }

        throw new ConnectException("Unknown connector type: {$type}");
    }

    private static function createAI(string $provider, array $config): object
    {
        $class = "Tualo\\Connect\\AI\\" . ucfirst($provider);

        if (!class_exists($class)) {
            throw new ConnectException("AI provider '{$provider}' not found");
        }

        return new $class($config);
    }

    private static function createDataSource(string $source, array $config): object
    {
        $class = "Tualo\\Connect\\DataSource\\" . ucfirst($source);

        if (!class_exists($class)) {
            throw new ConnectException("Data source '{$source}' not found");
        }

        return new $class($config);
    }
}
