<?php
/**
 * Deploys the cogeco.ca trunk from SVN to production, including the database
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\Dir;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\EmailTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\MysqlTask;
use \Cogeco\Build\Task\SshTask;
use \Cogeco\Build\Task\SvnTask;
use \Cogeco\Build\Task\TimerTask;
use \Cogeco\Build\Task\ViewTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************
$build = 'prod'; // Use test for testing the script or prod for production use

// Common configs for test/prod
$workingCopy = $cogecoCaWorkingCopy;
$revisionToCheckOut = 0;
$sourceDb = $devDb;
$destinationDb = $prodDbPublic;
$syncDestinationDir1 = $preprodDir;
$syncDestinationDir2 = $prodDir;
$sourceDbSchema = 'cogecouat';
$destinationDbSchema = 'cogecouattest';

if ($build === 'prod') {
	// Prod setup
	$syncDestinationDir2 = $prodDir;
	$destinationDbSchema = 'cogeco';
	$cogecoCaReleaseEmail->to = array(
		'mathieu.price@cogeco.com',
//		'mathieu.beauchemin@cogeco.com',
//		'christiane.magee@cogeco.com',
//		'philippe.gauthier@cogeco.com',
//		'guillaume-martin.ratte@cogeco.com',
//		'julien.allen@cogeco.com',
//		'mathieu.price@cogeco.com',
//		'steve.pellan@cogeco.com',
//		'cristian.bredowbuergel@cogeco.com',
//		'Jonathan.Lacroix@cogeco.com',
//		'michel.gratton@cogeco.com',
//		'Felix.Tousignant@cogeco.com',
//		'Alice.Chaumier@cogeco.com',
//		'hamida.hamel@cogeco.com',
	);
}

$rsyncOptions1 = new RsyncOptions($workingCopy->dir, $syncDestinationDir1);
$rsyncOptions1
	//->dryRun()
	->excludesAppend(array('/dev/'));

$rsyncOptions2 = new RsyncOptions(new Dir($syncDestinationDir1->getPath()), new Dir($syncDestinationDir2->getPath()));
$rsyncOptions2
	//->dryRun()
	->useSsh(FALSE)
	->excludesAppend(array('/dev/'));


// *******************************
// Start the build script
// *******************************
//
Config::enableLogging(TRUE, TRUE);


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("-------- Deploy Prod\n\n");

// Chicken quit
//if ($build === 'prod') {
//	CliTask::promptQuit('Deploying to production! Continue? [y/n]: ');
//}

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
Task::log("\n- Deploy schema {$sourceDbSchema} from {$sourceDb->host->hostname} ");
Task::log("to {$destinationDbSchema} on {$destinationDb->getHost()->hostname}\n\n");

//
$tmpDumpFile = new File(SCRIPT_DIR . '/db', "{$sourceDbSchema}-" . Config::get('datetime.slug') . '.sql');
MysqlTask::mysqlDump($sourceDb, $sourceDbSchema, array(), $tmpDumpFile);

// ********************
// *** Prep sync to intermediate remote location
//
Task::log("- Syncing revision {$workingCopy->info->commitRevision} with {$syncDestinationDir1->getPath()} on {$syncDestinationDir1->getHost()->hostname}\n\n");

//
FileSyncTask::sync($rsyncOptions1);


// ********************
// *** Deploy
//
TimerTask::start();

// Import DB
MysqlTask::importDump($destinationDb, $tmpDumpFile->getPath(), $destinationDbSchema);

//
Task::log("- Syncing intermediate directory {$syncDestinationDir1->path} with {$syncDestinationDir2->path}\n\n");
//
$rsyncCommand = FileSyncTask::getRsyncCommand($rsyncOptions2);
SshTask::exec($syncDestinationDir1->getHost(), $rsyncCommand);

// Stop the deploy timer and log it
TimerTask::stop();

Task::log("Deploy time: " . TimerTask::getLastElapsedTime() . "\n\n");

exit;


// ********************
// *** Tag
//
if ($build === 'prod') {
	SvnTask::commit($workingCopy, "Release to Production");
}


// ********************
// *** Notify
//
$cogecoCaReleaseEmail->bodyHtml = ViewTask::load('views/template-release-html.php', array('changes' => ''), TRUE);
$cogecoCaReleaseEmail->bodyText = ViewTask::load('views/template-release-text.php', array('changes' => ''), TRUE);
EmailTask::sendEmail($emailConnector, $cogecoCaReleaseEmail);
