<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Entity;

/**
 *
 */
class Database extends Entity
{
	/** @var string $dbName */
	public $dbName;
	/** @var int $port */
	public $port;
	/** @var Host $host */
	public $host;
	/** @var Account $account */
	public $account;


	/**
	 * @param Account $account
	 * @param string $dbName
	 * @param Host $host
	 * @param int $dbPort
	 */
	public function __construct($dbName, Account $account, Host $host = NULL, $dbPort = 3306)
	{
		parent::__construct();

		$this->dbName = $dbName;
		$this->account = $account;
		$this->host = $host;
		$this->port = $dbPort;
	}

	/**
	 * Get the account used to connect to this database
	 * @return Account
	 */
	public function getAccount()
	{
		return $this->account;
	}

	/**
	 * Get the name of the database
	 * @return string
	 */
	public function getDbName()
	{
		return $this->dbName;
	}

	/**
	 * Get the host where this resource is located
	 * @return Host|null
	 */
	public function getHost()
	{
		return (empty($this->host)) ? NULL : $this->host;
	}

	/**
	 * Get the port used to connect to this database
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * Test if the db is located on a remote server
	 * @return string
	 */
	public function isRemote()
	{
		return ! empty($this->host) && $this->host instanceof Host && $this->host->name !== 'localhost';
	}

	/**
	 * @param \Cogeco\Build\Entity\Account $account
	 */
	public function setAccount($account)
	{
		$this->account = $account;
	}

	/**
	 * @param string $dbName
	 */
	public function setDbName($dbName)
	{
		$this->dbName = $dbName;
	}

	/**
	 * @param \Cogeco\Build\Entity\Host $host
	 */
	public function setHost($host)
	{
		$this->host = $host;
	}

	/**
	 * @param int $port
	 */
	public function setPort($port)
	{
		$this->port = $port;
	}
}