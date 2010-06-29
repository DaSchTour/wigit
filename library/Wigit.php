<?php 
namespace Wigit;

/* 
 * @category
 * @package   WiGit
 * @copyright (c) Remko Tronçon (http://el-tramo.be)
 * See COPYING for details
 */
class Core
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
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
        if (!$wigit->git("init")) {
            throw new \RuntimeException("Could not init: $wikiPage");
        }
        if (!$wigit->git("add $wikiPage")) {
            throw new \RuntimeException("Could not add: $wikiPage");
        }
        if (!$wigit->git("commit --allow-empty --no-verify --message='$commitMessage' --author='$author'")) {
            throw new \RuntimeException("Could not commit: $wikiPage");
        }
        if (!$wigit->git("gc")) {
            throw new \RuntimeException("Coult not gc: $wikiPage");
        }
        return true;
    }

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
     * Return the current HTTP Auth user.
     *
     * @return string
     */
    public function getHTTPUser()
    {
        // This code is copied from phpMyID. Thanks to the phpMyID dev(s).
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
		$gitDir      = __DIR__ . "/../{$this->config->data_dir}/.git";
		$gitWorkTree = __DIR__ . "/../{$this->config->data_dir}";

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