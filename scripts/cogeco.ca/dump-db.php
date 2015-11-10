<?php
/**
 *
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Entity\WorkingCopy;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\MysqlTask;
use \Cogeco\Build\Task\SvnTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************


// *******************************
// Start the build script
// *******************************

Config::enableLogging();


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("-------- Dump database\n\n");
// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);


// ********************
// Prompt user input
//

// Prepare a list of possible databases to dump
$dbList = array(
	$localPublicDb,
	$localPublicUBCDb,
	$localPublicPromo,
	$localPublicGBIncrease,
	$devPublicDb,
	$dev2PublicDb,
	$dev3PublicDb,
	$dev4PublicDb,
	$uatPublicDb,
	$preprodPublicDb,
	$prodPublicDb
);


// Prompt user to input a source database
$sourceDb = CliTask::promptDatabase($dbList, 'Choose a database:');
Task::log("You chose " . $sourceDb->getDbName() . " on " . $sourceDb->getHost()->getHostname() . "\n\n");

if (($key = array_search($sourceDb, $dbList)) !== false) {
	unset($dbList[$key]);
}

// Dump DB to an sql script
$tmpDumpFile = new File(SCRIPT_DB_DIR, $sourceDb->getHost()->getHostname() . '-' . $sourceDb->getDbName() . '-' .
	Config::get('datetime.slug') . '.sql');
MysqlTask::mysqlDump($sourceDb, $sourceDb->getDbName(), array(), $tmpDumpFile);
