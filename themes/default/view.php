<?php namespace WiGit; ?>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php print $wigit->getTitle() ?> &raquo; <?php print $query->getPagename() ?></title>
		<link rel="stylesheet" type="text/css" href="<?php print $wigit->getCSSURL() ?>" />
	</head>
	<body>
        <?php include __DIR__ . '/navigation.php'; ?>

		<div id="header">
			<h1 id="title"><?php print $query->getPagename() ?></h1>
			<p>[ <a href="<?php print $query->getPageURL("edit")?>">edit</a> 
               | <a href="<?php print $query->getPageURL("history")?>">history</a> ]</p>
		</div>

		<div id="content">
			<?php print $wigit->getContent(); ?>
		</div>

		<div id="footer">
			<p>
				Last modified on <?php print date("F d Y H:i:s", filemtime($wigit->getFile())); ?> 
			</p>
		</div>
        <?php include __DIR__ . '/plug.php'; ?>
	</body>
</html>
