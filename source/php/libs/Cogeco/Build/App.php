<?php namespace Cogeco\Build;

use \Cogeco\Build\Config;
use \Cogeco\Build\Exception;

/**
 * App is the entry point to the build application. It's main purpose is to initialize the app and route the
 * request/command correct controller.
 */

class App
{
	/** @var int $startTime The time that the build script started running */
	private static $startTime = 0;

	/** @var string $projectName */
	// private static $projectName = '..';

	private $profile;

	/** @var string $projectName */
	private $isCli = false;


	/**
	 *
	 */
	public function __construct()
	{
		/*
		 * Register some important handlers
		 */
		spl_autoload_register(array('self', 'autoload'));
		//set_exception_handler(array('\Cogeco\Build\Task', 'exceptionHandler'));

		/*
		 * Whether or not the script is running in the CLI as opposed to a request for a web page
		 */
		$this->isCli = strpos(php_sapi_name(), 'cli', 0) === 0;

		$this->parseRequest();
	}

	private function parseRequest()
	{
		// Re-route to controller
		$controller = '';
		$method = '';
		$args = array();
		if ($this->isCli) {

			// Make sure there is an argv array
			if (empty($_SERVER['argv'])) {
				throw new Exception("CLI: Missing argv");
			}

			if (empty($_SERVER['argv'][1])) {
				echo "Unknown command. Usage: {$_SERVER['argv'][0]} [command] [argument, [argument...]]\n";
				exit;
			}
			else {
				$controller = mb_strtolower($_SERVER['argv'][1]);
			}
			if (isset($_SERVER['argv'][2])) {
				$method = mb_strtolower($_SERVER['argv'][2]);
			}
			if (isset($_SERVER['argv'][3])) {
				$args = array_slice($_SERVER['argv'], 3);
			}
		}
		else if (isset($_SERVER['REQUEST_URI'])) {

			// Handle code to parse the URI
			$uri = trim($_SERVER['REQUEST_URI'], '/');
			$uriParts = explode('/', $uri);

			if (isset($uriParts[1])) {
				$controller = $uriParts[1];
			}
			if (isset($uriParts[2])) {
				$method = $uriParts[2];
			}
			if (count($uriParts) > 3) {
				$args = array_slice($_SERVER['argv'], 3);
			}
		}
		else {
			throw new Exception(__METHOD__ . ": Unable to run in CLI nor HTTP mode");
		}

//		echo "$controller\n";
//		echo "$method\n";
//		print_r($args);
//		echo "\n";

		$this->route($controller, $method, $args);
	}


	private function route($controller, $method = 'index', $args = [])
	{
		if (empty($method)) {
			$method = 'index';
		}

		$controllers = array(
			'user' => '\\Cogeco\\Build\\Controller\\Cli\\User'
		);

		if (!isset($controllers[$controller])) {
			throw new Exception(__METHOD__ . ": Unknown controller $controller");
		}

		$c = new $controllers[$controller]();

		/* @var \Cogeco\Build\Controller\Cli\User $c */
		if (!method_exists($c, $method)) {
			throw new Exception(__METHOD__ . ": Unknown method $controllers[$controller]->$method()");
		}

		echo $c->$method();
	}

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


		//





		/*
		 * Path to a directory where temporary files are created during script execution
		 */
		define('BUILD_TMP_DIR', BUILD_ROOT_DIR . DIRECTORY_SEPARATOR . 'tmp');

		/*
		 * Path to the directory where working copies are cached
		 */
		define('BUILD_WORKING_COPY_DIR', BUILD_ROOT_DIR . DIRECTORY_SEPARATOR . 'working_copies');
		//define('BUILD_WORKING_COPY_DIR', BUILD_ROOT_DIR . DIRECTORY_SEPARATOR . self::$projectName . DIRECTORY_SEPARATOR . 'working_copies');


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
		if (strpos($className, '\\') === FALSE) {
			return;
		}
		$className = ltrim($className, '\\');
		$fileName  = '';
		$namespace = '';
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
		require_once __DIR__ . '/../../' . $fileName;
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



	//public function



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
