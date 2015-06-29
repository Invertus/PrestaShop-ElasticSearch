<?php

namespace Brad;

/* including Symfony autoloader */
require_once(_ELASTICSEARCH_VENDOR_DIR_.'autoload.php');

/* Monolog namespaces */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AbstractLogger
{
	/*********** Logger properties start */

	const LOG_LINE_SEPARATOR = '------------------------------------------------------------------------------------------------';
	const LOG_ERROR_COLOR = 'red';
	const LOG_INFO_COLOR = 'white';

	public static $enable_logging = true;// Log messages or not
	public static $logger;// Logger instance
	public static $default_log_filename = 'log';
	public static $log_file_extension = 'html';

	private static $logs_dir;// Directory where logs will be saved

	/*********** Logger properties end */

	/**
	 * @return string - Logs directory
	 */
	public static function getLogsDir()
	{
		if (!self::$logs_dir)
			self::$logs_dir = _PS_MODULE_DIR_.'elasticsearch/logs/';

		return self::$logs_dir;
	}

	public function setLogsDir($dir)
	{
		self::$logs_dir = $dir;
	}

	/**
	 * Add records to the log
	 * @param $message String record to log
	 * @param array $vars additional parameters that will be included to log record
	 * @param int $level Severity of log message
	 * @param null|String $filename log filename - not necessary
	 */
	public static function log($message, $vars = array(), $level = Logger::ERROR, $filename = null)
	{
		if (!self::$enable_logging)
			return;

		if (!is_array($vars))
		{
			if (is_object($vars))
				$vars = (array)$vars;
			else
				$vars = array($vars);
		}

		// Logger gets the name of called class for easier tracking of log messages
		$logger_name = get_called_class();

		if (!self::$logger || self::$logger->id != $logger_name)
			self::initLogger($logger_name, $filename, $level);

		switch ($level)
		{
			default:
			case Logger::ERROR:
				self::$logger->addError(
					self::styleMessage($message, self::LOG_ERROR_COLOR),
					$vars
				);
				break;
			case Logger::INFO:
				self::$logger->addInfo(
					self::styleMessage($message, self::LOG_INFO_COLOR),
					$vars
				);
				break;
		}
	}

	/**
	 * Change style of log message
	 * @todo it's not needed at the moment because HtmlFormatter is used
	 * @param $message
	 * @param $color
	 * @return mixed
	 */
	public static function styleMessage($message, $color)
	{
		return $message;
	}

	private static function initLogger($logger_name, $filename = null, $level = Logger::ERROR)
	{
		// create a log channel
		self::$logger = new Logger($logger_name);
		self::$logger->id = $logger_name;

		$handler = new StreamHandler(
			self::getLogsDir().($filename ? $filename : $logger_name.'.'.self::$log_file_extension),// Logs file
			$level // Message severity
		);

		// Defining format of log messages
		$handler->setFormatter(new \Monolog\Formatter\HtmlFormatter());

		self::$logger->pushHandler($handler, $level);
	}
}