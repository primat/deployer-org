<?php namespace Cogeco\Build\Entity\Email;

/**
 * An email connector class used to indicate how to send emails (e.g. smtp, sendmail or PHP's mail() function).
 * This is the SMTP connector
 */
class SmtpConnector extends Connector
{
	public $host = '';
	public $port = 25;
	public $auth = FALSE;

	/**
	 * Constructor
	 * @param string $host
	 * @param int $port
	 */
	public function __construct($host, $port = 25)
	{
		$this->host = $host;
		$this->port = $port;
	}
}
