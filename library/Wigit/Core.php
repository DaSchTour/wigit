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
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  GIT: $Id$
 * @link     http://github.com/till/wigit              
 */
namespace Wigit;

/**
 * Core
 * 
 * @category VersionControl
 * @package  WiGit
 * @author   Remko Tronçon <remko@el-tramo.be>
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  Release: @package_version@
 * @link     http://github.com/till/wigit
 * @todo     Remove all globals.
 * @todo     Fix up documentation, cs.
 */
class Core
{
    /**
     * @var Config $config An instance of the \Wigit\Config.
     */
    protected $config;

    /**
     * @var string $authBackend Currently only 'http' is supported.
     */
    protected $authBackend = 'http';

    /**
     * Constructor!
     *
     * @param \Wigit\Config $config
     *
     * @return $this
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->base   = dirname(dirname(__DIR__));

    }

    /**
     * A simple method to check if the seup is correct. This could be disabled in
     * index.php once it's verified that everything is setup correctly.
     *
     * @return boolean
     */
    public function checkSetup()
    {
        if (!file_exists($this->base . '/' . $this->config->data_dir)) {
            throw new \RuntimeException("No data_dir: {$this->config->data_dir}.");
        }
        if (!is_writable($this->base . '/' . $this->config->data_dir)) {
            throw new \RuntimeException("data_dir {$this->config->data_dir} is not writable.");
        }
        if ($this->config->timezone) {
            date_default_timezone_set($this->config->timezone);
        }
        return true;
    }

    /**
     * Add and commit a new wikipage.
     *
     * @param string $wikiPage      The filename of the wikipage.
     * @param string $author        The name of the author: foo <foo@example.org>
     * @param string $commitMessage The commit message.
     *
     * @return boolean
     * @throws \RuntimeException For all kinds of reasons.
     */
    public function createNewPage($wikiPage, $author, $commitMessage)
    {
        if (!$this->git("init")) {
            throw new \RuntimeException("Could not init: $wikiPage");
        }
        if (!$this->git("add $wikiPage")) {
            throw new \RuntimeException("Could not add: $wikiPage");
        }
        if (!$this->git("commit --allow-empty --no-verify --message='$commitMessage' --author='$author'")) {
            throw new \RuntimeException("Could not commit: $wikiPage");
        }
        if (!$this->git("gc")) {
            throw new \RuntimeException("Coult not gc: $wikiPage");
        }
        return true;
    }

    /**
     * Wrapper around fopen, fread, fclose.
     *
     * @param string $file The filename to read/open.
     *
     * @return string
     * @throws \RuntimeException When the file could not be opened.
     */
    public function getFileContents($file)
    {
        $fh = fopen($file, 'r');
        if (!is_resource($fh)) {
            throw \RuntimeException("Could not open file: {$file}");
        }
        $data = fread($fh, filesize($file));
        fclose($fh);
        return $data;
    }

    /**
     * Receive the history of a file.
     *
     * @param string $file An optional parameter.
     *
     * @return array A numeric array stacked with associative arrays containing the
     *               history.
     */
    public function getGitHistory($file = "")
    {
        $output = array();

        // FIXME: Find a better way to find the files that changed than --name-only
        $this->git("log --name-only --pretty=format:'%H>%T>%an>%ae>%aD>%s' -- $file", $output);

        $history = array();
        $historyItem = array();
        foreach ($output as $line) {
            $logEntry = explode(">", $line, 6);
            if (sizeof($logEntry) > 1) {

                // Populate history structure
                $historyItem = array(
                    "author"        => $logEntry[2], 
                    "email"         => $logEntry[3],
                    "linked-author" => (
                        $logEntry[3] == "" ? $logEntry[2] : "<a href=\"mailto:$logEntry[3]\">$logEntry[2]</a>"
                    ),
                    "date" => $logEntry[4], 
                    "message" => $logEntry[5],
                    "commit" => $logEntry[0]
                );

			} else if (!isset($historyItem["page"])) {
                $historyItem["page"] = $line;
                $history[]           = $historyItem;
            }
        }
        return $history;
    }

    /**
     * @return array
     */
    public function getGitIndex()
    {
        $index  = array();
        $output = array();
        $this->git("ls-files", $output);
        foreach ($output as $line) {
	        $index[] = array("page" => $line);
        }
        return $index;
    }

    /**
     * This relies on a local map in the config.
     *
     * @return string User <user@example.org>
     * @uses   self::$config
     */
    public function getAuthorForUser($user)
    {
        if (isset($this->config->authors[$user])) {
            return $this->config->authors[$user];
		}
        if ($user != "") {
            return "$user <$user@wiggit>";
        }
        return $this->config->default_author;
    }

    /**
     * Return the currently authenticated user.
     *
     * @return string
     * @uses   self::$authBackend
     */
    public function getAuthenticatedUser()
    {
        if ($this->authBackend == 'http') {
            return $this->getHttpAuthUser();
        }
    }

    /**
     * This function determins the currently logged in user via HTTP Auth.
     *
     * The code was copied from phpMyID. Thanks to the phpMyID dev(s).
     *
     * @return string
     * @uses   $_SERVER
     */
    protected function getHttpAuthUser()
    {
        if (function_exists('apache_request_headers') && ini_get('safe_mode') == false) {
            $arh = apache_request_headers();
            $hdr = $arh['Authorization'];
        } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
            $hdr = $_SERVER['PHP_AUTH_DIGEST'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $hdr = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_ENV['PHP_AUTH_DIGEST'])) {
            $hdr = $_ENV['PHP_AUTH_DIGEST'];
        } elseif (isset($_REQUEST['auth'])) {
            $hdr = stripslashes(urldecode($_REQUEST['auth']));
        } else {
            $hdr = null;
        }
        $digest = (substr($hdr,0,7) == 'Digest ') ? substr($hdr, strpos($hdr, ' ') + 1) : $hdr;
        if (!is_null($digest)) {
            $hdr = array();
            \preg_match_all('/(\w+)=(?:"([^"]+)"|([^\s,]+))/', $digest, $mtx, PREG_SET_ORDER);
            foreach ($mtx as $m) {
                if ($m[1] == "username") {
                    return $m[2] ? $m[2] : str_replace("\\\"", "", $m[3]);
                }
            }
        }
        return $_SERVER['PHP_AUTH_USER'];
    }

    /**
     * @param string $command
     * @param string $output
     */
    public function git($command, &$output = "")
    {
		$gitDir      = $this->base . "/{$this->config->data_dir}/.git";
		$gitWorkTree = $this->base . "/{$this->config->data_dir}";

		$gitCommand  = "{$this->config->git} --git-dir=$gitDir --work-tree=$gitWorkTree $command";
		$output      = array();
		$result;

		// FIXME: Only do the escaping and the 2>&1 if we're not in safe mode 
		// (otherwise it will be escaped anyway).
		// FIXME: Removed escapeShellCmd because it clashed with author.
		$oldUMask = umask(0022);
		exec($gitCommand . " 2>&1", $output, $result);
		$umask = $oldUMask;
		// FIXME: The -1 is a hack to avoid 'commit' on an unchanged repo to
		// fail.
		if ($result != 0) {
			// FIXME: HTMLify these strings
			print "<h1>Error</h1>\n<pre>\n";
			print "$" . $gitCommand . "\n";
			print join("\n", $output) . "\n";
			//print "Error code: " . $result . "\n";
			print "</pre>";
			return 0;
		}
		return 1;
	}

    protected function sanitizeName($name)
    {
        return \preg_replace("[^A-Za-z0-9]", "_", $name);
    }

    public function parseResource($resource)
    {

        $matches = array();
        $page    = "";
        $type    = "";
        if (preg_match("=\/(.*)\/(.*)=", $resource, $matches)) {

            $page = $this->sanitizeName($matches[1]);
            $type = $matches[2];

        } else if (preg_match("=\/(.*)=", $resource, $matches)) {

            $page = $this->sanitizeName($matches[1]);

        }

        if ($page == "") {
            $page = $this->config->default_page;
        }
        if ($type == "") {
            $type = "view";
        }
        return array("page" => $page, "type" => $type);
    }


    // --------------------------------------------------------------------------
    // Wikify
    // --------------------------------------------------------------------------

    public function wikify($text)
    {
        // FIXME: Do not apply this in <pre> and <notextile> blocks.

        // Linkify
        $text = \preg_replace('@([^:])(https?://([-\w\.]+)+(:\d+)?(/([%-\w/_\.]*(\?\S+)?)?)?)@', '$1<a href="$2">$2</a>', $text);

        // WikiLinkify
        $text = \preg_replace('@\[([A-Z]\w+)\]@', '<a href="' . $this->config->script_url . '/$1">$1</a>', $text);
        $text = \preg_replace('@\[([A-Z]\w+)\|([\w\s]+)\]@', '<a href="' . $this->config->script_url . '/$1">$2</a>', $text);

        // Textilify
        $textile = new \Textile();
        return $textile->TextileThis($text);
    }

    // --------------------------------------------------------------------------
    // Utility functions (for use inside templates)
    // --------------------------------------------------------------------------

    function getViewURL($page, $version = null)
    {
        if ($version !== null) {
            return "{$this->config->script_url}/{$page}/{$version}";
        }
        return "{$this->config->script_url}/{$page}";
    }

	function getPostURL() {
		$page = $this->getPage();
		return "{$this->config->script_url}/{$page}";
	}

	function getEditURL() {
		$page = $this->getPage();
		return "{$this->config->script_url}/{$page}/edit";
	}

	function getHistoryURL() {
		$page = $this->getPage();
		return "{$this->config->script_url}/$page/history";
	}
	
	function getGlobalHistoryURL() {
		return "{$this->config->script_url}/history";
	}

    function getGlobalIndexURL() {
        return "{$this->config->script_url}/index";
    }


    function getHomeURL()
    {
        return "{$this->config->script_url}/";
    }

	function getUser()
    {
		global $wikiUser;
		return $wikiUser;
	}

	function getTitle() {
		return $this->config->title;
	}

    function getPage()
    {
        global $wikiPage;
        return $wikiPage;
    }

    /**
     * Get the current page name in HTML encoding.
     * @return string the 
     */
    function getPageHTML()
    {
        return htmlspecialchars($this->getPage());
    }

    function getCSSURL()
    {
        return "{$this->config->base_url}/" . $this->getThemeDir() . "/style.css";
    }

    function getThemeDir()
    {
        return "themes/{$this->config->theme}";
    }

    function getFile()
    {
        global $wikiFile;
        return $wikiFile;
    }

    function getContent()
    {
        global $wikiContent;
        return $wikiContent;
    }

    function getRawData()
    {
        global $wikiData;
        return $wikiData;
    }
}
