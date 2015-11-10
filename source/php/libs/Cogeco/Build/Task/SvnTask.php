<?php namespace Cogeco\Build\Task;

use \Cogeco\Build\Entity\WorkingCopy;
use \Cogeco\Build\Entity\SvnExternal;
use \Cogeco\Build\Entity\SvnInfo;
use \Cogeco\Build\Entity\SvnLogEntry;
use \Cogeco\Build\Entity\SvnTag;
use \Cogeco\Build\Exception;
use \Cogeco\Build\Exception\WorkingCopyException;
use \Cogeco\Build\Task\FileSystemTask;
use \Cogeco\Build\Config;
use \Cogeco\Build\Task;

/**
 * Class for doing subversion related tasks
 */
class SvnTask extends Task
{
	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @param int $revision
	 * @param bool $ignoreExternals
	 */
	public static function checkout(WorkingCopy $workingCopy, $revision = 0, $ignoreExternals = TRUE)
	{
		$revision = (int)$revision;
		$cmdParams = '';

		// Start preparing the command
		if ($revision > 0) {
			$cmdParams .= "-r $revision ";
		}
		if ($ignoreExternals) {
			$cmdParams .= "--ignore-externals ";
		}

		// Do the checkout
		self::log("- Checking out ");
		if ($revision > 0) {
			self::log("revision {$revision}\n");
		}
		else {
			self::log("head revision\n");
		}
		$cmd = Config::get('svn.bin') . " --force --depth infinity {$cmdParams}checkout " .
			$workingCopy->repoUrl . " " . $workingCopy->dir->getPath() . " " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--config-option config:miscellany:use-commit-times=yes " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		self::runCmd($cmd);
		self::log("\n");
	}

	/**
	 * Check out a clean working copy from SVN
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @param int $revision
	 * @param int $externalsRevision
	 */
	public static function checkoutClean(WorkingCopy $workingCopy, $revision = 0, $externalsRevision = 0)
	{
		$rev = ($revision === 0) ? 'HEAD' : $revision;
		$extRev = ($externalsRevision === 0) ? 'HEAD' : $rev;
		self::log("-- Getting a clean checkout at ");
		if ($revision > 0) {
			self::log("revision {$revision}");
		}
		else {
			self::log("head revision");
		}
		if ($externalsRevision > 0 ) {
			self::log(" with externals at revision $externalsRevision");
		}
		self::log("\n\n");

		// Get the repo information to validate the requested revision numbers to checkout
		$svnRemoteInfo = self::getRepoInfo($workingCopy, $revision);
		$realRevision = $svnRemoteInfo->commitRevision;
		$realExtRevision = $externalsRevision;

		// Correct the requested revision number to the last commit one from the repo
		if ($revision !== $realRevision) {
			self::log("Check out last commit revision {$realRevision}\n\n");
		}

		// Determine the real externals revision number as well
		if ($externalsRevision === $revision) {
			$realExtRevision = $realRevision;
		}
		else {
			$svnRemoteExtInfo = self::getRepoInfo($workingCopy, $externalsRevision);
			// Correct the requested revision number to the last commit one from the repo
			if ($externalsRevision !== $svnRemoteExtInfo->commitRevision) {
				self::log("Checking out last (externals) commit revision {$svnRemoteExtInfo->commitRevision}\n\n");
				$realExtRevision = $svnRemoteExtInfo->commitRevision;
			}
		}

		// Get local svn info
		if (empty($workingCopy->info)) {
			try {
				$workingCopy->info = SvnTask::getInfo($workingCopy);
			}
			catch (Exception $e) {
				$workingCopy->info = NULL; // Indicates that the working copy is not checked out yet
			}
		}

		// The Working copy and remote repo do not share the same URL.
		// We must delete the working copy and do a fresh checkout
		if (! empty($workingCopy->info) && $workingCopy->info->url !== $svnRemoteInfo->url) {
			self::log("- Emptying the working copy folder\n\n");
			FileSystemTask::rrmdir($workingCopy->dir->getPath(), FALSE);
			$workingCopy->info = NULL;
		}

		if ($workingCopy->info === NULL) {
			self::log("- Checkout a fresh copy\n\n");
			self::checkout($workingCopy, $realRevision);
			$workingCopy->info = SvnTask::getInfo($workingCopy);
		}
		else {
			self::cleanUp($workingCopy);
		}

		// Do a bit of cleanup
		self::revert($workingCopy);
		self::purgeIgnoredAndUnversioned($workingCopy);

		// Set the externals and update the info object
		if (self::setExternalsToRevision($workingCopy, $realExtRevision)) {
			self::update($workingCopy, $realRevision);
			$workingCopy->info = SvnTask::getInfo($workingCopy);
		}
	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @param string $commitMessage
	 */
	public static function commit(WorkingCopy $workingCopy, $commitMessage = '')
	{
		self::log("- Committing {$workingCopy->dir->getPath()} to {$workingCopy->repoUrl}\n");
		$cmd = Config::get('svn.bin') . " commit " . $workingCopy->dir->getPath() . " " .
			'-m "' . $commitMessage . '" ' .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		self::runCmd($cmd);
		self::log("\n");
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 */
	public static function checkoutTag(WorkingCopy $workingCopy)
	{
		// Checkout the tag to a temporary location
		if (is_dir($workingCopy->dir->getPath())) {
			FileSystemTask::rrmdir($workingCopy->dir->getPath(), FALSE);
		}
		else {
			mkdir($workingCopy->dir->getPath(), 0775);
		}

		// Do the check out
		self::log("- Checking out {$workingCopy->repoUrl}\n");
		$cmd = Config::get('svn.bin') . " --force --depth infinity checkout " .
			$workingCopy->repoUrl . " " . $workingCopy->dir->getPath() . " " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--config-option config:miscellany:use-commit-times=yes " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		self::runCmd($cmd);
		self::log("\n");
	}

	/**
	 * Cleans up a working copy (directory)
	 * @param $workingCopy
	 */
	public static function cleanUp($workingCopy)
	{
		self::log("- Running svn cleanup\n");
		$cmd = Config::get('svn.bin') . " cleanup {$workingCopy->dir->getPath()} 2>&1";
		self::runCmd($cmd);
		self::log("\n");
	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @param string $revisionFilePath
	 * @param bool $fullTimestamp
	 * @throws \Cogeco\Build\Exception
	 */
	public static function createManifestFile(WorkingCopy $workingCopy, $revisionFilePath = '', $fullTimestamp = FALSE)
	{
		if (empty($revisionFilePath)) {
			$revisionFilePath = $workingCopy->dir->getPath() . 'manifest';
		}
		// Create the file
		self::log("- Creating manifest file\n");

		$revision = self::getRevision($workingCopy);

		if ($fullTimestamp) {
			$extraSegment = '-' . date('Hi');
		}
		else {
			$extraSegment = '-' . $revision;
		}

		if (file_put_contents($revisionFilePath, date("Ymd") . $extraSegment) === FALSE) {
			throw new Exception("Unable to create manifest file");
		}

		self::log("Created file {$revisionFilePath} for revision {$revision}\n\n");
	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @return int
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getBranchHeadRevision(WorkingCopy $workingCopy)
	{
		$cmd = Config::get('svn.bin') . " log {$workingCopy->repoUrl}" .
			" --username {$workingCopy->account->username}" .
			" --password {$workingCopy->account->password}" .
			" --xml --stop-on-copy --limit 1 --non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);

		// Parse the XML
		$xmlObj = @simplexml_load_string($xml);

		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		if (count($xmlObj->logentry) > 0) {
			$attributes = $xmlObj->logentry->attributes();
			if (! empty($attributes['revision'])) {
				return (int)$attributes['revision'];
			}
		}

		throw new Exception("Unable to get the last revision number for this branch:\n" . $xml . "\n");
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @return mixed
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getExternals(WorkingCopy $workingCopy)
	{
		$cmd = Config::get('svn.bin') . " propget -R svn:externals {$workingCopy->dir->getPath()} --xml 2>&1";
		$xml = self::runCmd($cmd, FALSE);

		$xmlObj = @simplexml_load_string($xml);
		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		$workingCopy->externals = array();
		foreach($xmlObj->target as $target) {
			$workingCopy->externals = array_merge($workingCopy->externals, self::parseExternalsNode($target));
		}

		return $workingCopy->externals;
	}

	/**
	 * @deprecated
	 * @param WorkingCopy $workingCopy
	 * @return array
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getExternalsArray(WorkingCopy $workingCopy)
	{
		self::log("- Getting list of externals\n");
		$result = array();
		$cmd = Config::get('svn.bin') . " propget -R svn:externals " .
			"{$workingCopy->dir->getPath()} " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		exec($cmd, $output, $err);
		if ($err !== 0) {
			throw new Exception("SVN get externals failed.\n\t{$cmd}");
		}
		foreach($output as $index => $line) {
			if (! empty($line)) {
				if ($index === 0) {
					$tmpParts = explode('- ', $line, 2);
					if (count($tmpParts) !== 2) {
						throw new Exception("SVN parse externals failed:\n\t".implode('\n\t', $output)."\n");
					}
					$line = $tmpParts[1];
				}
				$tmpParts = explode(' ', $line);
				$result[$index]['url'] = explode('@', $tmpParts[0], 2);
				$partCnt = count($result[$index]['url']);
				if ($partCnt === 2) {
					$result[$index]['revision'] = $result[$index]['url'][1];
					$result[$index]['url'] = $result[$index]['url'][0];
				}
				else {
					$result[$index]['url'] = $result[$index]['url'][0];
				}
				$result[$index]['path'] = $tmpParts[1];
			}
		}
		self::log("\n");
		return $result;
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @return int
	 */
	public static function getHeadRevision(WorkingCopy $workingCopy)
	{
		$cmd = Config::get('svn.bin') . " info {$workingCopy->repoUrl} --xml " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);
		$svnInfo = new SvnInfo($xml);
		return $svnInfo->currentRevision;
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @return \Cogeco\Build\Entity\SvnInfo
	 */
	public static function getInfo(WorkingCopy $workingCopy)
	{
		$cmd = Config::get('svn.bin') . " info {$workingCopy->dir->getPath()} --xml 2>&1";
		$xml = self::runCmd($cmd, FALSE);
		return new SvnInfo($xml);
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @param int $limit
	 * @return array
	 */
	public static function getLastTagUrls(WorkingCopy $workingCopy, $limit = 5)
	{
		$repoUrl = rtrim($workingCopy->repoUrl, '/'); // The base repo url
		$urls = array(); // The urls to be returned

		// Start by getting the first level of folder names - each name corresponds to the year of the release so they
		// must be sorted to get the latest ones
		self::log("- Getting the latest tags\n");

		$cmd = Config::get('svn.bin') . " list {$workingCopy->repoUrl} " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$output = self::runCmd($cmd, FALSE);

		// Parse the svn text into an array of folder names and sort them descending
		$segments = self::getFolderSegmentsFromString($output);
		rsort($segments);

		$counter = 0;
		foreach($segments as $i => $segment) {
			$cmd = Config::get('svn.bin') . " list {$workingCopy->repoUrl}/{$segment} " .
				"--username {$workingCopy->account->username} " .
				"--password {$workingCopy->account->password} " .
				"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
			$output = self::runCmd($cmd, FALSE);

			// Parse the second level of relative folder names and sort
			$segments2 = self::getFolderSegmentsFromString($output);
			rsort($segments2);

			foreach($segments2 as $j => $segment2) {
				$urls[] = $repoUrl . '/' . $segment . '/' . $segment2 . '/';
				$counter++;
				if ($counter === $limit) {
					break;
				}
			}
			if ($counter === $limit) {
				break;
			}
		}

		self::log("\n");
		return $urls;
	}

	/**
	 * @param $repoUrl
	 * @param int $qty
	 */
//	public static function getLastCommits($repoUrl, $qty = 5)
//	{
//		$result = array();
//		$cmd = Config::get('svn.bin') . " log {$repoUrl} --limit {$qty} 2>&1";
//		$output = self::runCmd($cmd, FALSE);
//		$output = trim($output, '-');
//		$output = trim($output, "\n");
//		$output = explode("------------------------------------------------------------------------", $output);
//
//		file_put_contents('temp.txt', implode("\n", $output));
//
//return;
//		print_r($output);
//		foreach($output as $index => $commitData) {
//
//			$commitData = trim($commitData, "\n");
//			//$tmpData = $commitData;
//			$tmpData = explode("\n\n", $commitData, 2);
//			foreach($tmpData as $j => $tmpData2) {
//
//				$tmpData2 = trim($tmpData2, "\n");
//
//				//$tmpData[$j] = trim($tmpData2, "\n");
//
//				//$text = preg_replace("/[\r\n]+/", "\n", $text);
//
//
//				$tmpData[$j] = explode("\n", $tmpData2, 2);
//				print_r($tmpData[$j]);
//			}
////			$tmpData[0] = explode(" | ", $tmpData[0], 4);
////			$result[] = array(
////				'revision' => ltrim($tmpData[0][0], 'r'),
////				'user' => $tmpData[0][1],
////				'timestamp' => $tmpData[0][2],
////				'message' => $tmpData[1]
////			);
//			//echo $commitData;
//		}
//
//		//print_r($result);
//	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @return int
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getLastTagRevision(WorkingCopy $workingCopy)
	{
		$revision = 0;
		$tag = NULL;
		$cmd = Config::get('svn.bin') . " log {$workingCopy->repoUrl} " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			" --xml -v --stop-on-copy --limit 40 --non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);

		// Parse the XML
		$xmlObj = @simplexml_load_string($xml);
		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		/* @var $tagEntry \SimpleXmlElement */
		$deletedPaths = array();
		$tagEntry = NULL;
		foreach($xmlObj->logentry as $index => $entry) { /* @var $entry \SimpleXmlElement */

			if (! $entry->paths || ! $entry->paths->path) {
				continue;
			}
			foreach($entry->paths->path as $j => $path) { /* @var $path \SimpleXmlElement */

				if (empty($path['action'])
					|| !preg_match("/_\d\d\-\d\d\-\d\d$/", (string)$path)
					|| isset($deletedPaths[(string)$path])) {
					continue;
				}

				if ($path['action'] == 'D') {
					$deletedPaths[(string)$path] = true;
					continue;
				}

				if ($path['action'] == 'A' && (int)$path['copyfrom-rev'] > 0) {
					$revision = (int)$path['copyfrom-rev'];
					break;
				}
			}
			if ($revision > 0) {
				break;
			}
		}

//		if ($revision === 0) {
//			throw new Exception('No tags found in subversion');
//		}

		return $revision;
	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @param string $commitText
	 * @return int
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getLastDeployRevision(WorkingCopy $workingCopy, $commitText="Release to Production")
	{
		$logLimit = 10;
		$revision = -1;
		$tag = NULL;
		$cmd = Config::get('svn.bin') . " log {$workingCopy->repoUrl}" .
			" --username {$workingCopy->account->username}" .
			" --password {$workingCopy->account->password}" .
			" --xml --stop-on-copy --limit " . $logLimit .
			" --non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);

		// Parse the XML
		$xmlObj = @simplexml_load_string($xml);

		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		/* @var $tagEntry \SimpleXmlElement */
		foreach($xmlObj->logentry as $index => $entry) { /* @var $entry \SimpleXmlElement */
			if (stripos($entry->msg, $commitText) !== FALSE) {
				$attributes = $entry->attributes();
				if (! empty($attributes['revision'])) {
					$revision = (int)$attributes['revision'];
				}
				break;
			}
		}
		return $revision;
	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @param int $maxEntries
	 * @return array
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getLatestLogEntries(WorkingCopy $workingCopy, $maxEntries = 40)
	{
		$entries = array();
		$cmd = Config::get('svn.bin') . " log {$workingCopy->repoUrl}" .
			" --username {$workingCopy->account->username}" .
			" --password {$workingCopy->account->password}" .
			" --limit $maxEntries" .
			" --xml --non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);

		// Parse the XML
		$xmlObj = @simplexml_load_string($xml);
		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		foreach($xmlObj->logentry as $index => $entry) {
			/* @var $entry \SimpleXmlElement */
			if (isset($entry['revision'])) {
				$entries[(int)$entry['revision']] = new SvnLogEntry($entry);
			}
		}
		return $entries;
	}


	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @param $fromRevision
	 * @param $toRevision
	 * @return array
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getLogEntries(WorkingCopy $workingCopy, $fromRevision, $toRevision)
	{
		$entries = array();
		$cmd = Config::get('svn.bin') . " log -r{$fromRevision}:{$toRevision} {$workingCopy->repoUrl} " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			" --xml --non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);

		// Parse the XML
		$xmlObj = @simplexml_load_string($xml);
		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		foreach($xmlObj->logentry as $index => $entry) {
			/* @var $entry \SimpleXmlElement */
			if (isset($entry['revision'])) {
				$entries[(int)$entry['revision']] = new SvnLogEntry($entry);
			}
		}
		krsort($entries); // Sort descending
		return $entries;
	}

	/**
	 * Get an object representing information for a repository URL
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @param int $revision
	 * @return \Cogeco\Build\Entity\SvnInfo
	 */
	public static function getRepoInfo(WorkingCopy $workingCopy, $revision = 0)
	{
		$revisionParam = ($revision > 0) ? "@{$revision}" : '';
		$cmd = Config::get('svn.bin') . " info {$workingCopy->repoUrl}{$revisionParam} " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--non-interactive --trust-server-cert --xml --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);
		return new SvnInfo($xml);
	}

	/**
	 *
	 */
	public static function getRevision(WorkingCopy $workingCopy)
	{
		if (! isset($workingCopy->info->commitRevision)) {
			$workingCopy->info = self::getInfo($workingCopy);
		}
		return $workingCopy->info->commitRevision;
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @return array
	 * @throws \Cogeco\Build\Exception
	 */
	public static function getTags(WorkingCopy $workingCopy)
	{
		$tags = array();
		$cmd = Config::get('svn.bin') . " log {$workingCopy->repoUrl} " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			" --xml -v --stop-on-copy --non-interactive --trust-server-cert --no-auth-cache 2>&1";
		$xml = self::runCmd($cmd, FALSE);

		// Parse the XML
		$xmlObj = @simplexml_load_string($xml);
		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		foreach($xmlObj->logentry as $index => $entry) {
			/* @var $entry \SimpleXmlElement */
			$tmpObj = new SvnTag($entry->asXML(), $workingCopy->repoBaseUrl);
			if ($tmpObj->copyFromRevision > 0 && ! empty($tmpObj->copyFromPath)) {
				$tags[$tmpObj->copyFromRevision] = $tmpObj;
			}
		}

		// Filter and sort the tags
		krsort($tags);

		return $tags;
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @return bool
	 */
	public static function isCheckedOut(WorkingCopy $workingCopy)
	{
		if ($workingCopy->info === NULL) {
			try {
				self::loadInfo($workingCopy);
			}
			catch (WorkingCopyException $e) { }
		}
		return $workingCopy->info !== NULL;
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @throws \Cogeco\Build\Exception\WorkingCopyException
	 */
	public static function loadInfo(WorkingCopy $workingCopy)
	{
		if ($workingCopy->info === NULL) {
			$cmd = Config::get('svn.bin') . " info {$workingCopy->dir->getPath()} " .
				"--username {$workingCopy->account->username} " .
				"--password {$workingCopy->account->password} " .
				"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
			exec($cmd, $output, $err);
			if ($err) {
				$outputString = implode("\n\t", $output);
				preg_match('/svn: [A-Z](\d\d\d\d\d\d)/', $outputString, $matches);
				$code = 0;
				if (! empty($matches[1])) {
					$code = (int)$matches[1];
				}
				throw new WorkingCopyException("Unable to get working copy information. Command failed with exit status $err.\n\t" . $outputString, $code);
			}
			foreach($output as $i => $line) {
				$line = trim($line);
				if (! empty($line)) {
					if (! is_array($workingCopy->info)) {
						$workingCopy->info = array();
					}
					$parts = explode(': ', $line, 2);
					if (count($parts) === 2) {
						$key = str_replace(' ', '_', strtolower($parts[0]));
						$workingCopy->info[$key] = $parts[1];
					}
				}
			}
		}
	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @throws Exception
	 */
	public static function purgeIgnoredAndUnversioned($workingCopy)
	{
		// Remove unversioned and ignored files/folders
		self::log("- Removing ignored and unversioned files and folders\n");
		$cmd = Config::get('svn.bin') . " status --no-ignore " .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--non-interactive --trust-server-cert --no-auth-cache {$workingCopy->dir->getPath()} 2>&1";
		exec($cmd, $output, $err);
		if ($err !== 0) {
			throw new Exception("SVN deletion of ignored and unversioned files failed:\n\t".implode("\n\t", $output)."\n");
		}
		foreach($output as $lineNumber => $line) {
			if (preg_match('/^(I|\?)\s{7}/', $line) === 1) {
				$file = mb_substr($line, 8);
				self::log("Deleting {$file}\n");
				if (file_exists($file)){
					if (is_dir($file)) {
						FileSystemTask::rrmdir($file);
					}
					else {
						unlink($file);
					}
				}
			}
		}
		self::log("\n");
	}

	/**
	 * @param WorkingCopy $workingCopy
	 * @throws Exception
	 */
	public static function revert($workingCopy)
	{
		$command = Config::get('svn.bin') . " revert -R {$workingCopy->dir->getPath()} 2>&1";
		self::log("- Reverting changes\n");
		self::runCmd($command);
		self::log("\n");
	}

	/**
	 * @param $path
	 * @param $externalsText
	 * @throws \Cogeco\Build\Exception
	 */
	public static function setExternals($path, $externalsText)
	{
		$tmpFilePath = BUILD_TMP_DIR . '/tmp-svn-externals.txt';
		if (file_put_contents($tmpFilePath, $externalsText) === FALSE) {
			throw new Exception('Could not create temporary file for svn externals');
		}
		$cmd = Config::get('svn.bin') . " propset svn:externals -F {$tmpFilePath} {$path} 2>&1";
		self::runCmd($cmd);
		unlink($tmpFilePath);
	}

	/**
	 * @deprecated
	 * @param $workingCopy
	 * @param int $revisionNbr
	 * @return bool
	 */
	public static function setExternalsRevision($workingCopy, $revisionNbr = 0)
	{
		$revisionNbr = (int)$revisionNbr;
		$revisionNbrStr = ($revisionNbr === 0) ? '' : '@'.(string)$revisionNbr;

		self::log("- Setting externals to ");
		if ($revisionNbr === 0) {
			self::log("head revision\n");
		}
		else {
			self::log("revision {$revisionNbr}\n");
		}

		self::$muteOutput = TRUE;
		$externals = self::getExternalsArray($workingCopy);
		self::$muteOutput = FALSE;
		if (count($externals) === 0) {
			self::log("No externals to set!\n\n");
			return FALSE;
		}

		$fileContents = '';
		foreach($externals as $index => $external) {
			$fileContents .= $external['url'] . $revisionNbrStr . ' ' . $external['path'] . "\n";
		}

		$tmpFilePath = $workingCopy->dir->getPath() . '/tmp-svn-externals.txt';
		file_put_contents($tmpFilePath, $fileContents);
		$cmd = Config::get('svn.bin') . " propset svn:externals -F {$tmpFilePath} {$workingCopy->dir->getPath()} 2>&1";

		self::runCmd($cmd);
		unlink($tmpFilePath);
		self::log("\n");
		return TRUE;
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $workingCopy
	 * @param string $revision
	 * @return bool
	 */
	public static function setExternalsToRevision(WorkingCopy $workingCopy, $revision = '')
	{
		$exts = array();

		self::log("- Setting externals to ");
		if ($revision === '') {
			self::log("head revision\n");
		}
		else {
			self::log("revision {$revision}\n");
		}

		// Get the externals when they've not been loaded yet
		if ($workingCopy->externals == NULL) {
			self::getExternals($workingCopy);
		}

		if ($revision !== '') {
			$revision = '@' . $revision;
		}

		// Make a new data structure for the externals which will be easier to use when setting them
		// Specifically, place them in tan array wher the index is the path to the properties file and the keys are
		// the extrnals that are set for that path
		/** @var $external SvnExternal */
		foreach($workingCopy->externals as $index => $external) {
			if (! isset($exts[$external->basePath])) {
				$exts[$external->basePath] = '';
			}
			// Append th external info to a string which cabn easily be added to a SVN property file
			$exts[$external->basePath] .= "{$external->url}{$revision} {$external->relPath}\n";
		}

		foreach($exts as $path => $externals) {
			self::setExternals($path, $externals);
		}
		self::log("\n");
		return TRUE;
	}

	/**
	 * @param \Cogeco\Build\Entity\WorkingCopy $tag
	 * @param string $workingCopyPath
	 * @param $message
	 */
	public static function tagRelease(WorkingCopy $tag, $workingCopyPath, $message)
	{
		$message = addslashes($message);
		self::log("-- Tagging working copy\n");
		$command = Config::get('svn.bin') . " copy {$workingCopyPath} " .
			"{$tag->repoUrl}/" . date('Y-m-d_H-i-s') . "/ " .
			"-m \"{$message}\" " .
			"--username {$tag->account->username} " .
			"--password {$tag->account->password} " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		self::runCmd($command);
		self::log("\n");
	}

	/**
	 * @param $workingCopy
	 * @param int $revision
	 * @throws \Cogeco\Build\Exception
	 */
	public static function update($workingCopy, $revision = 0)
	{
		$revision = (int)$revision;
		$revisionToUpdate = '';
		if ($revision > 0) {
			$revisionToUpdate = "-r$revision ";
		}
		self::log("- Updating to ");
		if ($revision > 0) {
			self::log("revision {$revision}\n");
		}
		else {
			self::log("head revision\n");
		}
		$cmd = Config::get('svn.bin') . " update {$workingCopy->dir->getPath()} {$revisionToUpdate}" .
			"--username {$workingCopy->account->username} " .
			"--password {$workingCopy->account->password} " .
			"--config-option config:miscellany:use-commit-times=yes " .
			"--non-interactive --trust-server-cert --no-auth-cache 2>&1";
		self::runCmd($cmd);
		self::log("\n");
	}

	/**
	 * Parse the result of a SVN command to extract a list of folder segments based on a call to svn list
	 * @param $string
	 * @return array
	 */
	protected static function getFolderSegmentsFromString($string)
	{
		$arr = explode("\n", $string);
		$result = array();
		foreach($arr as $i => $str) {
			$str = trim($str);
			if (mb_substr($str, -1, 1) === '/') {
				$result[] = trim($str, '/');
			}
		}
		return $result;
	}

	/**
	 * Parses an xml node coming from svn propget svn:externals
	 * @param $node
	 * @return array
	 */
	protected static function parseExternalsNode($node)
	{
		$externals = array();
		if (empty($node['path']) || empty($node->property)) {
			return $externals;
		}
		$text = trim((string)$node->property);
		$lines = explode("\n", $text);
		foreach($lines as $line) {
			$path = '';
			$revision = '';
			$parts = explode(" ", $line);
			if (! empty($parts[1])) {
				$path = $parts[1];
			}
			$parts = explode("@", $parts[0], 2);
			if (! empty($parts[1])) {
				$revision = (int)$parts[1];
			}
			$url = $parts[0];
			$externals[] = new SvnExternal((string)$node['path'], $path, $url, $revision);
		}
		return $externals;
	}
}
