<?php

declare(strict_types=1);

namespace PattonWebz\Psr3Logger\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LoggerTraitTest extends TestCase
{
    protected function setUp(): void
    {
        TraitHarness::resetLogger();
    }

    public function test_trait_uses_null_logger_when_not_injected(): void
    {
        TraitHarness::logInfo('message without injected logger');

        self::assertTrue(true);
    }

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

final class TraitHarness
{
    use \PattonWebz\Psr3Logger\LoggerTrait;

    public static function logInfo(string $message, array $context = []): void
    {
        self::log_info($message, $context);
    }

    public static function logAny($level, string $message, array $context = []): void
    {
        self::log($level, $message, $context);
    }

    public static function logError(string $message, array $context = []): void
    {
        self::log_error($message, $context);
    }

    public static function logCritical(string $message, array $context = []): void
    {
        self::log_critical($message, $context);
    }

    public static function logWarning(string $message, array $context = []): void
    {
        self::log_warning($message, $context);
    }

    public static function logNotice(string $message, array $context = []): void
    {
        self::log_notice($message, $context);
    }

    public static function logAlert(string $message, array $context = []): void
    {
        self::log_alert($message, $context);
    }

    public static function logEmergency(string $message, array $context = []): void
    {
        self::log_emergency($message, $context);
    }

    public static function logDebug(string $message, array $context = []): void
    {
        self::log_debug($message, $context);
    }

    public static function resetLogger(): void
    {
        self::$logger = null;
    }
}
