<?php
/**
 * File containing global properties for build scripts. These should not generally change for each (Deployer) user
 */

use \Cogeco\Build\Entity\Account;
use \Cogeco\Build\Entity\Host;
use \Cogeco\Build\Entity\Email\SmtpConnector;

// Active directory account
$cogecoAccount = new Account((string)getenv("username"));

// Servers / Clients / Hosts
$localDevHost = new Host('', $cogecoAccount, 'dev (local)');

// The channel that script use to send emails
$emailConnector = new SmtpConnector('');
