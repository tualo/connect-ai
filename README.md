# Tualo Connect AI

Eine generische PHP Library für die Verbindung zu verschiedenen AI-Providern und Datenquellen.

## Installation

```bash
composer require tualo/connect-ai
```

## Grundlagen

Das Projekt nutzt ein Registry-Pattern, um AI-Provider und Datenquellen zu verwalten.

### AI-Provider

Das Projekt unterstützt folgende AI-Provider (alle OpenAI-kompatibel):

- `openai` - OpenAI API (GPT-4, GPT-3.5, etc.)
- `ollama` - Ollama (lokale LLMs)
- `anthropic` - Anthropic Claude
- `gemini` - Google Gemini
- `azure` - Azure OpenAI
- `localai` - LocalAI (OpenAI-kompatibel)
- `vllm` - vLLM (OpenAI-kompatibel)

### Datenquellen

- `mysql` - MySQL/MariaDB
- `tualodb` - Current Session DB
- `postgresql` - PostgreSQL
- `rest` - REST APIs
- `graphql` - GraphQL APIs
- `soap` - SOAP Web Services
- `spreadsheet` - CSV/Excel Dateien

## Grundlegende Verwendung

```php
<?php

require 'vendor/autoload.php';

use Tualo\ConnectAI\Connect;
use Tualo\ConnectAI\Registry;

// Registry initialisieren
$registry = ConnectAI::Registry();

// AI-Provider registrieren
$openai = new \Tualo\ConnectAI\AI\OpenAI([
     'apiKey' => 'sk-...',
     'model' => 'gpt-4'
]);
ConnectAI::register('openai', $openai);

// Datenquelle registrieren
$mysql = new \Tualo\ConnectAI\DataSource\MySQL([
     'host' => 'localhost',
     'dbname' => 'meine_datenbank',
     'username' => 'user',
     'password' => 'pass'
]);
ConnectAI::register('mysql', $mysql);

// AI aufrufen
$response = ConnectAI::ai('openai')->chat([
     ['role' => 'user', 'content' => 'Hallo, wie geht es dir?']
]);

// Datenquelle verwenden
$result = ConnectAI::dataSource('mysql')->query(
     'SELECT * FROM customers WHERE active = ?',
     [1]
);
```

## Pipeline (Chaining)

Für komplexere Workflows kann die Pipeline-Klasse verwendet werden:

```php
$conn = new \Tualo\ConnectAI\Connect();

$result = $conn
     ->dataSource('mysql')
     ->query('SELECT * FROM orders WHERE status = ?', ['shipped'])
     ->ai('openai')
     ->chat('Fasse diese Bestellungen zusammen.')
     ->result();
```

## Factory

Die Factory-Klasse erstellt Provider und Quellen dynamisch:

```php
use Tualo\ConnectAI\Factory;

// AI-Provider erstellen
$ai = FactoryAI::create('ai/openai', ['apiKey' => 'sk-...', 'model' => 'gpt-4']);

// Datenquelle erstellen
$db = FactoryAI::create('ds/mysql', ['host' => 'localhost', 'dbname' => 'test']);
```

## Konfiguration

Konfiguration kann als Array, JSON oder Datei übergeben werden:

```php
// Aus Array
$config = \Tualo\ConnectAI\Config::fromArray(['key' => 'value']);

// Aus JSON
$config = \Tualo\ConnectAI\Config::fromJson('{"key": "value"}');

// Aus Datei
$config = \Tualo\ConnectAI\Config::fromFile('config.json');
```

## Lizenz

MIT License - siehe LICENSE-Datei für Details.