<?php
/**
 * PSR-3 compatible logger implementation
 *
 * @package PattonWebz\Psr3Logger
 */

namespace PattonWebz\Psr3Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * Standalone PSR-3 logger implementation.
 *
 * Provides level-based filtering, message interpolation, prefix support,
 * and a protected writeLog() hook for custom output destinations. By default,
 * writes to PHP's error_log().
 */
class Logger implements LoggerInterface {

	/**
	 * Prefix prepended to all logged messages.
	 *
	 * @var string
	 */
	private $prefix = '';

	/**
	 * Minimum PSR-3 level required for a message to be logged.
	 *
	 * Messages below this threshold are silently dropped.
	 *
	 * @var string
	 */
	private $minimum_level;

	/**
	 * Controls whether log messages are written.
	 *
	 * When disabled, all messages are silently dropped regardless of level.
	 *
	 * @var bool
	 */
	private $enabled;

	/**
	 * List of all valid PSR-3 log level constants.
	 *
	 * Used to validate level strings passed to log() and setMinimumLevel().
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
	 * Maps PSR-3 levels to numeric severity values for threshold comparisons.
	 *
	 * Lower numbers represent higher severity (EMERGENCY = 0, DEBUG = 7).
	 * Used by isLevelLoggable() to determine if a message meets the minimum threshold.
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
	 * Constructs a new logger with the specified minimum level and enabled state.
	 *
	 * @param string $minimum_level Minimum PSR-3 level to log (default: DEBUG logs everything).
	 * @param bool   $enabled       Whether logging is enabled (default: true).
	 */
	public function __construct( string $minimum_level = LogLevel::DEBUG, bool $enabled = true ) {
		$this->setMinimumLevel( $minimum_level );
		$this->enabled = $enabled;
	}

	/**
	 * Sets the minimum log level threshold.
	 *
	 * Messages below this level are silently dropped. Setting a higher severity
	 * level (e.g., WARNING) filters out lower-priority messages (INFO, DEBUG).
	 *
	 * @param  string $level PSR-3 log level constant (use Psr\Log\LogLevel).
	 * @return self          Returns this instance for method chaining.
	 * @throws InvalidArgumentException If $level is not a valid PSR-3 level.
	 */
	public function setMinimumLevel( string $level ): self {
		if ( ! in_array( $level, $this->valid_levels, true ) ) {
			throw new InvalidArgumentException( 'Invalid log level: ' . $level );
		}
		$this->minimum_level = $level;
		return $this;
	}

	/**
	 * Sets a prefix to prepend to all logged messages.
	 *
	 * Useful for distinguishing log sources (e.g., "[MyPlugin] " or "[Queue] ").
	 * Applied after interpolation but before calling writeLog().
	 *
	 * @param  string $prefix Prefix string to prepend (can be empty to clear).
	 * @return self           Returns this instance for method chaining.
	 */
	public function setPrefix( string $prefix ): self {
		$this->prefix = $prefix;
		return $this;
	}

	/**
	 * Enables or disables all logging output.
	 *
	 * When disabled, all messages are silently dropped regardless of level.
	 * Useful for toggling logging in different environments without reconstructing the logger.
	 *
	 * @param  bool $enabled True to enable logging, false to disable.
	 * @return self          Returns this instance for method chaining.
	 */
	public function setEnabled( bool $enabled ): self {
		$this->enabled = $enabled;
		return $this;
	}

	/**
	 * Determines whether a message at the given level would be logged.
	 *
	 * Returns false if logging is disabled, if the level is invalid, or if
	 * the level's severity is below the configured minimum threshold.
	 *
	 * @param  string $level PSR-3 log level to check.
	 * @return bool          True if the level would be logged, false otherwise.
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
	 * Logs a message indicating the system is unusable.
	 *
	 * Use for catastrophic failures requiring immediate attention (e.g., entire
	 * application down, database unavailable). Delegates to log() with EMERGENCY level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function emergency( $message, array $context = [] ): void {
		$this->log( LogLevel::EMERGENCY, $message, $context );
	}

	/**
	 * Logs a message indicating action must be taken immediately.
	 *
	 * Use for urgent problems requiring immediate intervention (e.g., security breach,
	 * payment gateway down). Delegates to log() with ALERT level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function alert( $message, array $context = [] ): void {
		$this->log( LogLevel::ALERT, $message, $context );
	}

	/**
	 * Logs a critical condition.
	 *
	 * Use for serious problems that don't require immediate action but need urgent
	 * investigation (e.g., service degraded, backup failed). Delegates to log() with CRITICAL level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function critical( $message, array $context = [] ): void {
		$this->log( LogLevel::CRITICAL, $message, $context );
	}

	/**
	 * Logs a runtime error that should be monitored.
	 *
	 * Use for recoverable errors that don't require immediate action but should be
	 * tracked (e.g., API call failed, validation error). Delegates to log() with ERROR level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function error( $message, array $context = [] ): void {
		$this->log( LogLevel::ERROR, $message, $context );
	}

	/**
	 * Logs an exceptional occurrence that is not an error.
	 *
	 * Use for unexpected but non-critical situations (e.g., deprecated API usage,
	 * slow query). Delegates to log() with WARNING level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function warning( $message, array $context = [] ): void {
		$this->log( LogLevel::WARNING, $message, $context );
	}

	/**
	 * Logs a normal but significant event.
	 *
	 * Use for noteworthy events that are part of normal operation (e.g., user login,
	 * cache cleared). Delegates to log() with NOTICE level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function notice( $message, array $context = [] ): void {
		$this->log( LogLevel::NOTICE, $message, $context );
	}

	/**
	 * Logs an informational message.
	 *
	 * Use for general informational messages about application flow (e.g., task started,
	 * configuration loaded). Delegates to log() with INFO level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function info( $message, array $context = [] ): void {
		$this->log( LogLevel::INFO, $message, $context );
	}

	/**
	 * Logs detailed debugging information.
	 *
	 * Use for verbose diagnostic data useful during development (e.g., variable dumps,
	 * execution traces). Delegates to log() with DEBUG level.
	 *
	 * @param  string|Stringable $message Message to log.
	 * @param  array             $context Key-value pairs for placeholder interpolation.
	 * @return void
	 */
	public function debug( $message, array $context = [] ): void {
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	/**
	 * Logs a message at an arbitrary PSR-3 level.
	 *
	 * Validates the level, applies threshold filtering via isLevelLoggable(),
	 * interpolates {placeholder} tokens from $context, prepends the configured
	 * prefix, and calls writeLog() to output the final message.
	 *
	 * @param  mixed             $level   PSR-3 level string (use Psr\Log\LogLevel constants).
	 * @param  string|Stringable $message Log message; cast to string before interpolation.
	 * @param  array             $context Key-value pairs to interpolate into {placeholder} tokens.
	 * @return void
	 * @throws InvalidArgumentException If $level is not a valid PSR-3 level.
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
	 * Interpolates context values into message placeholders.
	 *
	 * Replaces {key} tokens with corresponding $context values. Scalars and
	 * Stringable objects are cast to string; arrays are JSON-encoded; null
	 * becomes "null"; non-Stringable objects become "[object ClassName]";
	 * resources become "[resource]".
	 *
	 * @param  string $message Message template with {placeholder} tokens.
	 * @param  array  $context Key-value pairs to interpolate.
	 * @return string          Message with placeholders replaced.
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
	 * Writes the formatted log message to the output destination.
	 *
	 * The base implementation sends messages to PHP's error_log() function.
	 * Subclasses can override this method to write to custom destinations
	 * (e.g., files, databases, external services).
	 *
	 * @param  string $level   PSR-3 log level.
	 * @param  string $message Fully interpolated and prefixed log message.
	 * @return void
	 */
	protected function writeLog( string $level, string $message ): void {
		error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional logging.
	}
}
