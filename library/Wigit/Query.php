<?php
/**
 * +-----------------------------------------------------------------------+
 * | Copyright (c) 2009, Jakob Voß                                         |
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
 * | Author: Jakob Voß                                                     |
 * +-----------------------------------------------------------------------+
 *
 * PHP version 5
 *
 * @category VersionControl
 * @package  Wigit
 * @author   Jakob Voß <jakob@nichtich.de>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  GIT: $Id$
 * @link     http://github.com/nichtich/wigit
 */
namespace Wigit;

/**
 * Query
 *
 * A query consists of an action, a page and some optional parameters.
 * This class can maps HTTP requests to queries and vice versa.
 */
class Query {
    private $action;
    private $page;
    private $parameters = array();
    private $config;

    /**
     * Create a query by parsing a HTTP request.
     *
     * This should be the only method that inspects server variables such as
     * $_REQUEST and $_SERVER. You must pass a Config object that may contain
     * default values and must define the base URL.
     *
     * The action can be supplied as first part of the request URI or with the
     * HTTP parameter 'a'. It is always converted to lowercase letters if set 
     * to the empty string if it contains other characters but letters, digits
     * and hyphem.
     *
     * The page name can be supplied as second part of the request URI or with 
     * the HTTP parameter 'p'. If the page name is not valid Unicode or if it 
     * contains line breaks then it is set to the empty string. If the right
     * PEAR module is installed, it is also normalized to Unicode normalization
     * form C (NFC).
     */
    public function __construct($config) {
        global $_SERVER, $_REQUEST;

        $this->config = $config;

        $this->action = @$_REQUEST['a'];
        $this->page   = @$_REQUEST['p'];

        # request without query part
        $path = preg_replace('/\?.*$/','',@$_SERVER['REQUEST_URI']);
        $path = substr($path,strlen($config->base_url));
        $path = urldecode($path);

        if (isset($path) && $path != '') {
            if (substr($path,0,1) == '/') {
                $this->page = substr($path,1);
            } else {
                $parts = explode('/',$path,2);
                $this->action = $parts[0];
                if (isset($parts[1]) && $parts[1] != '') {
                    $this->page = $parts[1];
                }
            }
        }
        if ($this->page === null) $this->page = "";

        $method = @$_SERVER['REQUEST_METHOD'];

        // Map HTTP requests methods to actions.
        // All request methods but GET and POST override other ways to specify 
        // actions
        if ($method == 'GET' || "$method" == '') {
            if ($this->action == '') {
                $this->action = $config->default_action;
            }
        } else if ($method == 'POST') {
            if ($this->action == '') {
               $this->action = 'POST';
            }
        } else {
            $this->action = $method;
        } # NOTE: support HTTP PUT?

        foreach($_REQUEST as $key => $value) {
            if (preg_match('/^(p|page|a|action)$/',$key)) continue;
            if (isset($this->parameters[$key])) next;
            $this->parameters[$key] = $value;
        }

        if (preg_match('[^A-Za-z0-9-]', $this->action)) {
            $this->action = '';
        } else {
            $this->action = $this->action;
        }

        if ($this->page == '' && $this->action == $config->default_action) {
            $this->page = $config->default_page;
        }

        # FIXME: this passes five and six octet UTF-8 which is not valid
        # see http://hsivonen.iki.fi/php-utf8/ for real validity checker
        if (!preg_match('/^[^\n\r]*$/u', $this->page)) {
            $this->page = '';
        } else {
            # TODO: normalize if PEAR 'intl' is available
            # $this->page = \Normalizer::FORM_C($this->page);
        }
    }

    /**
     * Returns the action of this query.
     *
     * The action is always a (possibly empty) lowercase string that may
     * contain letters (a-z), digits (0-9) and hyphem (-).
     *
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Returns the page name.
     * 
     * The page name is always a (possibly empty) unicode string without line
     * breaks.
     *
     * @return string
     */
    public function getPagename() {
        return $this->page;
    }

    /**
     * Returns the query parameters (excluding action and pagename).
     *
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }

    public function getParam($key) {
        return $this->parameters[$key];
    }

    public function getURL($page="", $action="") {
        $url = $this->config->base_url . "$action";
        if ($page != "") {
            $page = urlencode($page);
            if (strpos($page,'%2F') !== FALSE) {
                $url .= "?p=$page";
            } else {
                $url .= "/$page";
            }
        }
        return $url;
    }

    public function getPageURL($action="") {
        return $this->getURL($this->getPagename(),$action);
    }

    public function asJSON() {
        return json_encode(array(
            'page' => $this->page,
            'action' => $this->action,
            'parameters' => $this->parameters
            # we could add timestamp etc.
        ),JSON_FORCE_OBJECT);
    }
}

?>
