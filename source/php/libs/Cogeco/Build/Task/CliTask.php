<?php namespace Cogeco\Build\Task;

use \Cogeco\Build\Entity;
use \Cogeco\Build\Entity\Account;
use \Cogeco\Build\Entity\Database;
use \Cogeco\Build\Entity\Dir;
use \Cogeco\Build\Task;

/**
 * Performs task related to the command line interface
 */
class CliTask extends Task
{
	/**
	 * Prompt the user for an account password
	 * @param Account $account
	 * @param bool $forcePrompt
	 */
	public static function promptAccountPassword(Account $account, $forcePrompt = FALSE)
	{
		// Prompt for all necessary passwords
		if (empty($account->password) || $forcePrompt) {
			self::log('Enter password for user ' . $account->username . ': ');
			$account->password = self::readStdin();
			self::log("\n");
		}
	}

	/**
	 * Prompt the user to select a database
	 * @param Database[] $databases
	 * @param string $promptText
	 * @return \Cogeco\Build\Entity\Database
	 */
	public static function promptDatabase($databases = NULL, $promptText = 'Choose a database:')
	{
		$choices = array();
		if (empty($databases)) {
			$databases = Entity::getList('Database', true);
		}
		$mapping = array();
		foreach($databases as $index => $db) {
			/** @var $db \Cogeco\Build\Entity\Database */
			$mapping[] = $db;
			$choices[] = $db->getDbName() . ' on ' . $db->getHost()->getHostname();
		}
		$selection = CliTask::promptMultipleChoice($choices, $promptText);
		return $mapping[$selection];
	}

	/**
	 * Ask he user if they want to quit the script immediately
	 * @param string $promptMessage
	 */
	public static function promptQuit($promptMessage = '')
	{
		if (empty($promptMessage)) {
			$promptMessage = 'Press y to continue or n to quit [y/n]: ';
		}

		$input  = '';
		while ($input !== 'y' && $input !== 'n') {
			self::log( "\n" . $promptMessage );
			$input = self::readStdin();
			self::writeLog($input . "\n");
		}

		self::log("\n");

		if ($input === 'n') {
			self::log("Exiting immediately\n");
			exit();
		}
	}

	/**
	 * Prompt a user to select one amongst many options
	 * @param mixed[] $choices
	 * @param string $customPromptText
	 * @return int
	 */
	public static function promptMultipleChoice(array $choices, $customPromptText = "")
	{
		if (empty($customPromptText)) {
			$customPromptText = "Please choose one of the following:\n";
		}
		else {
			$customPromptText .= "\n";
		}
		$choiceCnt = count($choices);
		$choiceText = '';
		if ($choiceCnt === 1) {
			$choiceText = '1 or ';
		}
		else if ($choiceCnt > 1) {
			$choiceText = "1-{$choiceCnt} or ";
		}
		$result = 0;

		while(TRUE) {
			$result = 0;
			self::log($customPromptText);
			$counter = 1;
			if ($choiceCnt < 1) {
				self::log("No choices available\n");
			}
			foreach($choices as $i => $choice) {
				self::log("\t[{$counter}] {$choice}\n");
				$counter++;
			}

			self::log("Choice [{$choiceText}e(x)it]: ");
			$result = trim(self::readStdin());

			self::log("\n");
			if ($result === 'x') {
				self::log("Exiting immediately\n");
				exit;
			}
			else if (ctype_digit($result) && $result > 0 && $result <= $choiceCnt) {
				break;
			}
		}
		return $result - 1;
	}

	/**
	 * Prompt the user to select a local directory to sync file from
	 * @param string $promptText
	 * @return \Cogeco\Build\Entity\Dir
	 */
	public static function promptLocalSyncDir($promptText = 'Choose a local directory to sync from:')
	{
		$choices = array();
		$dirs = Entity::getList('Dir');
		$mapping = array();
		foreach($dirs as $index => $dir) {
			/** @var $dir \Cogeco\Build\Entity\Dir */
			if ($dir->host === NULL && is_dir($dir->path) && stripos($dir->path, BUILD_ROOT_DIR) === FALSE) {
				$mapping[] = $dir;
				$choices[] = $dir->path;
			}
		}
		$selection = CliTask::promptMultipleChoice($choices, $promptText);
		return $mapping[$selection];
	}

	/**
	 * Prompt the user to select a repository to sync
	 * @param string $promptText
	 * @return \Cogeco\Build\Entity\WorkingCopy
	 */
	public static function promptRepo($promptText = 'Choose a repository to sync:')
	{
		$choices = array();
		$workingCopies = Entity::getList('WorkingCopy');
		$mapping = array();
		foreach($workingCopies as $index => $workingCopy) {
			/** @var $workingCopy \Cogeco\Build\Entity\WorkingCopy */
				$mapping[] = $workingCopy;
				$choices[] = $workingCopy->repoUrl;
		}
		$selection = CliTask::promptMultipleChoice($choices, $promptText);
		return $mapping[$selection];
	}

	/**
	 * Prompt the user to select a directory for a specific remote host
	 * @param Dir[] $dirs
	 * @param string $promptText
	 * @return mixed
	 */
	public static function promptDir(array $dirs, $promptText = 'Choose a remote directory:')
	{
		$choices = array();
		if (empty($dirs)) {
			$dirs = Entity::getList('Dir');
		}
		foreach($dirs as $index => $dir) {
			/** @var $dir \Cogeco\Build\Entity\Dir */
			$displayString = $dir->getPath();
			if ($dir->getHost() !== NULL) {
				$displayString .= ' on ' . $dir->getHost()->hostname;
			}
			$choices[] = $displayString;
		}
		$selection = CliTask::promptMultipleChoice($choices, $promptText);
		return $dirs[$selection];
	}

	/**
	 * Prompt the user to select a directory for a specific remote host
	 * @param null $hostFilter
	 * @param string $promptText
	 * @return mixed
	 */
	public static function promptRemoteDir($hostFilter = NULL, $promptText = 'Choose a remote directory:')
	{
		$choices = array();
		$dirs = Entity::getList('Dir');
		$mapping = array();
		foreach($dirs as $index => $dir) {
			/** @var $dir \Cogeco\Build\Entity\Dir */
			if ($hostFilter === NULL || $dir->getHost() === $hostFilter) {
				$mapping[] = $dir;
				$choices[] = $dir->getPath();
			}
		}
		$selection = CliTask::promptMultipleChoice($choices, $promptText);
		return $mapping[$selection];
	}

	/**
	 * Prompt the user to choose a host
	 * @param Entity\Host[] $hosts
	 * @param string $promptText
	 * @return Entity\Host
	 */
	public static function promptHost(array $hosts = NULL, $promptText = 'Choose a host:')
	{
		$choices = array();
		if (empty($hosts)) {
			$hosts = Entity::getList('Host');
		}
		foreach($hosts as $index => $host) {
			/** @var $host \Cogeco\Build\Entity\Host */
			$name = $host->hostname;
			if (! empty($host->name)) {
				$name .= ' - ' . $host->name;
			}
			$choices[] = $name;
		}
		$selection = CliTask::promptMultipleChoice($choices, $promptText);
		return $hosts[$selection];
	}

	/**
	 * Prompt the user to select a working copy
	 * @param string $promptText
	 * @return mixed
	 */
	public static function promptWorkingCopy($promptText = 'Choose a working copy:')
	{
		$choices = array();
		$workingCopies = Entity::getList('WorkingCopy');
		foreach($workingCopies as $index => $wc) {
			/** @var $wc \Cogeco\Build\Entity\WorkingCopy */
			$choices[] = $wc->id;
		}
		$selection = CliTask::promptMultipleChoice($choices, $promptText);
		return $workingCopies[$selection];
	}

	/**
	 * Read characters from STDIN until enter is pressed
	 * @return string
	 */
	public static function readStdin()
	{
		$fr = fopen("php://stdin","r");
		do {
			$input = fgets($fr, 128);
			$input = rtrim($input);
		} while (empty($input));
		fclose ($fr);
		return $input;
	}
}
