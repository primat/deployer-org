<?php
/**
 * Properties for cogeco.ca
 */

use \Cogeco\Build\Entity\Dir;
use \Cogeco\Build\Entity\Host;
use \Cogeco\Build\Entity\WorkingCopy;


// ******************
// The list of various accounts
// SSH accounts

// DB accounts


// ******************
// Servers / Clients / Hosts / Machines / etc
// Web servers
$devHost = new Host('', $cogecoAccount, 'uat/dev');

// Database servers


// ******************
// Directories and files
$devDir = new Dir('/var/www/corpo_dev/docroot/', $devHost);


// ******************
// Cogeco.ca working copy
$svnBaseUrl = '';

//OLD 'https://source.cogeco.com/repository/corp/marketingweb/trunk',
$cogecoCaWorkingCopy = new WorkingCopy(
	'corpoCCC-trunk',
	$svnBaseUrl . '/trunk',
	'',
	$cogecoAccount
);

// ******************
// Databases


// ******************
// Email related
