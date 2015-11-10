<?php
/**
 * Get the orders from cogeco.ca and send them by email to whoever needs it
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Task;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\EmailTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\SshTask;
use \Cogeco\Build\Task\ViewTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************
$build = 'prod'; // Use test for testing the script or prod for production use

// Common configs for test/prod

// Prod setup
if ($build === 'prod') {

	$remoteFileOld = new File('/var/www/prod/logs/', 'orders.log', $prodHost);
	$sendOrdersEmail->to = array(
		//'mathieu.price@cogeco.com',
		'cristian.bredowburgel@cogeco.com',
		'mathieu.bernatchez@cogeco.com',
		'yasmina.saiari@cogeco.com',
		'Stephane.Melancon@cogeco.com',
		'Tristan.Audet@cogeco.com',
		'Web.Marketing@cogeco.com'
	);
}
else {
	$remoteFileOld = new File('/var/www/prod/logs/', 'orders2.log', $prodHost);
}

// Common configs for test/prod
$destinationDirPath = '\\eBusiness_Cust_Data_Mngt\\Orders_Archives';
$destDirPathPrefix = '\\\\mtl-fs1\\dept';
$destDirPathPrefixCygwin = 'S:';
$sharedDestinationDir = $destDirPathPrefix . $destinationDirPath;
$localDestinationDir = $destDirPathPrefixCygwin . $destinationDirPath;
$remoteFileNew = new File($remoteFileOld->dir, 'orders_' . Config::get('datetime.slug') . '.log');
$destinationFile = new File($localDestinationDir, $remoteFileNew->name);
$rsyncOptions = new RsyncOptions($remoteFileNew, $destinationFile);
$rsyncOptions->chmod('u=rw,g=rw,o=');



// *******************************
// Start the build script
// *******************************

Config::enableLogging();


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("- Move cogeco.ca orders.log to secured shared drive\n\n");

// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);

// ********************
// *** Move the file to the shared drive
//
// Firstly, move it on the remote server
SshTask::moveFile($remoteFileOld, $remoteFileNew->getPath());
//SshTask::copyFile($remoteFileOld, $remoteFileNew->getPath());

// Sync from the remote server to the shared drive
FileSyncTask::sync($rsyncOptions);

// Verify that the file was created on the shared drive and then delete it on the remote server if it exists
if (! is_file($destinationFile->getPath())) {
	throw new \Exception('File was not successfully copied to the shared drive!');
}

//If the file was successfully transferred, delete it on the remote host
//SshTask::deleteFile($remoteFileNew);


// ********************
// *** Notify
//
$pathsHtml = <<<HTML
<p>
	<a href="file:{$sharedDestinationDir}">
		{$sharedDestinationDir}
	</a>
	<br />
	<a href="file:{$sharedDestinationDir}\\{$destinationFile->name}">
		{$sharedDestinationDir}\\{$destinationFile->name}
	</a>
</p>
HTML;

$pathsText = <<<TEXT
	{$sharedDestinationDir}
	{$sharedDestinationDir}\\{$destinationFile->name}
TEXT;

$sendOrdersEmail->bodyHtml = ViewTask::load('views/send-orders-html.php', array('pathToLogs' => $pathsHtml), TRUE);
$sendOrdersEmail->bodyText = ViewTask::load('views/send-orders-text.php', array('pathToLogs' => $pathsText), TRUE);
EmailTask::sendEmail($emailConnector, $sendOrdersEmail);
