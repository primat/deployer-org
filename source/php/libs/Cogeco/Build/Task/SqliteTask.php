<?php namespace Cogeco\Build\Task;

use \Cogeco\Build\Entity\WorkingCopy;
use \Cogeco\Build\Entity\Account;
use \Cogeco\Build\Entity\Node;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Entity\Database;
use \Cogeco\Build\Exception;
use \Cogeco\Build\Config;
use \Cogeco\Build\Task;

/**
 *
 */
class SqliteTask extends Task
{
	//protected $lastDumpFileName ='';

	/**
	 * @param \Cogeco\Build\Entity\Database $db
	 * @param \Cogeco\Build\Entity\File $dumpFile
	 * @param $dbName
	 * @param array $tables
	 * @throws \Cogeco\Build\Exception
	 */
	public static function mysqlDump(Database $db, $dbName, array $tables = array(), File $dumpFile)
	{
		if (! is_array($tables) || count($tables) === 0) {
			$tablesParam = '';
		}
		else {
			$tablesParam = explode(' ', $tables) . ' ';
		}

		// Presentation
		self::log("- Getting dump of {$dbName}");
		if ($db->isRemote()) {
			Task::log(" from {$db->host->hostname}");
		}
		self::log("\n");

		// Build the command
		$cmd = Config::get('mysqldump.bin') . " -vf -P {$db->port}";
		if ($db->isRemote()) {
			$cmd .= " -h {$db->host->hostname}";
		}

		// Create the folder if it doesn't exist already
		FileSystemTask::mkdir($dumpFile->dir->getPath());

		$cmd .= " -u {$db->account->username} -p{$db->account->password} {$dbName} {$tablesParam}| sed '/^\\/\\*\\!50013 DEFINER/d' > {$dumpFile->getPath()}";

		self::runCmd($cmd);
		self::log("\n");
	}

}
