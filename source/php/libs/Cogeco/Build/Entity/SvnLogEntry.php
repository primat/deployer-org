<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Exception;

/**
 *
 */
class SvnLogEntry
{
	public $revision = 0;
	public $author = '';
	public $date = '';
	public $message ='';

	/**
	 * @param \SimpleXMLElement $xmlObj
	 */
	public function __construct(\SimpleXMLElement $xmlObj)
	{

		//$revision, $author, $date, $message
/*
<?xml version="1.0" encoding="UTF-8"?>
<log>
	<logentry revision="19048">
		<author>c_mbeauchemin@COGECO.COM</author>
		<date>2013-12-06T16:32:40.992780Z</date>
		<msg>Fixed declinedTransaction handling</msg>
	</logentry>
</log>
*/

		$this->revision = (int)$xmlObj['revision'];
		$this->author = (string)$xmlObj->author;
		$this->date = (string)$xmlObj->date;
		$this->message = (string)$xmlObj->msg;
	}
}
