# pattonwebz/psr3-logger

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

