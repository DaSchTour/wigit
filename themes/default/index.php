<html xmlns="http://www.w3.org/1999/xhtml">
	<?php $indexTitle = "Index"; ?>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php print $wigit->getTitle() ?> &raquo; <?php print $indexTitle ?></title>
		<link rel="stylesheet" type="text/css" href="<?php print $wigit->getCSSURL() ?>" />
	</head>
	<body>
        <?php include __DIR__ . '/navigation.php'; ?>

		<div id="header">
			<h1 id="title"><?php print $indexTitle ?></h1>
		</div>

		<div id="index">
			<p>
			<table>
				<tr><th>Page</th><!--th>Date</th><th>Author</th><th>Message</th--></tr>
			<?php 
				foreach ($wikiIndex as $item) {
					print "<tr>"
						. "<td class='page'><a href=\"" . $wigit->getViewURL($item["page"]) . "\">" 
						. htmlspecialchars($item["page"]) . "</a></td>"
						#. "<td>" . $item["date"] . "</td>"
						#. "<td class='author'>" . $item["linked-author"] . "</td>"
						#. "<td>" . $item["message"] . "</td>"
						. "</tr>\n";
				}
			?>
			</table>
			</p>
		</div>
        <?php include __DIR__ . '/plug.php'; ?>
	</body>
</html>
