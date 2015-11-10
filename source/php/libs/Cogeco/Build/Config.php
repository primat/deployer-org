<?php namespace Cogeco\Build;

/**
 * Config class takes care of storing variable application settings
 */
class Config
{
	/** @var string[] $config */
	private static $config = array(
		// Misc
		'datetime.slug' => '',

		// Display options
		'output.script.duration'=> TRUE,

		// Logging
		'logging.enabled'  => FALSE,
		'logging.distinct' => FALSE, // Adds a timestamp to the log filename, making it a unique log file

		// Paths to important binaries
		'bash.bin'      => 'bash',
		'expect.bin'    => 'expect',
		'mintty.bin'    => 'mintty',
		'rsync.bin'     => 'rsync',
		'svn.bin'       => 'svn',
		'mysql.bin'     => 'mysql',
		'mysqldump.bin' => 'mysqldump',
	);

	/**
	 * Gets a value from the config based on a provided key
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($key, $default = '')
	{
		if (isset(self::$config[$key])) {
			return self::$config[$key];
		}
		return $default;
	}

	/**
	 * Set the config value, pointed to by $key, to $value
	 * @param $key
	 * @param $value
	 */
	public static function set($key, $value)
	{
		self::$config[$key] = $value;
	}

	/**
	 * This method enables / disables logging the script tasks to a file. If $isDistinctEnabled is false, all logs are
	 * written to the same log file as previous logs, overwriting them. If $isDistinctEnabled is true, then logging is
	 * made to a distinct timestamped file.
	 * @param bool $isEnabled
	 * @param bool $isDistinctEnabled
	 */
	public static function enableLogging($isEnabled = TRUE, $isDistinctEnabled = FALSE)
	{
		$isEnabled = (bool)$isEnabled;
		$isDistinctEnabled = (bool)$isDistinctEnabled;
		self::$config['logging.enabled'] = $isEnabled;
		self::$config['logging.distinct'] = $isDistinctEnabled;
	}

	/**
	 * @param $configFilePath
	 * @throws Exception
	 */
	public static function loadFile($configFilePath)
	{
		// Load the config from the given path
		$config = array();
		if (is_file($configFilePath)) {
			$config = include $configFilePath;
		}
		else {
			throw new Exception('Unable to load config at ' . $configFilePath);
		}

		if (! is_array($config)) {
			throw new Exception("Config file $configFilePath is not returning an array");
		}

		self::$config = $config + self::$config; // Keeps left hand side elements if there are dupes
	}

	/**
	 * @param string $format
	 */
	public static function output($format = 'text')
	{
		if ($format === 'html') {
			echo '<pre>';
			echo self::toString();
			echo '</pre>';
		}
		else {
			echo self::toString() . "\n";
		}
	}

	/**
	 * Displays the config data structure
	 * @return mixed
	 */
	public static function toString()
	{
		return var_export(self::$config, TRUE);
	}
}
