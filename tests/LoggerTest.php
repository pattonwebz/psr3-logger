<?php

declare(strict_types=1);

namespace PattonWebz\Psr3Logger\Tests;

use PattonWebz\Psr3Logger\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * Test suite for the PSR-3 Logger implementation.
 *
 * Verifies log level gating, message interpolation, prefix support, and
 * compliance with the PSR-3 LoggerInterface contract.
 */
final class LoggerTest extends TestCase
{
    // -------------------------------------------------------------------
    // setMinimumLevel
    // -------------------------------------------------------------------

    /**
     * Verifies that setMinimumLevel() throws InvalidArgumentException for any string
     * that is not a recognised PSR-3 level, so misconfigured loggers fail fast at
     * construction rather than silently swallowing log messages.
     */
    public function test_set_minimum_level_rejects_invalid_level(): void
    {
        $logger = new Logger();

        $this->expectException(InvalidArgumentException::class);
        $logger->setMinimumLevel('not-a-valid-level');
    }

    /**
     * Verifies that the fluent setter methods (setPrefix, setEnabled, setMinimumLevel)
     * return the Logger instance itself, so calls can be chained for more concise
     * configuration code.
     */
    public function test_fluent_setters_support_method_chaining(): void
    {
        $logger = new Logger();

        $result = $logger
            ->setPrefix('[TEST] ')
            ->setEnabled(false)
            ->setMinimumLevel(LogLevel::ERROR);

        self::assertInstanceOf(Logger::class, $result);
        self::assertSame($logger, $result);
    }

    // -------------------------------------------------------------------
    // isLevelLoggable
    // -------------------------------------------------------------------

    /**
     * Verifies that isLevelLoggable() correctly applies both the minimum level threshold
     * and the enabled flag, so disabled loggers never emit messages and messages below
     * the threshold are filtered out.
     */
    public function test_is_level_loggable_respects_threshold_and_enabled_state(): void
    {
        $logger = new Logger(LogLevel::WARNING, true);

        self::assertTrue($logger->isLevelLoggable(LogLevel::ERROR));
        self::assertFalse($logger->isLevelLoggable(LogLevel::INFO));

        $logger->setEnabled(false);
        self::assertFalse($logger->isLevelLoggable(LogLevel::ERROR));
    }

    /**
     * Verifies that isLevelLoggable() returns false for any level string not defined
     * in PSR-3, ensuring invalid levels are never logged even if the logger is enabled.
     */
    public function test_is_level_loggable_returns_false_for_unknown_level(): void
    {
        $logger = new Logger(LogLevel::DEBUG, true);

        self::assertFalse($logger->isLevelLoggable('not-a-real-level'));
    }

    /**
     * Verifies that messages logged at exactly the minimum level threshold are
     * written (not filtered), confirming the boundary condition that level N
     * includes messages at level N as well as all higher severities.
     */
    public function test_is_level_loggable_allows_messages_at_exact_threshold(): void
    {
        $logger = new Logger(LogLevel::WARNING, true);

        self::assertTrue($logger->isLevelLoggable(LogLevel::WARNING));
    }

    // -------------------------------------------------------------------
    // log() — level gate
    // -------------------------------------------------------------------

    /**
     * Verifies that log() silently drops messages whose level is below the
     * configured minimum threshold, preventing log spam from verbose levels
     * when higher severity filtering is enabled.
     */
    public function test_log_skips_messages_below_minimum_level(): void
    {
        $logger = new CapturingLogger(LogLevel::WARNING, true);
        $logger->debug('this should be filtered');

        self::assertCount(0, $logger->records);
    }

    /**
     * Verifies that log() throws InvalidArgumentException for any level string that
     * is not a valid PSR-3 constant, failing fast before any message interpolation
     * or write logic runs.
     */
    public function test_log_rejects_invalid_level(): void
    {
        $logger = new Logger();

        $this->expectException(InvalidArgumentException::class);
        $logger->log('invalid-level', 'message');
    }

    // -------------------------------------------------------------------
    // Convenience methods (emergency … debug)
    // -------------------------------------------------------------------

    /**
     * Verifies that all eight PSR-3 convenience methods (emergency, alert, critical,
     * error, warning, notice, info, debug) correctly delegate to log() with the
     * appropriate level constant, ensuring the interface is fully implemented.
     */
    public function test_all_convenience_methods_dispatch_to_log(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);

        $logger->emergency('em');
        $logger->alert('al');
        $logger->critical('cr');
        $logger->error('er');
        $logger->warning('wa');
        $logger->notice('no');
        $logger->info('in');
        $logger->debug('de');

        $levels = array_column($logger->records, 'level');

        self::assertContains(LogLevel::EMERGENCY, $levels);
        self::assertContains(LogLevel::ALERT,     $levels);
        self::assertContains(LogLevel::CRITICAL,  $levels);
        self::assertContains(LogLevel::ERROR,     $levels);
        self::assertContains(LogLevel::WARNING,   $levels);
        self::assertContains(LogLevel::NOTICE,    $levels);
        self::assertContains(LogLevel::INFO,      $levels);
        self::assertContains(LogLevel::DEBUG,     $levels);
    }

    // -------------------------------------------------------------------
    // interpolate — all branches
    // -------------------------------------------------------------------

    /**
     * Verifies that log() correctly interpolates context placeholders using
     * {key} syntax, prepends the configured prefix string, and serialises
     * array context values as JSON for safe string embedding.
     */
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

    /**
     * Verifies that messages with no placeholders or an empty context array are
     * returned unchanged, avoiding unnecessary string manipulation overhead when
     * no interpolation is needed.
     */
    public function test_interpolate_returns_message_unchanged_when_context_is_empty(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $logger->info('no placeholders here');

        self::assertSame('no placeholders here', $logger->records[0]['message']);
    }

    /**
     * Verifies that null context values are serialised as the string "null"
     * rather than an empty string or causing a type error, making null values
     * visible in log output for debugging.
     */
    public function test_interpolate_handles_null_context_value(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $logger->info('{val}', ['val' => null]);

        self::assertSame('null', $logger->records[0]['message']);
    }

    /**
     * Verifies that objects without a __toString method are serialised as
     * "[object ClassName]" rather than causing a fatal error or using a
     * generic representation like "Object", making the object type visible.
     */
    public function test_interpolate_handles_non_stringable_object(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $obj    = new \stdClass();
        $logger->info('{obj}', ['obj' => $obj]);

        self::assertSame('[object stdClass]', $logger->records[0]['message']);
    }

    /**
     * Verifies that objects implementing __toString are correctly cast to
     * string via that method, ensuring Stringable objects (like value objects
     * or DTOs) can be logged naturally without custom serialisation.
     */
    public function test_interpolate_handles_stringable_object(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);
        $obj    = new class {
            public function __toString(): string {
                return 'custom-string-representation';
            }
        };
        $logger->info('Value: {obj}', ['obj' => $obj]);

        self::assertSame('Value: custom-string-representation', $logger->records[0]['message']);
    }

    /**
     * Verifies that scalar types (int, float, bool) are correctly cast to
     * their string representations during interpolation, matching PHP's
     * standard scalar-to-string conversion rules.
     */
    public function test_interpolate_handles_scalar_types(): void
    {
        $logger = new CapturingLogger(LogLevel::DEBUG, true);

        $logger->info('int: {val}', ['val' => 42]);
        $logger->info('float: {val}', ['val' => 3.14]);
        $logger->info('bool_true: {val}', ['val' => true]);
        $logger->info('bool_false: {val}', ['val' => false]);

        self::assertSame('int: 42', $logger->records[0]['message']);
        self::assertSame('float: 3.14', $logger->records[1]['message']);
        self::assertSame('bool_true: 1', $logger->records[2]['message']);
        self::assertSame('bool_false: ', $logger->records[3]['message']);
    }

    /**
     * Verifies that resource context values (like file handles or streams)
     * are serialised as "[resource]" rather than attempting to cast them to
     * strings, preventing type errors and making their presence visible.
     */
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

    /**
     * Verifies that the base writeLog() implementation sends the formatted
     * message to PHP's error_log() function, ensuring the default behaviour
     * writes to the configured error log destination.
     */
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

/**
 * Test double that captures log messages in memory rather than writing to
 * error_log, enabling assertions on message content, interpolation, and
 * level routing without filesystem side effects.
 */
final class CapturingLogger extends Logger
{
    /** @var array<int, array{level: string, message: string}> */
    public array $records = [];

    /**
     * Overrides the base writeLog to capture messages in the $records array
     * instead of sending them to error_log().
     *
     * @param string $level   PSR-3 level string.
     * @param string $message Fully interpolated and prefixed log message.
     */
    protected function writeLog(string $level, string $message): void
    {
        $this->records[] = [
            'level'   => $level,
            'message' => $message,
        ];
    }
}
