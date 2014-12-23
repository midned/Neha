<?php

namespace Neha;


/**
 * Error handler
 *
 * @author Alvaro Carneiro <alv.ccbb@gmail.com>
 * @package Handler
 * @license MIT License
 */
class Handler {

	/**
	 * List of exception handlers
	 *
	 * @var array
	 */
	protected static $catchers = [];

	/**
	 * Add a exception handler
	 *
	 * <code>
	 *	// Catch all uncatched PDO exceptions
	 *	Neha\Handler::catches('PDOException', function($e) {
	 *		echo 'Error with the db!';
	 *	});
	 *
	 *	// catch all of these too
	 *	Neha\Handler::catches('ErrorException', function($e) {
	 *		echo 'Some error with something;
	 *		// you could do something like if($e->getCode() & E_ERROR) die(); or something
	 * 	});
	 *
	 *	// uses reflection to get the exception you want to catch
	 *	Neha\Handler::catches(function(RuntimeException $e) {
	 *		echo 'Only RuntimeExceptions here';
	 *	});
	 *	// is the same as Neha\Handler::catches('RuntimeException' ...);
	 *	
	 *	// catch them all
	 *	Neha\Handler::catches(function() {
	 *		echo 'All uncatched exceptions';
	 *	});
	 *	// is the same as Neha\Handler::catches('Exception', fu(...));
	 *
	 * </code>
	 *
	 *
	 * @param string|callable $target Name of the exception that will be handled or a handler itself that specifies the target in the argument list
	 * @param callable $handler[optional] The exception handler 
	 *
	 * @return void
	 */
	public static function catches($target = null, $handler = null) {
		if (null === $target) {
			throw new \InvalidArgumentException('Parameter $target must be a callback or a string containing the name of the exception to be handled');
		}
		if (is_callable($target)) {
			$handler = $target;
			// Use reflection to get the name of the exception that will be handled
			$target = static::discoverTargetedException($handler);
		}
		elseif ( ! is_callable($handler)) {
			throw new \InvalidArgumentException('Parameter $handler must be a valid callback');
		}

		static::$catchers[$target] = $handler;
	}

	/**
	 * Get the target exception of the specified handler
	 *
	 * @param callable $handler
	 *
	 * @return string
	 */
	protected static function discoverTargetedException($handler) {
		$reflection = new \ReflectionFunction($handler);

		$args = $reflection->getParameters();

		// If you did not specified the exception wanted to be handled
		// use the basic Exception, so you will handle every exception there
		if ($ex = $args[0]->getClass()) {
			$target = $ex->name;
		}
		if (null === $target) {
			$target = 'Exception';
		}

		return $target;
	}

	/**
	 * Handle an exception
	 *
	 * @param \Exception $exception The thrown exception to be handled
	 *
	 * @return boolean
	 */
	public static function handle(\Exception $exception) {
		foreach (array_reverse(static::$catchers) as $target => $callback) {
			if (is_a($exception, $target)) {
				return call_user_func($callback, $exception);
			}
		}
	}

	/**
	 * Register the handler
	 *
	 * @return void
	 */
	public static function register() {
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			if ( ! (error_reporting() & $errno)) {
				return;
			}

			    Handler::handle(new \ErrorException($errstr, $errno, 0, $errfile, $errline));

			    return true;
		});

		set_exception_handler('Neha\Handler::handle');

		// The basic handler, can be overwritten
		static::catches('Exception', function($exception) {
			echo static::format($exception);
		});
	}

	/**
	 * Get a formatted error message of an exception
	 *
	 * @param \Exception $exception The exception
	 *
	 * @return string The formatted error
	 */
	public static function format(\Exception $exception) {
		return sprintf('Uncaught exception <b>%s</b>: "<i>%s</i>" [File <b>%s</b> | Line %s]<br />', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine());
	}

	/**
	 * Restore the error handlers
	 *
	 * @return void
	 */
	public static function restore() {
		restore_error_handler();
		restore_exception_handler();
		static::$catches = [];
	}
}