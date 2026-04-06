<?php

declare(strict_types=1);

namespace PattonWebz\Psr3Logger\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * Test suite for the LoggerTrait.
 *
 * Verifies that the trait correctly delegates to an injected PSR-3 logger
 * or falls back to a NullLogger when none is provided, and that all
 * convenience methods forward to the underlying logger instance.
 */
final class LoggerTraitTest extends TestCase
{
    protected function setUp(): void
    {
        TraitHarness::resetLogger();
    }

    /**
     * Verifies that the trait falls back to a PSR-3 NullLogger when no logger
     * has been injected via setLogger(), ensuring safe no-op behaviour rather
     * than throwing errors in classes that use the trait before DI is complete.
     */
    public function test_trait_uses_null_logger_when_not_injected(): void
    {
        TraitHarness::logInfo('message without injected logger');

        $logger = TraitHarness::exposeLogger();
        self::assertInstanceOf(NullLogger::class, $logger);
    }

    /**
     * Verifies that getLogger() returns the same instance on repeated calls
     * rather than creating multiple NullLogger instances, ensuring lazy
     * initialisation is stable and predictable.
     */
    public function test_trait_returns_same_logger_instance_on_repeated_calls(): void
    {
        $logger1 = TraitHarness::exposeLogger();
        $logger2 = TraitHarness::exposeLogger();

        self::assertSame($logger1, $logger2);
    }

    /**
     * Verifies that setLogger() replaces any previously injected logger,
     * so subsequent log calls go to the new instance rather than the old one,
     * enabling logger swapping at runtime (e.g., for testing or context changes).
     */
    public function test_trait_replaces_existing_logger_when_set_logger_is_called(): void
    {
        $loggerA = $this->createMock(LoggerInterface::class);
        $loggerB = $this->createMock(LoggerInterface::class);

        $loggerA->expects(self::never())->method('info');
        $loggerB->expects(self::once())->method('info')->with('test', []);

        TraitHarness::setLogger($loggerA);
        TraitHarness::setLogger($loggerB);
        TraitHarness::logInfo('test');
    }

    /**
     * Verifies that the trait's log_info() method correctly forwards the
     * message and context to the injected logger's info() method, ensuring
     * the delegation contract is honoured.
     */
    public function test_trait_forwards_info_message_to_injected_logger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects(self::once())
            ->method('info')
            ->with('hello', ['id' => 123]);

        TraitHarness::setLogger($logger);
        TraitHarness::logInfo('hello', ['id' => 123]);
    }

    /**
     * Verifies that the trait's log() method forwards arbitrary level + message
     * combinations to the injected logger's log() method, ensuring the generic
     * logging interface is correctly delegated.
     */
    public function test_trait_forwards_arbitrary_log_call(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects(self::once())
            ->method('log')
            ->with(LogLevel::NOTICE, 'notice text', ['k' => 'v']);

        TraitHarness::setLogger($logger);
        TraitHarness::logAny(LogLevel::NOTICE, 'notice text', ['k' => 'v']);
    }

    /**
     * Verifies that all seven PSR-3 convenience method wrappers (error, critical,
     * warning, notice, alert, emergency, debug) correctly forward to the injected
     * logger, ensuring the trait provides a complete logging API surface.
     */
    public function test_all_convenience_methods_forward_to_injected_logger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(self::once())->method('error')    ->with('err',  []);
        $logger->expects(self::once())->method('critical') ->with('crit', []);
        $logger->expects(self::once())->method('warning')  ->with('warn', []);
        $logger->expects(self::once())->method('notice')   ->with('note', []);
        $logger->expects(self::once())->method('alert')    ->with('alrt', []);
        $logger->expects(self::once())->method('emergency')->with('emrg', []);
        $logger->expects(self::once())->method('debug')    ->with('dbg',  []);

        TraitHarness::setLogger($logger);
        TraitHarness::logError('err');
        TraitHarness::logCritical('crit');
        TraitHarness::logWarning('warn');
        TraitHarness::logNotice('note');
        TraitHarness::logAlert('alrt');
        TraitHarness::logEmergency('emrg');
        TraitHarness::logDebug('dbg');
    }
}

/**
 * Test harness that exposes the LoggerTrait's protected methods via public
 * static wrappers, enabling black-box testing of delegation behaviour without
 * requiring a real class implementation.
 */
final class TraitHarness
{
    use \PattonWebz\Psr3Logger\LoggerTrait;

    /**
     * Wrapper for the trait's log_info() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logInfo(string $message, array $context = []): void
    {
        self::log_info($message, $context);
    }

    /**
     * Wrapper for the trait's log() method.
     *
     * @param mixed  $level   PSR-3 level string.
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logAny($level, string $message, array $context = []): void
    {
        self::log($level, $message, $context);
    }

    /**
     * Wrapper for the trait's log_error() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logError(string $message, array $context = []): void
    {
        self::log_error($message, $context);
    }

    /**
     * Wrapper for the trait's log_critical() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logCritical(string $message, array $context = []): void
    {
        self::log_critical($message, $context);
    }

    /**
     * Wrapper for the trait's log_warning() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logWarning(string $message, array $context = []): void
    {
        self::log_warning($message, $context);
    }

    /**
     * Wrapper for the trait's log_notice() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logNotice(string $message, array $context = []): void
    {
        self::log_notice($message, $context);
    }

    /**
     * Wrapper for the trait's log_alert() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logAlert(string $message, array $context = []): void
    {
        self::log_alert($message, $context);
    }

    /**
     * Wrapper for the trait's log_emergency() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logEmergency(string $message, array $context = []): void
    {
        self::log_emergency($message, $context);
    }

    /**
     * Wrapper for the trait's log_debug() method.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     */
    public static function logDebug(string $message, array $context = []): void
    {
        self::log_debug($message, $context);
    }

    /**
     * Resets the internal logger to null, used by setUp() to ensure test isolation.
     */
    public static function resetLogger(): void
    {
        self::$logger = null;
    }

    /**
     * Exposes the trait's internal getLogger() method for assertions about
     * the NullLogger fallback behaviour.
     *
     * @return LoggerInterface The current logger instance (injected or NullLogger).
     */
    public static function exposeLogger(): LoggerInterface
    {
        return self::getLogger();
    }
}
