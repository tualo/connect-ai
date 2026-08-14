<?php

namespace Tualo\Connect;

use Tualo\Connect\AI\AIProvider;
use Tualo\Connect\DataSource\DataSource;
use Tualo\Connect\Exception\ConnectException;

class Connect
{
    private static Registry $registry;

    public static function Registry(): Registry
    {
        if (!self::$registry) {
            self::$registry = new Registry();
        }
        return self::$registry;
    }

    public static function register(string $name, AIProvider|DataSource $instance): void
    {
        if ($instance instanceof AIProvider) {
            self::Registry()->registerAI($name, $instance);
        } elseif ($instance instanceof DataSource) {
            self::Registry()->registerDataSource($name, $instance);
        } else {
            throw new ConnectException("Instance must implement AIProvider or DataSource");
        }
    }

    public static function ai(string $name): AIProvider
    {
        return self::Registry()->getAI($name);
    }

    public static function dataSource(string $name): DataSource
    {
        return self::Registry()->getDataSource($name);
    }

    public static function pipeline(): Pipeline
    {
        return new Pipeline(self::Registry());
    }

    public static function reset(): void
    {
        self::$registry = new Registry();
    }
}
