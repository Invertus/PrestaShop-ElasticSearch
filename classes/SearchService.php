<?php
/**
 * Copyright (c) 2015 Invertus, JSC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/* including Symfony autoloader */
require_once(__DIR__.'/../vendor/autoload.php');

/* Monolog namespaces */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

abstract class SearchService
{
	const ELASTICSEARCH_INSTANCE = 1;
	const ELASTICSEARCH_SERVICE_CLASS_NAME = 'ElasticSearchService';

	public $errors = array();

	/* Array containing all active instances of search services */
	protected static $instance = array();

	/*********** Logger properties start */

	const LOG_LINE_SEPARATOR = '------------------------------------------------------------------------------------------------';
	const LOG_ERROR_COLOR = 'red';
	const LOG_INFO_COLOR = 'white';

	public static $enable_logging = true;// Log messages or not

	public static $logger;// Logger instance
	private static $logs_dir;// Directory where logs will be saved
	public static $default_log_filename = 'log';
	public static $log_file_extension = 'html';

	/*********** End of Logger properties */

	public $client; // Search service client

	public $index_prefix;
	public $index; // Full index name (prefix + id_shop)

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
	 * Returns search instance
	 * @param int $type - which instance to use
	 * @return null|object
	 */
	public static function getInstance($type)
	{
		if (!isset(self::$instance[$type]))
		{
			$class = self::getClass($type);

			if (!$class)
			{
				self::log('Search service class is missing', array('class' => $class));
				return null;
			}

			if (!class_exists($class.'.php'))
				require_once($class.'.php');

			self::$instance[$type] = new $class();
		}

		return self::$instance[$type];
	}

	public static function getClass($type)
	{
		switch ($type)
		{
			case self::ELASTICSEARCH_INSTANCE:
				if (!file_exists(__DIR__.'/'.self::ELASTICSEARCH_SERVICE_CLASS_NAME.'.php'))
					return false;
				return self::ELASTICSEARCH_SERVICE_CLASS_NAME;
		}

		return false;
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

	protected function initIndex()
	{
		$this->initIndexPrefix();

		if (!$this->index)
			$this->index = $this->index_prefix.Context::getContext()->shop->id;
	}

	abstract protected function initClient();

	abstract public function testSearchServiceConnection();

	abstract protected function initIndexPrefix();

	abstract public function getDocumentById($type, $id);

	abstract public function createDocument($body, $id, $type);

	abstract public function indexAllProducts($delete_old);

	abstract public function indexAllCategories();

	abstract public function buildSearchQuery($type, $term);

	abstract public function deleteDocumentById($id_shop, $id, $type);

	abstract public function documentExists($id_shop, $id, $type);

	abstract public function search($type, array $query, $pagination, $from, $order_by, $order_way, $filter);

	abstract public function getDocumentsCount($type, array $query, $filter = null);

	abstract public function indexExists($index_name);

	abstract protected function createIndex($index_name);

	abstract public function deleteShopIndex();
}