<?php namespace Cogeco\Build\Task;

use \Cogeco\Build\Entity\Host;
use \Cogeco\Build\Entity\Database;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Exception;
use \Cogeco\Build\Task;

require_once BUILD_ROOT_DIR . '/vendor/autoload.php';
require_once BUILD_ROOT_DIR . '/vendor/phpseclib/phpseclib/phpseclib/Net/SFTP.php';
require_once BUILD_ROOT_DIR . '/vendor/phpseclib/phpseclib/phpseclib/Crypt/RSA.php';

define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX); // NET_SFTP_LOG_COMPLEX or NET_SFTP_LOG_SIMPLE
define('NET_SSH2_LOGGING', NET_SSH2_LOG_COMPLEX); // NET_SSH2_LOG_COMPLEX or NET_SSH2_LOG_SIMPLE


/**
 *
 */
class SshTask extends Task
{
	const SSH_KEY_NAME = 'deployer-generated-key';

	/** @var \Net_SFTP[] $handles An array of SSH connection handles */
	public static $handles = array();

	/**
	 * @param \Cogeco\Build\Entity\Host $host
	 * @return \Net_SFTP
	 * @throws \Cogeco\Build\Exception
	 */
	public static function connect(Host $host)
	{
		// Throw an exception if no host is provided
		if (empty($host)) {
			throw new Exception(__METHOD__ . "() No host specified");
		}

		// If the connection doesn't already exist, create it
		if (empty(self::$handles[$host->hostname])) {
			self::log("- Starting an SSH session on {$host->hostname} for user {$host->account->username}\n\n");
			self::$handles[$host->hostname] = $handle = new \Net_SFTP($host->hostname);
			if (! $handle->login($host->account->username, $host->account->password)) {
				throw new Exception(__METHOD__ . '() SSH connection failed');
			}
			// Set the home folder, if it isn't explicitly set already
			$homeDirPath = $handle->pwd();
			if (empty($host->homeDirPath) && ! empty($homeDirPath)) {
				$host->homeDirPath = $homeDirPath;
			}
		}
		return self::$handles[$host->hostname];
	}

	/**
	 * Copies a file on the remote server from $remoteFile->path to $newPath
	 * @param File $remoteFile
	 * @param $newPath
	 * @throws \Cogeco\Build\Exception
	 */
	public static function copyFile(File $remoteFile, $newPath)
	{
		self::exec($remoteFile->getHost(), "cp {$remoteFile->getPath()} {$newPath}");
	}

	/**
	 * Generate an SSH public / private key pair
	 * @return array
	 */
	public static function generateKeyPair()
	{
		$publickey = '';
		$privatekey = '';
		$rsa = new \Crypt_RSA();
		$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_OPENSSH);
		extract($rsa->createKey());
		$publickey = str_replace('phpseclib-generated-key', self::SSH_KEY_NAME, $publickey);
		return array($publickey, $privatekey);
	}

	/**
	 * @param Host $remoteHost
	 * @return array
	 * @throws \Cogeco\Build\Exception
	 */
//	public static function getAuthorizedKeys(Host $remoteHost)
//	{
//		$sftp = self::connect($remoteHost);
//		$fileContents = $sftp->get("/home/{$remoteHost->account->username}/.ssh/authorized_keys");
//		$fileContents = trim($fileContents);
//		$lastError = trim($sftp->getLastSFTPError());
//		if (strlen($lastError)) {
//			if (strpos($lastError, 'NET_SFTP_STATUS_EOF') === FALSE &&
//				strpos($lastError, 'NET_SFTP_STATUS_NO_SUCH_FILE') === FALSE) {
//				throw new Exception(__METHOD__ . "()\n\t" . $lastError);
//			}
//		}
//
//		$result = array();
//		if (empty($fileContents)) {
//			echo var_export($result);
//			return $result;
//		}
//
//		// Parse the authorized keys file and convert keys to objects/arrays
//		$parts = explode("\n", $fileContents);
//		foreach ($parts as $part) {
//			$part = trim($part);
//			if (empty($part)) {
//				continue;
//			}
//			$subParts = explode(' ', $part);
//			$tmpParts = array();
//			foreach($subParts as $subPart) {
//				$subPart = trim($subPart);
//				if (empty($subPart)) {
//					continue;
//				}
//				$tmpParts[] = $subPart;
//			}
//			$result[] = $tmpParts;
//		}
//		return $result;
//	}

	/**
	 * Provides automation for command line ssh commands which require a tty/pty
	 * @param Host $remoteHost
	 * @throws \Cogeco\Build\Exception
	 */
	public static function GenerateTemporaryKeyPair(Host $remoteHost)
	{
		$sftp = self::connect($remoteHost);
		$sshDir = "{$remoteHost->homeDirPath}/.ssh";

		// Create the .ssh folder if it doesn't exist
		if (! self::dirExists($sftp, $sshDir)) {
			$sftp->mkdir($sshDir, 0700);
			$lastError = trim($sftp->getLastSFTPError());
			if (strlen($lastError)) {
				throw new Exception(__METHOD__ . "() \$sftp->mkdir() failed\n\t" . $lastError);
			}
		}

		// Get the contents of the authorized_keys file
		$fileContents = trim($sftp->get($sshDir . "/authorized_keys")) . "\n";
		$lastError = trim($sftp->getLastSFTPError());
		if (strlen($lastError)) {
			if (strpos($lastError, 'NET_SFTP_STATUS_EOF') === FALSE &&
				strpos($lastError, 'NET_SFTP_STATUS_NO_SUCH_FILE') === FALSE) {
				throw new Exception(__METHOD__ . "() \$sftp->get()\n\t" . $lastError);
			}
		}

		// Produce the key pair
		list($publicKey, $privateKey) = self::generateKeyPair();

		// Clean up old keys and add the new one
		$fileContents = self::removeTemporaryAuthorizedKeys($fileContents);
		$fileContents .= $publicKey . "\n";
		$cmdResult = $sftp->put($sshDir . "/authorized_keys", $fileContents);
		if(! $cmdResult) {
			$lastError = trim($sftp->getLastSFTPError());
			if (strlen($lastError)) {
				if (strpos($lastError, 'NET_SFTP_STATUS_EOF') === FALSE) {
					throw new Exception(__METHOD__ . "() \$sftp->put()\n\t" . $lastError);
				}
			}
		}

		$sftp->chmod(0644, $sshDir . "/authorized_keys");

		$remoteHost->privateKeyPath = BUILD_TMP_DIR . '/.id_rsa_' .$remoteHost->hostname;

		// Copy the private key to a local temporary file
		if(file_put_contents($remoteHost->privateKeyPath , $privateKey) === FALSE) {
			throw new Exception(__METHOD__ . "()\n\t Could not create temporary private key file");
		}
	}

	/**
	 * @param $authorizedKeys
	 * @return string
	 */
	protected static function removeTemporaryAuthorizedKeys($authorizedKeys)
	{
		$result = '';
		$lines = explode("\n", $authorizedKeys);
		foreach($lines as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			if (strpos($line, self::SSH_KEY_NAME) === FALSE) {
				$result .= $line . "\n";
			}
		}
		return $result;
	}

	/**
	 * @param \Cogeco\Build\Entity\File $localFile
	 * @param \Cogeco\Build\Entity\File $remoteFile
	 * @param int $chmod
	 * @throws \Cogeco\Build\Exception
	 */
	public static function uploadFile(File $localFile, File $remoteFile, $chmod = 0664)
	{
		$sftp = self::connect($remoteFile->getHost());
		self::log("- Uploading $localFile->name to {$remoteFile->getHost()->hostname}:{$remoteFile->getPath()}\n");
		$res = $sftp->put($remoteFile->getPath(), $localFile->getPath(), NET_SFTP_LOCAL_FILE);
		if (! $res || count($sftp->getSFTPErrors())) {
			throw new Exception(__METHOD__ . "()\n\t" . implode("\n\t", $sftp->getSFTPErrors()));
		}
		$res = $sftp->chmod($chmod, $remoteFile->getPath());
		if (! $res || count($sftp->getSFTPErrors())) {
			throw new Exception(__METHOD__ . "()\n\t" . implode("\n\t", $sftp->getSFTPErrors()));
		}
		self::log("\n");
	}

	/**
	 * Moves a file on the remote server from $remoteFile->path to $newPath
	 * @param File $remoteFile
	 * @param $newPath
	 * @throws \Cogeco\Build\Exception
	 */
	public static function moveFile(File $remoteFile, $newPath)
	{
		$sftp = self::connect($remoteFile->getHost());
		self::log("- Moving {$remoteFile->getPath()} to {$newPath}\n");
		$res = $sftp->rename($remoteFile->getPath(), $newPath);
		if (! $res || count($sftp->getSFTPErrors())) {
			throw new Exception(__METHOD__ . "()\n\t" . implode("\n\t", $sftp->getSFTPErrors()));
		}
		self::log("\n");
	}

	/**
	 * @param \Cogeco\Build\Entity\File $localDumpFile
	 * @param \Cogeco\Build\Entity\Database $db
	 * @param string $dbName
	 */
	public static function importDb(File $localDumpFile, Database $db, $dbName)
	{
		$remoteFile = new File('/home/'.$db->host->account->username, $localDumpFile->name, $db->host);
		self::uploadFile($localDumpFile, $remoteFile);
		self::mysqlImport($remoteFile, $db, $dbName);
		self::deleteFile($remoteFile);
	}

	/**
	 * Import a sql file into mysql by copying a temp file through SSH then importing it with mysql and deleting the
	 * dump afterward
	 * @param \Cogeco\Build\Entity\File $dumpFile
	 * @param \Cogeco\Build\Entity\Database $db
	 * @param $dbName
	 * @throws \Cogeco\Build\Exception
	 */
	public static function mysqlImport(File $dumpFile, Database $db, $dbName)
	{
		self::log("- Importing {$dumpFile->name} into {$dbName}\n");
		$command = "mysql -u {$db->account->username} -p{$db->account->password} {$dbName} < {$dumpFile->getPath()}";
		self::exec($db->getHost(), $command);
		self::log("\n");
	}

	/**
	 * Delete a file on the remote Host
	 * @param File $remoteFile
	 * @throws \Cogeco\Build\Exception
	 */
	public static function deleteFile(File $remoteFile)
	{
		// Make sure there is a connection open
		$ssh = self::connect($remoteFile->getHost());

		self::log("- Deleting file " . $remoteFile->getPath() . "\n");
		$res = $ssh->delete($remoteFile->getPath());
		if (! $res || count($ssh->getSFTPErrors())) {
			throw new Exception(__METHOD__ . "() - File deletion failed: \n\t" .
				implode("\n\t", $ssh->getSFTPErrors()));
		}
		self::log("\n");
	}

	/**
	 * Execute a one-off command on a remote host
	 * @param \Cogeco\Build\Entity\Host $host The host to run the command on
	 * @param string $command The actual command to execute
	 * @param bool $printOutput Whether or not to display the output from the command
	 * @throws \Cogeco\Build\Exception
	 */
	public static function exec(Host $host, $command, $printOutput = TRUE)
	{
		// Make sure there is a connection open
		$ssh = self::connect($host);

		// log the command
		//self::log("{$host->account->username}@{$host->hostname}: $command\n");

		// Execute the command
		if ($printOutput) {
			$ssh->exec($command, function($str) {
				SshTask::log($str);
			});
			self::log("\n");
		}
		else {
			$ssh->exec($command);
		}
		self::checkCmdExceptions($ssh);
	}

	/**
	 * Test if the given directory exists on the remote machine
	 * @param \Net_SSH2 $ssh The ssh connection handle
	 * @param string $path The directory to test for existence
	 * @return bool TRUE if the directory exists, FALSE otherwise
	 */
	public static function dirExists(\Net_SSH2 $ssh, $path)
	{
		$command = '[ -d ' . $path . ' ] && echo "1" || echo "0"';
		$output = trim($ssh->exec($command));
		self::checkCmdExceptions($ssh);
		return $output === "1";
	}

	/**
	 * Tests if a given file exists on the remote server
	 * @param \Cogeco\Build\Entity\File $file
	 * @return bool
	 */
	public static function fileExists(File $file)
	{
		// Make sure there is a connection
		if (! $file->isRemote()) {
			echo "Warning: Testing for file existence on local machine rather than a remote one";
			return file_exists($file->getPath());
		}

		$command = '[ -f ' . $file->getPath() . ' ] && echo "1" || echo "0"';
		$ssh = self::connect($file->getHost());
		$output = trim($ssh->exec($command));
		self::checkCmdExceptions($ssh);
		return $output === "1";
	}

	/**
	 * Run this method after executing a command to detect errors
	 * @param \Net_SSH2 $handle
	 * @throws \Cogeco\Build\Exception
	 */
	protected static function checkCmdExceptions(\Net_SSH2 $handle)
	{
		if ($handle->getExitStatus() > 0) {
			throw new Exception("Command failed with exit status ".$handle->getExitStatus()."\n\t" .
				implode("\n\t", $handle->getErrors()));
		}
		else if (count($handle->getErrors())) {
			throw new Exception(__CLASS__ . ": Command failed.\n\t" . implode("\n\t", $handle->getErrors()));
		}
	}
}
