<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php print $wigit->getTitle() ?> &raquo; Error</title>
		<link rel="stylesheet" type="text/css" href="<?php print $wigit->getCSSURL() ?>" />
	</head>
	<body>
        <?php include __DIR__ . '/navigation.php'; ?>

		<div id="header">
			<h1 id="title">An error occured</h1>
		</div>

		<div id="content">
			<?php echo $errorMsg; ?>
		</div>

		<div id="footer">
		</div>
        <?php include __DIR__ . '/plug.php'; ?>
	</body>
</html>
