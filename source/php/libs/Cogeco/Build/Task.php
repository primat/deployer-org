<?php namespace Cogeco\Build;

use \Cogeco\Build\Exception\CommandException;
use \Cogeco\Build\Exception\ExitStatusException;

/**
 * A parent class for all tasks
 */
class Task
{
	const CMD_IO_BUFFER = 4096;

	/** @var bool $muteOutput Used to mute all output */
	public static $muteOutput = FALSE;

	/** @var resource $logFileHandle */
	private static $logFileHandle;

	/**
	 * Creates a log file name based on config settings
	 * @return string
	 * @throws exception
	 */
	public static function buildLogFilePath()
	{
		$dir = SCRIPT_DIR . "/logs/";
		if (! is_dir($dir)) {
			mkdir($dir);
		}
		if (! is_dir($dir)) {
			throw new exception('Could not create log file directory ' . $dir);
		}
		// Build the complete log file path
		$logFile = $dir . SCRIPT_FILE_BASENAME;
		if (Config::get('logging.distinct') == TRUE) {
			$logFile .= "_" . Config::get('datetime.slug');
		}
		$logFile .= '.txt';

		return $logFile;
	}

	/**
	 * Performs operations meant to run at the end of a script
	 */
	public static function endOfScriptMaintenance()
	{
		if (Config::get('output.script.duration')) {
			Task::outputElapsedTime();
		}

		if (! empty(self::$logFileHandle)) {
			fclose(self::$logFileHandle);
		}
	}

	public static function getLogFileHandle()
	{

	}

	/**
	 * @param $cmd
	 * @param bool $echoOutput
	 * @return string
	 * @throws CommandException
	 * @throws Exception\ExitStatusException
	 */
	public static function runCmd($cmd, $echoOutput = TRUE)
	{
		$descriptorSpec = array(
			0 => array("pipe", "r"), // stdin is a pipe that the child will read from
			1 => array("pipe", "w"), // stdout is a pipe that the child will write to
			// do nothing with stderr
		);
		$pipes = array();
		$result = '';

		$process = proc_open($cmd, $descriptorSpec, $pipes);

		if (is_resource($process)) {

			stream_set_blocking($pipes[1] , 0);
			//stream_set_blocking($pipes[2] , 0);

			while (! feof($pipes [1])) {
				$read = fread($pipes[1] , self::CMD_IO_BUFFER);
				$result .= $read;
				if ($echoOutput) {
					self::log($read);
				}
			}

			fclose($pipes[1]);
			//fclose($pipes[2]);

			$exitStatus = proc_close($process);
			if ($exitStatus > 0) {
				throw new ExitStatusException("Command failed.\n\tCommand: $cmd\n\tExit status: $exitStatus");
			}
		}
		else {
			throw new CommandException('Command failed: invalid process resource');
		}
		return $result;
	}

	/**
	 * @param $message
	 */
	public static function log($message)
	{
		if (self::$muteOutput || empty($message)) {
			return;
		}

		// Output to console/screen/web page/etc
		if (IS_CLI) {
			echo $message;
		}
		else {
			echo nl2br($message);
		}

		// Output to log file
		if (Config::get('logging.enabled')) {

			// Init the file handle if not done already
			if (self::$logFileHandle === NULL) {
				self::initLogFile();
			}
			self::writeLog($message);
		}
		flush();
	}

	/**
	 * Writes a chunk of text to the log file, assuming the log file is open and writable
	 * @param $message
	 */
	public static function writeLog($message)
	{
		if (empty(self::$logFileHandle)) {
			// TODO Consider triggering an error
			return;
		}
		fwrite(self::$logFileHandle, $message);
	}

	/**
	 * Initializes a log file handle for further use
	 * @throws Exception
	 */
	public static function initLogFile()
	{
		if (! empty(self::$logFileHandle)) {
			fclose(self::$logFileHandle);
		}

		$filePath = self::buildLogFilePath();
		self::$logFileHandle = fopen($filePath, 'w');
		if (empty(self::$logFileHandle)) {
			throw new Exception('Unable to create log file ' . $filePath . ' for writing');
		}
	}

	/**
	 * This method is designed to handle un caught exceptions so it should be registered with set_exception_handler()
	 * @param \Exception $e
	 */
	public static function exceptionHandler($e)
	{
		self::log($e->getMessage() . "\n\nAbandon ship!\n---------------------------------------\n");
		exit;
	}

	/**
	 *
	 */
	public static function outputElapsedTime()
	{
		$endTime = time();
		$elapsedTime = $endTime - Build::getStartTime();

		if ($elapsedTime === $endTime) {
			$elapsedTime = 0;
		}

		self::log("\n---------------------------------------\n");
		self::log("Script execution time: " . gmdate("H:i:s", $elapsedTime));
		self::log("\n---------------------------------------\n\n");
	}
}
