<?php namespace Cogeco\Build\Entity;

/**
 * A class for storing Email related data such as a list of recipients or reply-to address, for example
 */
class Email
{
	public $fromAddress = '';
	public $fromName = '';
	public $replyAddress = '';
	public $replyName = '';
	public $to = array();
	public $subject = '';
	public $bodyText = '';
	public $bodyHtml = '';
	public $attachments = array();
	public $encoding = 'UTF-8';

	/**
	 * @param $from
	 * @param $to
	 * @param $subject
	 */
	public function __construct($from, $to, $subject)
	{
		if (isset($from[0]) && isset($from[1])) {
			$this->fromAddress = $from[0];
			$this->fromName = $from[1];
		}
		else {
			$this->fromAddress = $from;
		}
		$this->to = $to;
		$this->subject = $subject;
	}
}
