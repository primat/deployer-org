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
class MysqlTask extends Task
{
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

		// For use when converting a dump to SQLite
		// --compatible=ansi --skip-extended-insert --compact

		// Build the command
		$cmd = Config::get('mysqldump.bin') . " -vf -P {$db->port}";
		if ($db->isRemote()) {
			$cmd .= " -h {$db->host->hostname}";
		}

		// Create the folder if it doesn't exist already
		FileSystemTask::mkdir($dumpFile->dir->getPath());

		$cmd .= " -u {$db->account->username} -p{$db->account->password} {$dbName} {$tablesParam}| sed '/^\\/\\*\\!50013 DEFINER/d' > {$dumpFile->getPath()}";

		//$cmd .= " --result-file={$dumpFile->getPath()} -u {$db->account->username} -p{$db->account->password} {$dbName} {$tablesParam} 2>&1";

		self::runCmd($cmd);
		self::log("\n");
	}

	/**
	 * @param \Cogeco\Build\Entity\Database $db
	 * @param $dumpFilePath
	 * @param $dbName
	 * @throws \Cogeco\Build\Exception
	 */
	public static function importDump(Database $db, $dumpFilePath, $dbName)
	{
		$command = 'mysql -P ' . $db->port . ' -h ' . $db->host->hostname . ' -u ' . $db->account->username .
			' -p"' . $db->account->password . '" ' . $dbName . ' < ' . $dumpFilePath . ' 2>&1';
		
		self::log("- Importing MySQL dump to DB {$dbName} at {$db->host->hostname}:{$db->port} \n");
		passthru($command, $err);
		if ($err !== 0) {
			throw new Exception('- MysqlTask ' . __METHOD__ . '() failed');
		}
		self::log("\n");
	}

	/**
	 * @param \Cogeco\Build\Entity\Database $db
	 * @param $dumpFilePath
	 * @param $dbName
	 * @param $options
	 */
	public static function mysqlImport(Database $db, $dumpFilePath, $dbName, $options)
	{
		// use --debug-info to output some debug info
		$cmd = "mysqlimport -P {$db->port} -h {$db->host->hostname} -u {$db->account->username} " .
			"-p{$db->account->password} {$options} {$dbName} \"{$dumpFilePath}\" 2>&1";
		self::log("- Importing {$dumpFilePath} into {$dbName} on {$db->host->hostname}\n");
		self::runCmd($cmd);
		self::log("\n");
	}
}
