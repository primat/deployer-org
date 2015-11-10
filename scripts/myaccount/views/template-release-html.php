<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>My Account front end released to production - Mon Compte (front end) déploiement en production</title>
  <style type="text/css">
	  body {
		  font-family:arial;
		  font-size:12px;
	  }
	  li {
		  list-style-type:none;
	  }
  </style type="text/css">
  
</head>
<body>
	<p>
		A new version of <a href="https://myaccount.cogeco.ca/acpub/login/">My Account</a> (front end PHP) has been released to production.<br/>
		Here is a list of changes since the previous release:
	</p>

	<p>
		Une nouvelle version de <a href="https://moncompte.cogeco.ca/acpub/login/">Mon Compte</a> (front end PHP) a été deployée en production.<br />
		Voici la liste des changements depuis le dernier déploiement:
	</p>

	<?php echo (isset($changes)) ? $changes : '' ?>
</body>
</html>
