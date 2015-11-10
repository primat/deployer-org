<?php
$rootPath = realpath(__DIR__ . '/../');
set_include_path(get_include_path() . PATH_SEPARATOR . $rootPath . '/source/php/libs/phpseclib/');

include('Crypt/AES.php');


$plaintext = 'This is the plain text to encrypt';

$aes = new Crypt_AES();

$aes->setKey('abcdefghijklmnop');

$ciphertext = $aes->encrypt($plaintext);

echo $aes->decrypt($ciphertext);