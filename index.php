<?php 
/**
 * +-----------------------------------------------------------------------+
 * | Copyright (c) 2009, Remko Tronçon                                     |
 * | All rights reserved.                                                  |
 * |                                                                       |
 * | Redistribution and use in source and binary forms, with or without    |
 * | modification, are permitted provided that the following conditions    |
 * | are met:                                                              |
 * |                                                                       |
 * | o Redistributions of source code must retain the above copyright      |
 * |   notice, this list of conditions and the following disclaimer.       |
 * | o Redistributions in binary form must reproduce the above copyright   |
 * |   notice, this list of conditions and the following disclaimer in the |
 * |   documentation and/or other materials provided with the distribution.|
 * | o The names of the authors may not be used to endorse or promote      |
 * |   products derived from this software without specific prior written  |
 * |   permission.                                                         |
 * |                                                                       |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
 * | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
 * | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
 * | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
 * | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
 * | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
 * | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
 * | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
 * | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
 * |                                                                       |
 * +-----------------------------------------------------------------------+
 * | Author: Remko Tronçon                                                 |
 * +-----------------------------------------------------------------------+
 *
 * PHP version 5
 *
 * @category VersionControl
 * @package  Wigit
 * @author   Remko Tronçon <remko@el-tramo.be>
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  GIT: $Id$
 * @link     http://github.com/till/wigit
 */
namespace Wigit;

require_once __DIR__ . '/library/classTextile.php';
require_once __DIR__ . '/library/Wigit.php';

    
$config = new Config;
$config->checkLocalConfig(__DIR__ . '/etc/config.php');

$wigit = new Core($config);

// --------------------------------------------------------------------------
// Initialize globals
// --------------------------------------------------------------------------

$wikiUser    = $wigit->getAuthenticatedUser();
$resource    = $wigit->parseResource($_GET['r']);
$wikiPage    = $resource["page"];
$wikiSubPage = $resource["type"];
$wikiFile    = __DIR__ . "/" . $config->data_dir . "/" . $wikiPage;

try {
    $wigit->checkSetup();
} catch (\RuntimeException $e) {
    $errorMsg = "Check setup: " . (string) $e;
    include $wigit->getThemeDir() . '/error.php';
    exit;
}

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
        include $wigit->getThemeDir() . "/history.php";
    }
    // Page index
    else if ($wikiPage == "index") {
        $wikiIndex = $wigit->getGitIndex();
        include $wigit->getThemeDir() . "/index.php";
    }
    // Viewing
    else if ($wikiSubPage == "view") {
        if (!file_exists($wikiFile)) {
            header("Location: " . $config->script_url . "/" . $resource["page"] . "/edit");
            exit;
        }

        // Open the file
        $data = $wigit->getFileContents($wikiFile);

        // Put in template
        $wikiContent = $wigit->wikify($data);
        include $wigit->getThemeDir() . "/view.php";
    }
    // Editing
    else if ($wikiSubPage == "edit") {
        if (file_exists($wikiFile)) {
            $data = $wigit->getFileContents($wikiFile);
        } else {
            $data = 'This page does not exist (yet).';
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
    else if (preg_match("/[0-9A-F]{20,20}/", $wikiSubPage)) {
        $output = array();
        if (!$wigit->git("cat-file -p " . $wikiSubPage . ":$wikiPage", $output)) {
            exit('cat-file');
        }
        $wikiContent = $wigit->wikify(join("\n", $output));
        include $wigit->getThemeDir() . "/view.php";
    }
    else {
        $errorMsg = "Unknow subpage: " . $wikiSubPage;
        include $wigit->getThemeDir() . '/error.php';
    }
    exit;
}

$errorMsg = "Unsupported METHOD: " . $_SERVER['REQUEST_METHOD'];
include $wigit->getThemeDir() . '/error.php';

?>
