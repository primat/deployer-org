<?php namespace Cogeco\Build\Entity;
/**
 *
 */
class RemoteHost
{
	public $username;
	public $password;
	public $hostname;

	/**
	 * @param $account
	 * @param $hostname
	 */
	public function __construct($hostname, Account $account)
	{
		$this->username = $account->username;
		$this->password = $account->password;
		$this->hostname = $hostname;
	}
}
