<?php

declare(strict_types=1);

namespace PattonWebz\Psr3Logger\Tests;

use PattonWebz\Psr3Logger\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

final class LoggerTest extends TestCase
{
    public function test_set_minimum_level_rejects_invalid_level(): void
    {
        $logger = new Logger();

        $this->expectException(InvalidArgumentException::class);
        $logger->setMinimumLevel('not-a-valid-level');
    }

    public function test_is_level_loggable_respects_threshold_and_enabled_state(): void
    {
        $logger = new Logger(LogLevel::WARNING, true);

        self::assertTrue($logger->isLevelLoggable(LogLevel::ERROR));
        self::assertFalse($logger->isLevelLoggable(LogLevel::INFO));

        $logger->setEnabled(false);
        self::assertFalse($logger->isLevelLoggable(LogLevel::ERROR));
    }

    public function test_log_writes_prefixed_interpolated_message(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $logger->setPrefix('[unit] ');

        $logger->info('User {user} data {data}', [
            'user' => 'alice',
            'data' => ['ok' => true],
        ]);

        self::assertCount(1, $logger->records);
        self::assertSame(LogLevel::INFO, $logger->records[0]['level']);
        self::assertSame('[unit] User alice data {"ok":true}', $logger->records[0]['message']);
    }

    public function test_log_rejects_invalid_level(): void
    {
        $logger = new Logger();

        $this->expectException(InvalidArgumentException::class);
        $logger->log('invalid-level', 'message');
    }
}

final class CapturingLogger extends Logger
{
    /** @var array<int, array{level: string, message: string}> */
    public array $records = [];

    protected function writeLog(string $level, string $message): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => $message,
        ];
    }
}

