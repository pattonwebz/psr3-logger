<?php
/**
 * PSR-3 compatible logger implementation
 *
 * @package Pattonwebz\Psr3Logger
 */

namespace Pattonwebz\Psr3Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * PSR-3 compatible logger implementation.
 */
class Logger implements LoggerInterface {

	/**
	 * Log prefix for all log messages.
	 *
	 * @var string
	 */
	private $prefix = '';

	/**
	 * Minimum log level to record.
	 *
	 * @var string
	 */
	private $minimum_level;

	/**
	 * Whether to log to WordPress debug.log.
	 *
	 * @var bool
	 */
	private $enabled;

	/**
	 * Valid log levels as defined by PSR-3.
	 *
	 * @var array
	 */
	private $valid_levels = [
		LogLevel::EMERGENCY,
		LogLevel::ALERT,
		LogLevel::CRITICAL,
		LogLevel::ERROR,
		LogLevel::WARNING,
		LogLevel::NOTICE,
		LogLevel::INFO,
		LogLevel::DEBUG,
	];

	/**
	 * Severity map to translate PSR-3 levels to WordPress log levels.
	 *
	 * @var array
	 */
	private $severity_map = [
		LogLevel::EMERGENCY => 0,
		LogLevel::ALERT     => 1,
		LogLevel::CRITICAL  => 2,
		LogLevel::ERROR     => 3,
		LogLevel::WARNING   => 4,
		LogLevel::NOTICE    => 5,
		LogLevel::INFO      => 6,
		LogLevel::DEBUG     => 7,
	];

	/**
	 * Constructor.
	 *
	 * @param string $minimum_level Minimum log level to record.
	 * @param bool   $enabled Whether logging is enabled.
	 */
	public function __construct( string $minimum_level = LogLevel::DEBUG, bool $enabled = true ) {
		$this->setMinimumLevel( $minimum_level );
		$this->enabled = $enabled;
	}

	/**
	 * Set the minimum log level.
	 *
	 * @param string $level PSR-3 log level.
	 * @return self
	 * @throws InvalidArgumentException If level is invalid.
	 */
	public function setMinimumLevel( string $level ): self {
		if ( ! in_array( $level, $this->valid_levels, true ) ) {
			throw new InvalidArgumentException( 'Invalid log level: ' . $level );
		}
		$this->minimum_level = $level;
		return $this;
	}

	/**
	 * Set the log prefix.
	 *
	 * @param string $prefix Log prefix.
	 * @return self
	 */
	public function setPrefix( string $prefix ): self {
		$this->prefix = $prefix;
		return $this;
	}

	/**
	 * Enable or disable logging.
	 *
	 * @param bool $enabled Whether logging is enabled.
	 * @return self
	 */
	public function setEnabled( bool $enabled ): self {
		$this->enabled = $enabled;
		return $this;
	}

	/**
	 * Check if a message at the given level would be logged.
	 *
	 * @param string $level PSR-3 log level.
	 * @return bool
	 */
	public function isLevelLoggable( string $level ): bool {
		if ( ! $this->enabled ) {
			return false;
		}

		if ( ! isset( $this->severity_map[ $level ] ) ) {
			return false;
		}

		return $this->severity_map[ $level ] <= $this->severity_map[ $this->minimum_level ];
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function emergency( $message, array $context = [] ): void {
		$this->log( LogLevel::EMERGENCY, $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function alert( $message, array $context = [] ): void {
		$this->log( LogLevel::ALERT, $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function critical( $message, array $context = [] ): void {
		$this->log( LogLevel::CRITICAL, $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function error( $message, array $context = [] ): void {
		$this->log( LogLevel::ERROR, $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function warning( $message, array $context = [] ): void {
		$this->log( LogLevel::WARNING, $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function notice( $message, array $context = [] ): void {
		$this->log( LogLevel::NOTICE, $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function info( $message, array $context = [] ): void {
		$this->log( LogLevel::INFO, $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 */
	public function debug( $message, array $context = [] ): void {
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string $level   PSR-3 log level.
	 * @param string $message Message to log.
	 * @param array  $context Context data.
	 * @return void
	 * @throws InvalidArgumentException If level is invalid.
	 */
	public function log( $level, $message, array $context = [] ): void {
		if ( ! in_array( $level, $this->valid_levels, true ) ) {
			throw new InvalidArgumentException( 'Invalid log level: ' . $level );
		}

		if ( ! $this->isLevelLoggable( $level ) ) {
			return;
		}

		$message     = $this->interpolate( (string) $message, $context );
		$log_message = $this->prefix . $message;

		$this->writeLog( $level, $log_message );
	}

	/**
	 * Interpolates context values into the message placeholders.
	 *
	 * @param string $message Message with placeholders.
	 * @param array  $context Context data.
	 * @return string
	 */
	private function interpolate( string $message, array $context ): string {
		if ( empty( $context ) ) {
			return $message;
		}

		$replace = [];
		foreach ( $context as $key => $val ) {
			// Check if the value can be cast to string.
			if ( is_scalar( $val ) || ( is_object( $val ) && method_exists( $val, '__toString' ) ) ) {
				$replace[ '{' . $key . '}' ] = $val;
			} elseif ( is_array( $val ) ) {
				$replace[ '{' . $key . '}' ] = json_encode( $val );
			} elseif ( is_null( $val ) ) {
				$replace[ '{' . $key . '}' ] = 'null';
			} elseif ( is_object( $val ) ) {
				$replace[ '{' . $key . '}' ] = '[object ' . get_class( $val ) . ']';
			} else {
				$replace[ '{' . $key . '}' ] = '[' . gettype( $val ) . ']';
			}
		}

		return strtr( $message, $replace );
	}

	/**
	 * Write the log message to the appropriate destination.
	 *
	 * @param string $level PSR-3 log level.
	 * @param string $message Log message.
	 * @return void
	 */
	private function writeLog( string $level, string $message ): void {
		error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging.
	}
}
