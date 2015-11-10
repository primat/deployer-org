<?php namespace Cogeco\Build;

use \Cogeco\Build\Config;

/**
 * Class for handling application operations such as exception handlers, autoloaders, shutdown functions, etc
 */

class Build
{
	/** @var int $startTime The time that the build script started running */
	private static $startTime = 0;

	/** @var string $projectName */
	//private static $projectName = '..';


	/**
	 * Initialize the build app
	 */
	public static function init()
	{
		// Don't initialize more than once
		if (self::$startTime > 0) {
			return;
		}

		self::$startTime = time();

		/*
		 * Path to a directory where temporary files are created during script execution
		 */
		define('BUILD_TMP_DIR', BUILD_ROOT_DIR . DIRECTORY_SEPARATOR . 'tmp');

		/*
		 * Path to the directory where working copies are cached
		 */
		define('BUILD_WORKING_COPY_DIR', BUILD_ROOT_DIR . DIRECTORY_SEPARATOR . 'working_copies');
		//define('BUILD_WORKING_COPY_DIR', BUILD_ROOT_DIR . DIRECTORY_SEPARATOR . self::$projectName . DIRECTORY_SEPARATOR . 'working_copies');

		/*
		 * Whether or not the script is running in the CLI as opposed to a request for a web page
		 */
		define('IS_CLI', substr(php_sapi_name(), 0, 3) === 'cli');

		// Try and figure out the full path to the currently running script
		$path = realpath($_SERVER['SCRIPT_FILENAME']);
		if ($path === false) {
			$path = realpath($_SERVER['SCRIPT_NAME']);
		}
		if ($path === false) {
			throw new \Exception('Unable to determine script path');
		}
		$pathParts = pathinfo($path);

		// Disable output buffering for "streaming" display through HTTP and get the path (parts) to the script
		if (! IS_CLI) {
			self::enableOutputFlush();
		}

		/*
		 * Name of the script file that was called (file name without the file extension)
		 */
		define('SCRIPT_FILE_BASENAME', $pathParts['filename']);

		/*
		 * Path to the location that the script is running in
		 */
		define('SCRIPT_DIR', $pathParts['dirname']);

		/*
		 * Path to the location that the script is running in
		 */
		define('SCRIPT_DB_DIR', SCRIPT_DIR . '/db');

		/*
		 * Path to the location where email files are stored
		 */
		define('BUILD_EMAILS_DIR', SCRIPT_DIR . "/emails");

		/*
		 * Path to the location where log files are stored
		 */
		define('BUILD_LOGS_DIR', SCRIPT_DIR . "/logs");

		/*
		 * Register some important handlers
		 */
		spl_autoload_register(array('self', 'autoload'));
		set_exception_handler(array('\Cogeco\Build\Task', 'exceptionHandler'));
		register_shutdown_function(array('\Cogeco\Build\Task', 'endOfScriptMaintenance'));

		/*
		 * Load the default build configuration
		 */
		//Config::loadFile(BUILD_ROOT_DIR . '/config-default.php');
		Config::set('datetime.slug', date('Y-m-d_H-i-s'));
	}

	/**
	 * Gets the script start time (Either 0 or the first time when Build::init() was called)
	 */
	public static function getStartTime()
	{
		return self::$startTime;
	}

	/**
	 * The class autoloader function
	 * @param $className
	 */
	public static function autoload($className)
	{
		if (strpos($className, '\\') === FALSE) { return; }

		$className = ltrim($className, '\\');
		$fileName  = '';
		$namespace = '';
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		require_once __DIR__ . '/../' . $fileName;
	}

	/**
	 * Flushes output for the HTTP buffer
	 */
	private static function enableOutputFlush()
	{
		ini_set('output_buffering', 'off');
		ini_set('zlib.output_compression', false);
		while (@ob_end_flush());
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
	}

//	public static function getCacheFolder()
//	{
//		$path = BUILD_ROOT_DIR . DIRECTORY_SEPARATOR . 'cache';
//		if (! empty(self::$projectName)) {
//			$path .= DIRECTORY_SEPARATOR . self::$projectName;
//		}
//		echo $path;exit;
//		return $path;
//	}
//
//	/**
//	 *
//	 */
//	public static function getProjectName()
//	{
//		return self::$projectName;
//	}
//
//	/**
//	 * @param $name
//	 */
//	public static function setProjectName($name)
//	{
//		self::$projectName = $name;
//	}
}
