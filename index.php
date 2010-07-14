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
require_once __DIR__ . '/library/Wigit/Query.php';

$config = new Config;
$config->checkLocalConfig(__DIR__ . '/etc/config.php');

$wigit = new Core($config);

// --------------------------------------------------------------------------
// Initialize globals
// --------------------------------------------------------------------------

$wikiUser    = $wigit->getAuthenticatedUser();

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

$query = new Query($config);

$wikiPage     = $query->getPagename();
$wikiFilename = $wigit->nameToFile($wikiPage);

$wikiFile     = __DIR__ . "/" . $config->data_dir . "/" . $wikiFilename;

if ($query->getAction() == 'POST') {

    // Save (TODO: does not work for page named "./%")
    $handle = fopen($wikiFile, "w");
    $data = $query->getParam('data');
    fputs($handle, stripslashes($data));
    fclose($handle);

    // TODO: if page does not exist use "Created"
    $commitMessage = addslashes("Changed $wikiPage");
    $author        = addslashes($wigit->getAuthorForUser($wigit->getUser()));

    $wigit->createNewPage($wikiPage, $author, $commitMessage);

    header("Location: " . $query->getURL($wikiPage));
    exit;
} 
else if ($query->getAction() == "DELETE") {
    // Delete
    if (file_exists($wikiFile)) {
        if (!$wigit->git("rm $wikiFilename")) {
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
    header("Location: " . $query->getPageURL());
    exit;
}
// Global history
else if ($query->getAction() == "history") {
    $wikiHistory = $wigit->getGitHistory();
    $wikiPage = "";
    include $wigit->getThemeDir() . "/history.php";
}
// Page index
else if ($query->getAction() == "index") {
    $wikiIndex = $wigit->getGitIndex();
    include $wigit->getThemeDir() . "/index.php";
}
else if ($query->getAction() == "preview") {

    $wikiData = $query->getParam("data");

    // Put in template
    $wikiContent = $wigit->wikify($wikiData);

    include $wigit->getThemeDir() . "/edit.php";

// Viewing
} else if ($query->getAction() == "view") {
    if (!file_exists($wikiFile)) {
        header("Location: " . $query->getURL($wikiPage,"edit"));
        exit;
    }

    // Open the file
    $data = $wigit->getFileContents($wikiFile);

    // Put in template
    $wikiContent = $wigit->wikify($data);
    include $wigit->getThemeDir() . "/view.php";
}
// Editing
else if ($query->getAction() == "edit") {
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
else if ($query->getAction() == "history") {
    $wikiHistory = $wigit->getGitHistory($wikiPage);
    include $wigit->getThemeDir() . "/history.php";
}
// Specific version
else if (preg_match("/[0-9A-F]{20,20}/i", $query->getAction())) {
    $output = array();
    if (!$wigit->git("cat-file -p " . $query->getAction() . ":$wikiFilename", $output)) {
        exit('cat-file');
    }
    $wikiContent = $wigit->wikify(join("\n", $output));
    include $wigit->getThemeDir() . "/view.php";
}
else {
    $action = $query->getAction();
    // TODO: test this
    if ($query->getPagename() != "") $action .= "/".$query->getPagename();
    $haction = htmlspecialchars($action);
    $errorMsg = "Unknow action: $haction! Did you mean page " .
                "<a href='" . $query->getURL($action) . "'>$haction</a>?";
    include $wigit->getThemeDir() . '/error.php';
}
exit;

?>
