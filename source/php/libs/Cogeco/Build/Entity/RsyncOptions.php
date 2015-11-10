<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Entity\Dir;
use \Cogeco\Build\Utils\Cygwin;


/**
 * Class Rsync
 * @package Cogeco\Build\Entity\Command
 */
class RsyncOptions
{
	/** @var string $chmod */
	public $chmod = 'Du=rwx,Dg=rwx,Do=rx,Fu=rw,Fg=rw,Fo=r';

	/** @var bool $delayUpdates */
	public $delayUpdates= FALSE;

	/** @var bool $delete */
	public $delete = TRUE;

	/** @var bool $deleteAfter */
	public $deleteAfter = FALSE;

	/** @var bool $deleteExcluded */
	public $deleteExcluded = FALSE;

	/** @var bool $dryRun */
	public $dryRun = FALSE;

	/** @var string $flags */
	public $flags = 'vzrltCOp';

	/** @var bool $noMotd */
	public $noMotd = TRUE;

	/** @var bool $progress */
	public $progress = FALSE;

	/** @var bool $safeLinks */
	public $safeLinks = TRUE;

	/** @var bool $stats */
	public $stats = TRUE;

	/** @var \Cogeco\Build\Entity\Dir|\Cogeco\Build\Entity\File $source */
	public $source = '';

	/** @var \Cogeco\Build\Entity\Dir|\Cogeco\Build\Entity\File $destination */
	public $destination = '';

	/** @var bool $sshStrictHostKeyChecking */
	public $sshStrictHostKeyChecking = FALSE;

	/** @var bool $useSsh */
	public $useSsh = TRUE;

	/** @var bool $useHostIdentity */
	public $useHostIdentity = TRUE;

	/** * @var string[] $includes */
	public $includes = array('core/');

	/** @var string[] $excludes */
	public $excludes = array('.*/', '.DS_Store', '.iml');


	/**
	 * @param Dir|File $source
	 * @param Dir|File $destination
	 */
	public function __construct($source, $destination)
	{
		$this->source = $source;

		// If a File is being synced, adjust the includes/excludes accordingly
		if ($this->source instanceof File) {
			$this->excludes = array('*');
			$this->includes = array($this->source->name);
		}

		$this->destination = $destination;
	}

	/**
	 * @return null|Host
	 */
	public function getRemoteHost()
	{
		$sourceHost = $this->source->getHost();
		$destinationHost = $this->destination->getHost();
		if (empty($sourceHost)) {
			if (empty($destinationHost)) {
				return NULL;
			}
			else {
				return $destinationHost;
			}
		}
		return $sourceHost;
	}

	//
	// Methods are provided to help with IDE auto completion and chaining
	//

	/**
	 * @param Dir|File $destination
	 * @return $this
	 */
	public function destination($destination)
	{
		$this->destination = $destination;
		return $this;
	}

	/**
	 * @param Dir|File $source
	 * @return $this
	 */
	public function source($source)
	{
		$this->source = $source;
		return $this;
	}

	/**
	 * Set file permissions. Defaults to 0775 for folders and 0664 for files
	 * @param string $chmod
	 * @return $this
	 */
	public function chmod($chmod = 'Du=rwx,Dg=rwx,Do=rx,Fu=rw,Fg=rw,Fo=r')
	{
		$this->chmod = (string)$chmod;
		return $this;
	}

	/**
	 * @param bool $delayUpdates
	 * @return $this
	 */
	public function delayUpdates($delayUpdates = TRUE)
	{
		$this->delayUpdates = (bool)$delayUpdates;
		return $this;
	}

	/**
	 * @param bool $delete
	 * @return $this
	 */
	public function delete($delete = TRUE)
	{
		$this->delete = (bool)$delete;
		return $this;
	}

	/**
	 * @param bool $deleteAfter
	 * @return $this
	 */
	public function deleteAfter($deleteAfter = TRUE)
	{
		$this->deleteAfter = (bool)$deleteAfter;
		return $this;
	}

	/**
	 * @param bool $deleteExcluded
	 * @return $this
	 */
	public function deleteExcluded($deleteExcluded = TRUE)
	{
		$this->deleteExcluded = (bool)$deleteExcluded;
		return $this;
	}

	/**
	 * @param bool $dryRun
	 * @return $this
	 */
	public function dryRun($dryRun = TRUE)
	{
		$this->dryRun = (bool)$dryRun;
		return $this;
	}

	/**
	 * @param string $flags
	 * @return $this
	 */
	public function flags($flags = 'vzrlOCp')
	{
		$this->flags = (string)$flags;
		return $this;
	}

	/**
	 * @param bool $motd
	 * @return $this
	 */
	public function noMotd($motd = TRUE)
	{
		$this->motd = (bool)$motd;
		return $this;
	}

	/**
	 * @param bool $safeLinks
	 * @return $this
	 */
	public function safeLinks($safeLinks = TRUE)
	{
		$this->safeLinks = (bool)$safeLinks;
		return $this;
	}

	/**
	 * @param bool $progress
	 * @return $this
	 */
	public function progress($progress = TRUE)
	{
		$this->progress = (bool)$progress;
		return $this;
	}

	/**
	 * @param bool $sshStrictHostKeyChecking
	 * @return $this
	 */
	public function sshStrictHostKeyChecking($sshStrictHostKeyChecking = TRUE)
	{
		$this->sshStrictHostKeyChecking = $sshStrictHostKeyChecking;
		return $this;
	}

	/**
	 * @param bool $stats
	 * @return $this
	 */
	public function stats($stats = TRUE)
	{
		$this->stats = (bool)$stats;
		return $this;
	}

	/**
	 * @param bool $useSsh
	 * @return $this
	 */
	public function useSsh($useSsh = TRUE)
	{
		$this->useSsh = (bool)$useSsh;
		return $this;
	}

	/**
	 * @param string $useHostIdentity
	 * @return $this
	 */
	public function useHostIdentity($useHostIdentity = '')
	{
		$this->useHostIdentity = (bool)$useHostIdentity;
		return $this;
	}

	/**
	 * @param string[] $includes
	 * @return $this
	 */
	public  function includes(array $includes)
	{
		$this->includes = $includes;
		return $this;
	}

	/**
	 * @param string[] $includes
	 * @return $this
	 */
	public  function includesAppend(array $includes)
	{
		$this->includes = array_values(array_unique(array_merge($this->includes, $includes)));
		return $this;
	}

	/**
	 * @param string[] $excludes
	 * @return $this
	 */
	public function excludes(array $excludes)
	{
		$this->excludes = $excludes;
		return $this;
	}

	/**
	 * @param string[] $excludes
	 * @return $this
	 */
	public function excludesAppend(array $excludes)
	{
		$this->excludes = array_values(array_unique(array_merge($this->excludes, $excludes)));
		return $this;
	}

	/**
	 * Sets rsync properties using an associative array as input data structure
	 * @param string[] $options The rsync options, where the keys should match class property names
	 */
	public function setOptions(array $options)
	{
		if (isset($options['chmod'])) {
			$this->chmod((string)$options['chmod']);
		}
		if (isset($options['delayUpdates'])) {
			$this->delayUpdates($options['delayUpdates']);
		}
		if (isset($options['delete'])) {
			$this->delete($options['delete']);
		}
		if (isset($options['deleteAfter'])) {
			$this->deleteAfter($options['deleteAfter']);
		}
		if (isset($options['dryRun'])) {
			$this->dryRun($options['dryRun']);
		}
		if (isset($options['flags'])) {
			$this->flags($options['flags']);
		}
		if (isset($options['noMotd'])) {
			$this->noMotd($options['noMotd']);
		}
		if (isset($options['progress'])) {
			$this->progress($options['progress']);
		}
		if (isset($options['safeLinks'])) {
			$this->safeLinks($options['safeLinks']);
		}
		if (isset($options['stats'])) {
			$this->stats($options['stats']);
		}
		if (isset($options['source'])) {
			$this->source($options['source']);
		}
		if (isset($options['destination'])) {
			$this->destination($options['destination']);
		}
		if (isset($options['sshStrictHostKeyChecking'])) {
			$this->sshStrictHostKeyChecking($options['sshStrictHostKeyChecking']);
		}
		if (isset($options['useSsh'])) {
			$this->useSsh($options['useSsh']);
		}
		if (isset($options['useHostIdentity'])) {
			$this->useHostIdentity($options['useHostIdentity']);
		}
		if (isset($options['includes'])) {
			$this->includes($options['includes']);
		}
		if (isset($options['excludes'])) {
			$this->excludes($options['excludes']);
		}
		if (isset($options['includesAppend'])) {
			$this->includesAppend($options['includesAppend']);
		}
		if (isset($options['excludesAppend'])) {
			$this->excludesAppend($options['excludesAppend']);
		}
	}

	/**
	 * @return mixed
	 */
	public function toString()
	{
		return var_export($this);
	}
}