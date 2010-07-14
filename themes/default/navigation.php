<div id="navigation">
    <p><a href="<?php print $query->getURL() ?>">Home</a> 
    <?php if ($query->getPagename() != "") {
      print "| <a href='" . $query->getPageURL("history") . "'>History</a>";
    } ?>
    | <a href="<?php print $query->getURL("","history") ?>">Changes</a>
    | <a href="<?php print $query->getURL("","index") ?>">Index</a>
    <?php if ($wigit->getUser() != "") { ?>| Logged in as <?php print $wigit->getUser(); } ?>
    </p>
</div>