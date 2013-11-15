<?php namespace WiGit; ?>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php print $wigit->getTitle() ?> &raquo; Editing <?php print $wigit->getPageHTML() ?></title>
		<link rel="stylesheet" type="text/css" href="<?php print $wigit->getCSSURL() ?>" />
	</head>
	<body>
        <?php include __DIR__ . '/navigation.php'; ?>

		<div id="header">
			<h1 id="title">Editing <?php print $wigit->getPageHTML() ?></h1>
		</div>

        <?php if (isset($wikiContent) && $wikiContent) {
            print "<h3>Preview</h3>";
            print "<div id='preview'>$wikiContent</div>\n";
        }?>

		<div id="form">
			<form method="post" action="<?php print $query->getPageURL(); ?>">
				<p><textarea name="data" cols="80" rows="20" style="width: 100%"><?php print $wikiData; ?></textarea></p>
				<p>
                  <input type="submit" value="publish" />
                  <input type="submit" name="a" value="preview" />
                  <a href="<?php print $query->getPageURL(); ?>">cancel</a>
                </p>
			</form>
		</div>

		<div id="footer" style='text-align: left;'>
			<p class='syntax'>
				<span style='font-weight: bold;'>Syntax:</span> <?php print getEditingHelpText(); ?>
			</p>
		</div>

        <?php include __DIR__ . '/plug.php'; ?>

	</body>
</html>
