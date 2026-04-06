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

    public static function resetLogger(): void
    {
        self::$logger = null;
    }
}

