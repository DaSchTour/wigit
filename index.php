<?php 
namespace Wigit;

/* 
 * WiGit
 * (c) Remko Tronçon (http://el-tramo.be)
 * See COPYING for details
 */

require_once __DIR__ . '/library/classTextile.php';
require_once __DIR__ . '/library/Wigit.php';
require_once __DIR__ . '/library/Wigit/Config.php';

    
$config = new Config;
$config->checkLocalConfig(__DIR__ . '/etc/config.php');

$wigit = new Core($config);

// --------------------------------------------------------------------------
// Initialize globals
// --------------------------------------------------------------------------

$wikiUser    = $wigit->getHTTPUser();
$resource    = $wigit->parseResource($_GET['r']);
$wikiPage    = $resource["page"];
$wikiSubPage = $resource["type"];
$wikiFile    = __DIR__ . "/" . $config->data_dir . "/" . $wikiPage;

$wigit->checkSetup();

// --------------------------------------------------------------------------
// Process request
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['data'])) {
        header("Location:{$wikiHome}?msg=noPostDataSet");
        exit;
    }
    if (trim($_POST['data']) == "") {
        // Delete
       	if (file_exists($wikiFile)) {
            if (!$wigit->git("rm $wikiPage")) {
                exit('rm');
            }

   	    	$commitMessage = addslashes("Deleted $wikiPage");
        	$author        = addslashes($wigit->getAuthorForUser($wigit->getUser()));
	        if (!$wigit->git("commit --allow-empty --no-verify --message='$commitMessage' --author='$author'")) {
                exit('commit');
            }
  			if (!$wigit->git("gc")) {
                exit('gc');
            }
        }
        header("Location: $wikiHome");
	    exit;
    }

    // Save
    $handle = fopen($wikiFile, "w");
    fputs($handle, stripslashes($_POST['data']));
    fclose($handle);

    $commitMessage = addslashes("Changed $wikiPage");
    $author        = addslashes($wigit->getAuthorForUser($wigit->getUser()));

    $wigit->createNewPage($wikiPage, $author, $commitMessage);
    header("Location: " . $wigit->getViewURL($wikiPage));
    exit;
}

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		// Global history
		if ($wikiPage == "history") {
			$wikiHistory = $wigit->getGitHistory();
			$wikiPage = "";
			include getThemeDir() . "/history.php";
		}
		// Viewing
		else if ($wikiSubPage == "view") {
			if (!file_exists($wikiFile)) {
				header("Location: " . $config->script_url . "/" . $resource["page"] . "/edit");
				exit;
			}

			// Open the file
			$handle = fopen($wikiFile, "r");
			$data   = fread($handle, filesize($wikiFile));
			fclose($handle);

			// Put in template
			$wikiContent = $wigit->wikify($data);
			include $wigit->getThemeDir() . "/view.php";
		}
		// Editing
		else if ($wikiSubPage == "edit") {
			if (file_exists($wikiFile)) {
				$handle = fopen($wikiFile, "r");
				$data = fread($handle, filesize($wikiFile));
			}

			// Put in template
			$wikiData = $data;
			include $wigit->getThemeDir() . "/edit.php";
		}
		// History
		else if ($wikiSubPage == "history") {
			$wikiHistory = $wigit->getGitHistory($wikiPage);
			include $wigit->getThemeDir() . "/history.php";
		}
		// Specific version
		else if (eregi("[0-9A-F]{20,20}", $wikiSubPage)) {
			$output = array();
			if (!$wigit->git("cat-file -p " . $wikiSubPage . ":$wikiPage", $output)) {
				exit('cat-file');
			}
			$wikiContent = $wigit->wikify(join("\n", $output));
			include $wigit->getThemeDir() . "/view.php";
		}
		else {
			print "Unknow subpage: " . $wikiSubPage;
		}
        exit;
	}
    die("Unsupported METHOD: " . $_SERVER['REQUEST_METHOD']);
?>
