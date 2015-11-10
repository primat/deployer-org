<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Exception;

/**
 *
 */
class WorkingCopy extends \Cogeco\Build\Entity
{
	/** @var Account $account */
	public $account;
	/** @var Dir $dir */
	public $dir;
	/** @var string $id */
	public $id;
	/** @var string $repoBaseUri */
	protected $repoBaseUri;
	/** @var string $repoBaseUrl */
	public $repoBaseUrl;
	/** @var string $repoPath */
	public $repoPath;
	/** @var string $repoUrl */
	public $repoUrl;
	/** @var SvnInfo $info */
	public $info = NULL;
	/** @var SvnExternal[] $externals */
	public $externals = NULL;


	/**
	 * @param $id
	 * @param $baseUrl
	 * @param $baseUri
	 * @param Account $account
	 */
	public function __construct($id, $baseUrl, $baseUri, Account $account)
	{
		parent::__construct();

		// Set params
		$this->id = $id;
		$this->dir = new Dir(BUILD_WORKING_COPY_DIR . "/{$this->id}/");
		$this->repoBaseUri = rtrim($baseUri, '/');
		$this->repoBaseUrl = rtrim($baseUrl, '/');
		$this->repoUrl = $this->repoBaseUrl . $this->repoBaseUri;
		$this->account = $account;
	}

	/**
	 * @return Dir
	 */
	public function getDir()
	{
		return $this->dir;
	}

	/**
	 * @return string
	 */
	public function getRepoBaseUri()
	{
		return $this->repoBaseUri;
	}

	/**
	 * @return string
	 */
	public function getRepoBaseUrl()
	{
		return $this->repoBaseUrl;
	}

	/**
	 * @return string
	 */
	public function getRepoUrl()
	{
		return $this->repoBaseUrl . $this->repoBaseUri;
	}
}
