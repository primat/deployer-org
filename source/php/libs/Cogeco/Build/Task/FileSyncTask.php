<?php namespace Cogeco\Build\Task;

use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Exception;
use \Cogeco\Build\Utils\Cygwin;
use \Cogeco\Build\Config;
use \Cogeco\Build\Entity\RsyncOptions;

/**
 * Task for synchronizing files from local to remote, local to local but not remote to remote (use SshTask for that)
 */
class FileSyncTask extends Task
{
	/**
	 * Take a set of rsync options, build the command then run it
	 * @param RsyncOptions $rsync
	 */
	public static function sync(RsyncOptions $rsync)
	{
		$remoteHost = $rsync->getRemoteHost();

		if (empty($rsync->identityFilePath) && ! IS_CLI) {
			// No CLI and no identity means one must be generated temporarily
			SshTask::GenerateTemporaryKeyPair($remoteHost);
		}

		$command = self::getRsyncCommand($rsync);

		// Adjust the command if we are using expect
		if (IS_CLI && ! empty($remoteHost)) { // IS_CLI &&
			$cmdTemplate = self::getExpectCommandTemplate();
			$command = sprintf($cmdTemplate, addslashes($command), addslashes($remoteHost->account->password));
		}
		else {
			self::log($command . "\n\n");
		}

		self::runCmd($command);
		self::log("\n\n");
	}

	/**
	 *
	 */
	public static function getRsyncCommand(RsyncOptions $rsync)
	{
		$cmd = Config::get('rsync.bin');

		if (! empty($rsync->flags)) {
			$cmd .= ' -' . $rsync->flags;
		}

		if ($rsync->deleteAfter) {
			$cmd .= ' --delete-after';
		}

		if ($rsync->deleteExcluded) {
			$cmd .= ' --delete-excluded';
		}

		if ($rsync->delete) {
			$cmd .= ' --delete';
		}

		if ($rsync->delayUpdates) {
			$cmd .= ' --delay-updates';
		}

		if ($rsync->dryRun) {
			$cmd .= ' --dry-run';
		}

		if ($rsync->noMotd) {
			$cmd .= ' --no-motd';
		}

		if ($rsync->stats) {
			$cmd .= ' --stats';
		}

		if ($rsync->progress) {
			$cmd .= ' --progress';
		}

		if ($rsync->safeLinks) {
			$cmd .= ' --safe-links';
		}

		if (! empty($rsync->chmod)) {
			$cmd .= ' --chmod=' . $rsync->chmod;
		}

		foreach($rsync->includes as $include) {
			$cmd .= ' --include "' . $include . '"';
		}

		foreach($rsync->excludes as $exclude) {
			$cmd .= ' --exclude "' . $exclude . '"';
		}

		$remoteHost = $rsync->getRemoteHost();

		if ($rsync->useSsh && ! empty($remoteHost)) {

			$cmd .= ' -e "ssh -o ConnectTimeout=5';
			$identity = '';
			if ($rsync->useHostIdentity && ! empty($remoteHost->privateKeyPath)) {
				// No CLI and no identity means one must be generated temporarily
				$cmd .= ' -i ' . Cygwin::cygPath($remoteHost->privateKeyPath);
			}
			if (! $rsync->sshStrictHostKeyChecking) {
				$cmd .= ' -o StrictHostKeyChecking=no';
			}
			$cmd .= '"';
		}

		// Continue building the command with the source arg
		$cmd .= ' ';
		if ($rsync->source->isRemote() && $rsync->useSsh) {
			$cmd .= "{$rsync->source->getHost()->account->username}@{$rsync->source->getHost()->hostname}:";
		}
		$cmd .= Cygwin::cygPath($rsync->source->getPath());

		// Continue building the command with the destination arg
		$cmd .= ' ';
		if ($rsync->destination->isRemote() && $rsync->useSsh) {
			$cmd .= "{$rsync->destination->getHost()->account->username}@{$rsync->destination->getHost()->hostname}:";
		}
		$cmd .= Cygwin::cygPath($rsync->destination->getPath());

		return $cmd;
	}

	/**
	 * Gets the Expect command tempalte the rsyn will run in to automate the interactive SSH password
	 * @return string
	 */
	private static function getExpectCommandTemplate()
	{
		return Config::get('expect.bin') . ' ' . Cygwin::cygPath(BUILD_ROOT_DIR) . '/source/expect/pass.exp "%s" "%s"';
	}
}
