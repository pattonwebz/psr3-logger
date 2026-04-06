<?php
/**
 * Logger Trait for classes that need logging capabilities
 *
 * @package PattonWebz\Psr3Logger
 */

namespace PattonWebz\Psr3Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Logger Trait
 *
 * This trait provides logging capabilities to any class that needs it.
 * Inject a PSR-3 logger via setLogger() before first use; falls back to
 * a no-op NullLogger if none is provided.
 */
trait LoggerTrait {

	/**
	 * Logger instance.
	 *
	 * @var LoggerInterface|null
	 */
	protected static $logger = null;

	/**
	 * Set a logger instance directly.
	 *
	 * @param LoggerInterface $logger PSR-3 compatible logger to use.
	 */
	public static function setLogger( LoggerInterface $logger ): void {
		self::$logger = $logger;
	}

	/**
	 * Get logger instance, falling back to a no-op NullLogger if none has been injected.
	 *
	 * @return LoggerInterface PSR-3 compatible logger.
	 */
	protected static function getLogger(): LoggerInterface {
		if ( null === self::$logger ) {
			self::$logger = new NullLogger();
		}

		return self::$logger;
	}

	/**
	 * Log a message at an arbitrary level.
	 *
	 * @param mixed  $level   PSR-3 log level (use Psr\Log\LogLevel constants).
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log( $level, string $message, array $context = [] ): void {
		self::getLogger()->log( $level, $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_info( string $message, array $context = [] ): void {
		self::getLogger()->info( $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_error( string $message, array $context = [] ): void {
		self::getLogger()->error( $message, $context );
	}

	/**
	 * Log a critical message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_critical( string $message, array $context = [] ): void {
		self::getLogger()->critical( $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_warning( string $message, array $context = [] ): void {
		self::getLogger()->warning( $message, $context );
	}

	/**
	 * Log a notice message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_notice( string $message, array $context = [] ): void {
		self::getLogger()->notice( $message, $context );
	}

	/**
	 * Log an alert message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_alert( string $message, array $context = [] ): void {
		self::getLogger()->alert( $message, $context );
	}

	/**
	 * Log an emergency message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_emergency( string $message, array $context = [] ): void {
		self::getLogger()->emergency( $message, $context );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 */
	protected static function log_debug( string $message, array $context = [] ): void {
		self::getLogger()->debug( $message, $context );
	}
}
