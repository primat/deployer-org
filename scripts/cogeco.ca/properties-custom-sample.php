<?php
/**
 * Custom properties for cogeco.ca
 */

use \Cogeco\Build\Entity\Dir;

// ***
// Misc. accounts used in the build scripts
$iWebAccount->password = '';
$devDbAccount->password = '';
$iWebDbAccount->password = '';

$cogecoCaReleaseEmail->to[] = $cogecoAccount->emailAddress;
$sendOrdersEmail->to[] = $cogecoAccount->emailAddress;

$trunkWcDir = new Dir('C:\\var\\www\\qml203545.cogeco.com\\');
$genesis2014WcDir = new Dir('C:\\var\\www\\qml203545-genesis.cogeco.com\\');
