<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Exception;

/**
 *
 */
class SvnInfo
{
	public $currentRevision;
	public $commitRevision;
	public $commitDate;
	public $commitAuthor;
	public $url;

	public function __construct($xml)
	{
/*
<?xml version="1.0" encoding="UTF-8"?>
<info>
	<entry kind="dir" path="MyAccountFE" revision="18881">
		<url>https://source.cogeco.com/repository/corp/web/trunk/WebMarketing/MyAccountFE</url>
		<repository>
			<root>https://source.cogeco.com/repository/corp/web</root>
			<uuid>8f82aa4d-edf7-ec26-c545-ff29a6b4c01e</uuid>
		</repository>
		<commit revision="18869">
			<author>c_mbeauchemin@COGECO.COM</author>
			<date>2013-11-26T20:05:14.741748Z</date>
		</commit>
	</entry>
</info>
*/
		$xmlObj = @simplexml_load_string($xml);
		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		$flagException = FALSE;
		if (isset($xmlObj->entry['revision'])) {
			$this->currentRevision = (int)$xmlObj->entry['revision'];
		}
		else {
			$flagException = TRUE;
		}

		if (isset($xmlObj->entry->commit['revision'])) {
			$this->commitRevision = (int)$xmlObj->entry->commit['revision'];
		}
		else {
			$flagException = TRUE;
		}

		if (isset($xmlObj->entry->commit->date)) {
			$this->commitDate = (string)$xmlObj->entry->commit->date;
		}
		else {
			$flagException = TRUE;
		}

		if (isset($xmlObj->entry->commit->author)) {
			$this->commitAuthor = (string)$xmlObj->entry->commit->author;
		}
		else {
			$flagException = TRUE;
		}

		if (isset($xmlObj->entry->url)) {
			$this->url = (string)$xmlObj->entry->url;
		}
		else {
			$flagException = TRUE;
		}

		if ($flagException) {
			throw new Exception("Encountered empty information fields in XML:\n" . $xml . "\n");
		}
	}
}
