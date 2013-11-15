<html xmlns="http://www.w3.org/1999/xhtml">

	<?php $historyTitle = "History" . ($wigit->getPage() == "" ? "" : " of " . $wigit->getPageHTML(); ?>

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php print $wigit->getTitle() ?> &raquo; <?php print $historyTitle ?></title>
		<link rel="stylesheet" type="text/css" href="<?php print $wigit->getCSSURL() ?>" />
	</head>
	<body>
        <?php include __DIR__ . '/navigation.php'; ?>

		<div id="header">
			<h1 id="title"><?php print $historyTitle ?></h1>
		</div>

		<div id="history">
			<p>
			<table>
				<tr><th>Date</th><th>Author</th><th>Page</th><th>Message</th></tr>
			<?php 
				foreach ($wikiHistory as $item) {
					print "<tr>"
						. "<td>" . $item["date"] . "</td>"
						. "<td class='author'>" . $item["linked-author"] . "</td>"
						. "<td class='page'><a href=\"" . $query->getURL($item["page"]) . "\">" . $item["page"] . "</a></td>"
						. "<td>" . $item["message"] . "</td>"
						. "<td>" . "<a href=\"" . $query->getURL($item["page"], $item["commit"]) . "\">View</a></td>"
						. "<td>" . "</td>"
						. "</tr>\n";
				}
			?>
			</table>
			</p>
		</div>
        <?php include __DIR__ . '/plug.php'; ?>
	</body>
</html>
