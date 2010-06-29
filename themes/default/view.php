<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php print $wigit->getTitle() ?> &raquo; <?php print $wigit->getPage() ?></title>
		<link rel="stylesheet" type="text/css" href="<?php print $wigit->getCSSURL() ?>" />
	</head>
	<body>
		<div id="navigation">
			<p><a href="<?php print $wigit->getHomeURL() ?>">Home</a> 
			| <a href="<?php print $wigit->getGlobalHistoryURL() ?>">History</a>
			<?php if ($wigit->getUser() != "") { ?>| Logged in as <?php print $wigit->getUser(); } ?>
			</p>
		</div>

		<div id="header">
			<h1 id="title"><?php print $wigit->getPage() ?></h1>
			<p>[ <a href="<?php print $wigit->getEditURL()?>">edit</a> | 
				   <a href="<?php print $wigit->getHistoryURL()?>">history</a> ]</p>
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
