<?php namespace Cogeco\Build\Task;

use \Cogeco\Build\Entity\Email;
use \Cogeco\Build\Entity\Email\Connector;
use \Cogeco\Build\Entity\Email\SmtpConnector;
use \Cogeco\Build\Exception;
use \Cogeco\Build\Task;

require_once BUILD_ROOT_DIR . '/vendor/autoload.php';

/**
 * The email task takes care of sending emails
 */
class EmailTask extends Task
{
	/**
	 * A basic PHP mail() wrapper
	 * @param $sendToList
	 * @param $subject
	 * @param $body
	 * @param array $headers
	 * @throws \Cogeco\Build\Exception
	 */
	public function send($sendToList, $subject, $body, $headers = array())
	{
		echo "-- Sending email notifications...\n";
		$headers =  implode("\r\n", $headers). "\r\n";
		$result = mail($sendToList, $subject, $body, $headers);
		if (! $result) {
			throw new Exception('Email notifications failed');
		}
		echo "-- Email notifications sent successfully\n";
	}

	/**
	 * Send an email using PHPMailer
	 * @param \Cogeco\Build\Entity\Email\Connector $connector
	 * @param \Cogeco\Build\Entity\Email $emailData
	 * @throws \Cogeco\Build\Exception
	 */
	public static function sendEmail(Connector $connector, Email $emailData)
	{
		$multipleRecipients = (count($emailData->to) > 1);
		Task::log("- Sending email {$emailData->subject}\n");

		$mail = new \PHPMailer();
		if ($connector instanceof SmtpConnector) {
			//Tell PHPMailer to use SMTP
			$mail->isSMTP();
			//Enable SMTP debugging
			// 0 = off (for production use)
			// 1 = client messages
			// 2 = client and server messages
			$mail->SMTPDebug = 0;
			//Ask for HTML-friendly debug output
			$mail->Debugoutput = 'html';
			//Set the hostname of the mail server
			$mail->Host = $connector->host;
			//Set the SMTP port number - likely to be 25, 465 or 587
			$mail->Port = $connector->port;
			//Whether to use SMTP authentication
			$mail->SMTPAuth = false;
			// SMTP auth is currently unsupported
			// if ($connector->auth) { }
		}
		else {
			throw new Exception("EmailTask error: Unsupported email connector");
		}

		// Set the FRom address and name
		$mail->setFrom($emailData->fromAddress, $emailData->fromName);

		//Set a reply-to address, if there is one
		if (! empty($emailData->replyAddress)) {
			$mail->addReplyTo($emailData->replyAddress, $emailData->replyName);
		}

		foreach($emailData->to as $i => $toAddress) {
			$mail->addAddress($toAddress);
		}

		// Set the subject, HTML body and text
		$mail->Subject = $emailData->subject;
		$mail->msgHTML($emailData->bodyHtml);
		$mail->AltBody = $emailData->bodyText;

		// Set attachments
		if (! empty($emailData->attachments)) {
			foreach($emailData->attachments as $i => $attachment) {
				$mail->addAttachment($attachment);
			}
		}

		$mail->CharSet = $emailData->encoding;

		// Send the message, check for errors
		if ($mail->send()) {
			Task::log("Email notification" . ($multipleRecipients ? 's' : '') . " sent\n\n");
		}
		else {
			throw new Exception("Mailer Error: " . $mail->ErrorInfo);
		}
	}

	/**
	 * Method for creating emails as files and storing them in a sub folder of the currently running script
	 * @param $fileBaseName
	 * @param $html
	 * @param $text
	 * @throws \Exception
	 */
	public static function createEmailFiles($fileBaseName, $html, $text)
	{
		if (! is_dir(BUILD_EMAILS_DIR)) {
			mkdir(BUILD_EMAILS_DIR);
		}
		if (! is_dir(BUILD_EMAILS_DIR)) {
			throw new \Exception('Unable to create folder for storing email files');
		}

		$basePath = BUILD_EMAILS_DIR . '/' . $fileBaseName;

		// Store the email contents in files
		if (! file_put_contents($basePath . '.txt', $text)) {
			throw new \Exception('Unable to create email text file');
		}
		if (! file_put_contents($basePath . '.html', $html)) {
			throw new \Exception('Unable to create email HTML file');
		}
	}
}
