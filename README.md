# pattonwebz/psr3-logger

A small PSR-3 compatible logger plus a convenience trait for class-level logging.

## Compatibility

This package currently declares:

- `php`: `>=7.4`
- `psr/log`: `^1.1 || ^2.0 || ^3.0`

How Composer resolves `psr/log` by PHP version:

| Runtime PHP | Typical resolved `psr/log` major |
| --- | --- |
| 7.4 | 1.x |
| 8.0+ | 1.x, 2.x, or 3.x |

Notes:

- `psr/log` 2.x and 3.x require PHP 8+.
- Keeping all three majors in constraints ensures the widest consumer compatibility.

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

