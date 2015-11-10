<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Cogeco.ca released to production - Cogeco.ca déploiement en production</title>
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
		A new version of <a href="http://www.cogeco.ca/web/">Cogeco.ca</a> has been released to production.<br/>
	</p>

	<p>
		Une nouvelle version de <a href="http://www.cogeco.ca/web/">Cogeco.ca</a> a été deployée en production.<br />
	</p>

	<p><strong>Liste des changements : </strong></p>

	<?php
	$count = count($svnEntries);
	if (isset($svnEntries) && $count > 0) {
		foreach ($svnEntries as $revision => $entry) { ?>
			<hr /><br />
			<strong>Révision :</strong> <?php echo $entry->revision ?><br />
			<strong>Auteur :</strong> <?php echo $entry->author ?><br />
			<strong>Date :</strong> <?php echo @ucfirst(strftime("%A le %d %B %Y à %Hh%M", strtotime($entry->date))) ?><br />
			<strong>Message :</strong> <?php echo nl2br(htmlspecialchars($entry->message)) ?>
			<br /><br />
	<?php }
	} else { ?>
		<p>Aucun changement (SVN) depuis le dernier déploiement.</p>
	<?php } ?>

	<p><strong>Déployé par : </strong> <?php echo isset($deployeur) ? htmlspecialchars($deployeur) : ''?></p>

</body>
</html>
