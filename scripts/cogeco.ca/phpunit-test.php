<?php
/**
 * Deploys the cogeco.ca trunk from SVN to production, including the database
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\SshTask;
use \Cogeco\Build\Task\PhpUnitTask;
use \Cogeco\Build\Task\SvnTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************

// Common configs for test/prod
$workingCopy = $cogecoCaWorkingCopy;
$revisionToCheckOut = 0;


// *******************************
// Start the build script
// *******************************

Config::enableLogging();


// ********************
// *** Checkout
//
//SvnTask::checkoutClean($workingCopy, $revisionToCheckOut, 0);
//SvnTask::createManifestFile($workingCopy, FALSE, TRUE);


// ********************
// *** Execute unit tests
//
chdir($workingCopy->dir->getPath() . 'dev/phpunit/');
PhpUnitTask::run();
