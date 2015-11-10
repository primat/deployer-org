<?php
/**
 * Deploys the cogeco.ca trunk from SVN to pre-production
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\MysqlTask;
use \Cogeco\Build\Task\SvnTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script arguments
// *******************************


// *******************************
// Script configuration
// *******************************

// Common configs
$workingCopy = $cogecoCaWorkingCopy;
$revisionToCheckOut = 0;
$sourceDb = $localPublicDb;
$destinationDb = $preprodPublicDb;
$syncDestinationDir1 = $preprodDir;
$deployDatabase = true;

$rsyncOptions1 = new RsyncOptions($workingCopy->dir, $syncDestinationDir1);
$rsyncOptions1
	//->dryRun()
	->chmod('Du=rwx,Dg=rwx,Do=rwx,Fu=rw,Fg=rw,Fo=r')
	->excludesAppend(array('/dev/', '/db/', '/build.xml', '/build.properties'));

$sourceDbSchema = $localPublicDb->getDbName();
$destinationDbSchema =  $preprodPublicDb->getDbName();

if (isset($argv) && in_array('-no-db', $argv)) {
	$deployDatabase = false;
}


// *******************************
// Start the build script
// *******************************
//
Config::enableLogging(TRUE, TRUE);


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("-------- Deploy Preprod\n\n");

// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);


// ********************
// *** Checkout
//
SvnTask::checkoutClean($workingCopy, $revisionToCheckOut, 0);
SvnTask::createManifestFile($workingCopy, FALSE, TRUE);


// ********************
// *** Dump DB
//

if ($deployDatabase) {
	Task::log("\n- Deploy schema {$sourceDbSchema} from {$sourceDb->host->hostname} ");
	Task::log("to {$destinationDbSchema} on {$destinationDb->getHost()->hostname}\n\n");

	// Export DB
	$mysqlDumpFile = new File($workingCopy->getDir()->getPath() . '/db/dump', 'cogeco.sql');
	MysqlTask::mysqlDump($sourceDb, $sourceDbSchema, array(), $mysqlDumpFile);

	// Import DB
	MysqlTask::importDump($destinationDb, $mysqlDumpFile->getPath(), $destinationDbSchema);
}

// ********************
// *** Prep sync to intermediate remote location
//
Task::log("- Syncing revision {$workingCopy->info->commitRevision} with {$syncDestinationDir1->getPath()} on {$syncDestinationDir1->getHost()->hostname}\n\n");

//
FileSyncTask::sync($rsyncOptions1);
