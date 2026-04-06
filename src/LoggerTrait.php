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
 * Provides PSR-3 logging capabilities to any class via composition.
 *
 * Inject a PSR-3 logger using setLogger() before first use. If no logger
 * is injected, the trait automatically falls back to a no-op NullLogger,
 * ensuring safe operation without requiring explicit null checks.
 *
 * All convenience methods (log_info, log_error, etc.) delegate to the
 * injected logger, making this trait a lightweight adapter for classes
 * that need logging without directly implementing LoggerInterface.
 */
trait LoggerTrait {

	/**
	 * Holds the injected PSR-3 logger instance.
	 *
	 * Null until setLogger() is called or getLogger() lazy-initialises a NullLogger.
	 *
	 * @var LoggerInterface|null
	 */
	protected static $logger = null;

	/**
	 * Injects a PSR-3 logger instance for use by all trait methods.
	 *
	 * Replaces any previously injected logger. Call this during dependency
	 * injection or setup to provide a real logger implementation.
	 *
	 * @param LoggerInterface $logger PSR-3 compatible logger to use.
	 */
	public static function setLogger( LoggerInterface $logger ): void {
		self::$logger = $logger;
	}

	/**
	 * Returns the injected logger, or a NullLogger if none was set.
	 *
	 * Lazy-initialises a NullLogger on first call if setLogger() was never
	 * called, ensuring all log methods can safely delegate without null checks.
	 * Subsequent calls return the same instance (either injected or NullLogger).
	 *
	 * @return LoggerInterface PSR-3 compatible logger instance.
	 */
	protected static function getLogger(): LoggerInterface {
		if ( null === self::$logger ) {
			self::$logger = new NullLogger();
		}

		return self::$logger;
	}

	/**
	 * Logs a message at an arbitrary PSR-3 level.
	 *
	 * Delegates to the injected logger's log() method, or to a NullLogger
	 * if no logger was injected. Use Psr\Log\LogLevel constants for $level.
	 *
	 * @param mixed  $level   PSR-3 log level (use Psr\Log\LogLevel constants).
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log( $level, string $message, array $context = [] ): void {
		self::getLogger()->log( $level, $message, $context );
	}

	/**
	 * Logs an informational message.
	 *
	 * Delegates to the injected logger's info() method. Use for general
	 * informational messages about application flow (e.g., task started,
	 * configuration loaded).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_info( string $message, array $context = [] ): void {
		self::getLogger()->info( $message, $context );
	}

	/**
	 * Logs a runtime error message.
	 *
	 * Delegates to the injected logger's error() method. Use for recoverable
	 * errors that should be monitored (e.g., API call failed, validation error).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_error( string $message, array $context = [] ): void {
		self::getLogger()->error( $message, $context );
	}

	/**
	 * Logs a critical condition message.
	 *
	 * Delegates to the injected logger's critical() method. Use for serious
	 * problems requiring urgent investigation (e.g., service degraded, backup failed).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_critical( string $message, array $context = [] ): void {
		self::getLogger()->critical( $message, $context );
	}

	/**
	 * Logs a warning message.
	 *
	 * Delegates to the injected logger's warning() method. Use for unexpected
	 * but non-critical situations (e.g., deprecated API usage, slow query).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_warning( string $message, array $context = [] ): void {
		self::getLogger()->warning( $message, $context );
	}

	/**
	 * Logs a notice message.
	 *
	 * Delegates to the injected logger's notice() method. Use for noteworthy
	 * events that are part of normal operation (e.g., user login, cache cleared).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_notice( string $message, array $context = [] ): void {
		self::getLogger()->notice( $message, $context );
	}

	/**
	 * Logs an alert message.
	 *
	 * Delegates to the injected logger's alert() method. Use for urgent problems
	 * requiring immediate intervention (e.g., security breach, payment gateway down).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_alert( string $message, array $context = [] ): void {
		self::getLogger()->alert( $message, $context );
	}

	/**
	 * Logs an emergency message.
	 *
	 * Delegates to the injected logger's emergency() method. Use for catastrophic
	 * failures requiring immediate attention (e.g., entire application down, database unavailable).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_emergency( string $message, array $context = [] ): void {
		self::getLogger()->emergency( $message, $context );
	}

	/**
	 * Logs a debug message.
	 *
	 * Delegates to the injected logger's debug() method. Use for verbose diagnostic
	 * data useful during development (e.g., variable dumps, execution traces).
	 *
	 * @param string $message Log message template with optional {placeholder} tokens.
	 * @param array  $context Key-value pairs for placeholder interpolation.
	 */
	protected static function log_debug( string $message, array $context = [] ): void {
		self::getLogger()->debug( $message, $context );
	}
}
