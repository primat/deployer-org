<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Demande de déploiement www.cogeco.ca</title>
<style type="text/css">
	body {
		font-family:arial;
		font-size:12px;
	}
	li {
		list-style-type:none;
	}

	table {
		border-top: 1px solid #5e5e5e;
		border-left: 1px solid #5e5e5e;
		border-right: 1px solid #5e5e5e;
		vertical-align: top;
	}

	th {
		text-align:left;
		border-bottom: 1px solid #5e5e5e;
		padding: 5px;
		border-right: 1px solid #5e5e5e;
		width:15%;
		vertical-align: top;
	}
	td {
		border-bottom: 1px solid #5e5e5e;
		padding: 5px;
		width:85%;
	}
	p {
		line-height: 1.2em;
		padding: 0 0 .8em 0;
	}
</style type="text/css">
  
</head>
<body>

	<p>Ceci est une demande pour déployer <a href="http://www.cogeco.ca/web/">www.cogeco.ca</a> en production.</p>
	<p>
		<strong>Date de déploiement :</strong> <?php echo $deployTime ?><br />
		<strong>Type de déploiement :</strong> Maintenance<br />
		<strong>Liste des changements :</strong>
	</p>

	<table cellpadding="0" cellspacing="0">
		<tr>
			<th><p><strong>Logs SVN</strong></p></th>
			<td>
				<?php
					$count = count($svnEntries);
					if (isset($svnEntries) && $count > 0) {
						$i = 0;
						foreach ($svnEntries as $revision => $entry) { ?>
						<br />
						<strong>Révision :</strong> <?php echo $entry->revision ?><br />
						<strong>Auteur :</strong> <?php echo $entry->author ?><br />
						<strong>Date :</strong> <?php echo @ucfirst(strftime("%A le %d %B %Y à %Hh%M", strtotime($entry->date))) ?><br />
						<strong>Message :</strong> <?php echo nl2br(htmlspecialchars($entry->message)) ?>
						<br /><br />
							<?php
							$i++;
							if ($i !== $count) {?>
								<hr />
							<?php }
						}
					} else { ?>
					<p>Aucun changement (SVN) depuis le dernier déploiement.</p>
				<?php } ?>
			</td>
		</tr>
		<tr>
			<th><p><strong>Database changes?</strong></p></th>
			<td><p>Data changes only</p></td>
		</tr>
	</table>

</body>
</html>
