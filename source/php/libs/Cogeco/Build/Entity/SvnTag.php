<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Exception;

/**
 *
 */
class SvnTag
{
	public $revision = 0;
	public $copyFromRevision = 0;
	public $copyFromPath ='';
	public $repoBaseUrl ='';
	public $author = '';
	public $date = '';
	public $path = '';

	/**
	 * @param string $xml
	 * @param string $baseUrl
	 * @throws Exception
	 */
	public function __construct($xml, $baseUrl)
	{
/*
<?xml version="1.0" encoding="UTF-8"?>
<log>
	<logentry
			revision="17694">
		<author>mprice@COGECO.COM</author>
		<date>2013-07-24T14:32:40.130589Z</date>
		<paths>
			<path
					kind=""
					action="A">/tags/WebMarketing/MyAccountFE/0-Rel</path>
		</paths>
		<msg>Create release directory</msg>
	</logentry>
	<logentry
			revision="18857">
		<author>mprice@COGECO.COM</author>
		<date>2013-11-26T05:56:45.076863Z</date>
		<paths>
			<path
					kind=""
					copyfrom-path="/trunk/WebMarketing/MyAccountFE"
					copyfrom-rev="18856"
					action="A">/tags/WebMarketing/MyAccountFE/0-Rel/2013/2013-11-26_00-58-52</path>
		</paths>
		<msg>Tagging My Account release to production (test)</msg>
	</logentry>
</log>

*/
		$xmlObj = @simplexml_load_string($xml);
		if (! $xmlObj) {
			throw new Exception("Unable to unmarshall XML:\n" . $xml . "\n");
		}

		$flagException = FALSE;
		if (isset($xmlObj['revision'])) {
			$this->revision = (int)$xmlObj['revision'];
		}
		else {
			$flagException = TRUE;
		}

		if (isset($xmlObj->author)) {
			$this->author = (string)$xmlObj->author;
		}
		else {
			$flagException = TRUE;
		}

		if (isset($xmlObj->date)) {
			$this->date = (string)$xmlObj->date;
		}
		else {
			$flagException = TRUE;
		}

		if (isset($xmlObj->paths) && isset($xmlObj->paths->path)) {



			$this->uri = (string)$xmlObj->paths->path;
			if (isset($xmlObj->paths->path['copyfrom-rev'])) {
				$this->copyFromRevision = (string)$xmlObj->paths->path['copyfrom-rev'];
			}
			if (isset($xmlObj->paths->path['copyfrom-path'])) {
				$this->copyFromPath = (string)$xmlObj->paths->path['copyfrom-path'];
			}
		}

		if ($flagException) {
			throw new Exception("Encountered empty information fields in XML:\n" . $xml . "\n");
		}
	}
}
