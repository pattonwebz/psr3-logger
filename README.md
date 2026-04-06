# pattonwebz/psr3-logger

[![Tests](https://github.com/pattonwebz/psr3-logger/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/pattonwebz/psr3-logger/actions/workflows/tests.yml)
[![Coverage Status](https://coveralls.io/repos/github/pattonwebz/psr3-logger/badge.svg?branch=master)](https://coveralls.io/github/pattonwebz/psr3-logger?branch=master)
![PHP >=7.4](https://img.shields.io/badge/php-%3E%3D7.4-777BB4.svg?logo=php&logoColor=white)
![psr/log ^1.1](https://img.shields.io/badge/psr%2Flog-%5E1.1-blue)
![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)

A small PSR-3 compatible logger plus a convenience trait for class-level logging.

## Compatibility

This package requires:

- `php`: `>=7.4`
- `psr/log`: `^1.1`

`psr/log` 1.x supports PHP 5.3+, making this package compatible with any PHP 7.4+ environment without
version conflicts.

## Installation

```bash
composer require pattonwebz/psr3-logger
```

## Basic Usage (`Logger`)

```php
<?php

use PattonWebz\Psr3Logger\Logger;
use Psr\Log\LogLevel;

$logger = new Logger(LogLevel::INFO, true);
$logger->setPrefix('[my-app] ');

$logger->info('User {user} logged in', ['user' => 'alice']);
$logger->debug('This will be skipped because minimum level is INFO');
```

## Trait Usage (`LoggerTrait`)

```php
<?php

use PattonWebz\Psr3Logger\Logger;
use PattonWebz\Psr3Logger\LoggerTrait;

class Worker {
    use LoggerTrait;

    public static function run(): void {
        self::log_info('Worker started');
    }
}

Worker::setLogger((new Logger())->setPrefix('[worker] '));
Worker::run();
```

If no logger is injected, `LoggerTrait` falls back to `Psr\Log\NullLogger` (no-op).

## License

GPL-2.0-or-later

