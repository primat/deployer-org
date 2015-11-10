<?php
/**
 * Created by IntelliJ IDEA.
 * User: baush1
 * Date: 12/17/2013
 * Time: 10:16 AM
 */

namespace Cogeco\Build\Entity;

class DeployProperties {

	public $projectName;
	public $projectId;

	// When $sourceSVNRepo = NULL, files won't be updated in sourceFSPath from SVN but taken as is.
	public $sourceSVNRepo = NULL;
	public $sourceSVNURI;
	public $sourceSVNRevision = 0;
	public $sourceSVNExternalRevision = 0;

	public $sourceFSPath;

	public $destinationHost;
	public $destinationPath;
	public $isCopyHtaccess = FALSE;

	public $textEmailTemplatePath = '';
	public $htmlEmailTemplatePath = '';
	public $notificationEmails = array();
	public $smtpServer = 'qc-mail.cogeco.com';

	public function __construct($projectId, $projectName)
	{
		$this->projectId = $projectId;
		$this->projectName = $projectName;

		// set default local path
		$this->sourceFSPath = '../../working_copies/' . $projectId;

		$this->htmlEmailTemplatePath = 'emails/mao-release-html.php';
		$this->textEmailTemplatePath = 'emails/mao-release-text.php';
	}

}


