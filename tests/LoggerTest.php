<?php

declare(strict_types=1);

namespace PattonWebz\Psr3Logger\Tests;

use PattonWebz\Psr3Logger\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

final class LoggerTest extends TestCase
{
    // -------------------------------------------------------------------
    // setMinimumLevel
    // -------------------------------------------------------------------

    public function test_set_minimum_level_rejects_invalid_level(): void
    {
        $logger = new Logger();

        $this->expectException(InvalidArgumentException::class);
        $logger->setMinimumLevel('not-a-valid-level');
    }

    // -------------------------------------------------------------------
    // isLevelLoggable
    // -------------------------------------------------------------------

    public function test_is_level_loggable_respects_threshold_and_enabled_state(): void
    {
        $logger = new Logger(LogLevel::WARNING, true);

        self::assertTrue($logger->isLevelLoggable(LogLevel::ERROR));
        self::assertFalse($logger->isLevelLoggable(LogLevel::INFO));

        $logger->setEnabled(false);
        self::assertFalse($logger->isLevelLoggable(LogLevel::ERROR));
    }

    public function test_is_level_loggable_returns_false_for_unknown_level(): void
    {
        $logger = new Logger(LogLevel::DEBUG, true);

        self::assertFalse($logger->isLevelLoggable('not-a-real-level'));
    }

    // -------------------------------------------------------------------
    // log() — level gate
    // -------------------------------------------------------------------

    public function test_log_skips_messages_below_minimum_level(): void
    {
        $logger = new CapturingLogger(LogLevel::WARNING, true);
        $logger->debug('this should be filtered');

        self::assertCount(0, $logger->records);
    }

    public function test_log_rejects_invalid_level(): void
    {
        $logger = new Logger();

        $this->expectException(InvalidArgumentException::class);
        $logger->log('invalid-level', 'message');
    }

    // -------------------------------------------------------------------
    // Convenience methods (emergency … debug)
    // -------------------------------------------------------------------

    public function test_all_convenience_methods_dispatch_to_log(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);

        $logger->emergency('em');
        $logger->alert('al');
        $logger->critical('cr');
        $logger->error('er');
        $logger->warning('wa');
        $logger->notice('no');
        $logger->debug('de');

        $levels = array_column($logger->records, 'level');

        self::assertContains(LogLevel::EMERGENCY, $levels);
        self::assertContains(LogLevel::ALERT,     $levels);
        self::assertContains(LogLevel::CRITICAL,  $levels);
        self::assertContains(LogLevel::ERROR,     $levels);
        self::assertContains(LogLevel::WARNING,   $levels);
        self::assertContains(LogLevel::NOTICE,    $levels);
        self::assertContains(LogLevel::DEBUG,     $levels);
    }

    // -------------------------------------------------------------------
    // interpolate — all branches
    // -------------------------------------------------------------------

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

    public function test_interpolate_returns_message_unchanged_when_context_is_empty(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $logger->info('no placeholders here');

        self::assertSame('no placeholders here', $logger->records[0]['message']);
    }

    public function test_interpolate_handles_null_context_value(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $logger->info('{val}', ['val' => null]);

        self::assertSame('null', $logger->records[0]['message']);
    }

    public function test_interpolate_handles_non_stringable_object(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $obj    = new \stdClass();
        $logger->info('{obj}', ['obj' => $obj]);

        self::assertSame('[object stdClass]', $logger->records[0]['message']);
    }

    public function test_interpolate_handles_resource_context_value(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $res    = fopen('php://memory', 'r');
        $logger->info('{res}', ['res' => $res]);
        fclose($res);

        self::assertSame('[resource]', $logger->records[0]['message']);
    }

    // -------------------------------------------------------------------
    // writeLog — base implementation
    // -------------------------------------------------------------------

    public function test_write_log_sends_message_to_error_log(): void
    {
        $tmp      = tempnam(sys_get_temp_dir(), 'phpunit_');
        $original = ini_get('error_log');
        ini_set('error_log', $tmp);

        try {
            $logger = new Logger(LogLevel::DEBUG, true);
            $logger->debug('write-log-coverage-test');
        } finally {
            ini_set('error_log', $original ?: '');
        }

        self::assertStringContainsString('write-log-coverage-test', (string) file_get_contents($tmp));
        @unlink($tmp);
    }
}

final class CapturingLogger extends Logger
{
    /** @var array<int, array{level: string, message: string}> */
    public array $records = [];

    protected function writeLog(string $level, string $message): void
    {
        $this->records[] = [
            'level'   => $level,
            'message' => $message,
        ];
    }
}
