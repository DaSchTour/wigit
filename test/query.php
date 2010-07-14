<?php

require_once(__DIR__.'/test.php');

require_once(__DIR__.'/../library/Wigit/Config.php');
require_once(__DIR__.'/../library/Wigit/Query.php');

class QueryTest extends TestBase {

    private $cases = array(
        // simple query
        0 => array('/','view','foo','GET','/',array(),
            'view','foo',array()
        ),
        // other HTTP requests but 'GET' and other default action
        1 => array('/','view','foo','DELETE','/',array(),
            'DELETE','',array()
        ),
        2 => array('/','view','foo','HEAD','/',array(),
            'HEAD','',array() # TODO: should this use default page?
        ),
        3 => array('/','view','foo','XYZ','/',array(),
            'XYZ','',array() 
        ),
        4 => array('/','default','foo','','/',array(),
            'default','foo',array()
        ),
        // action and page in request
        5 => array('/','view','foo','GET','//bar',array(),
            'view','bar',array()
        ),
        6 => array('/','view','home','GET','/bar',array(),
            'bar','',array()
        ),
        7 => array('/','view','home','GET','/x/',array(),'x','',array()),
        8 => array('/','view','home','GET','/x/0',array(),'x','0',array()),
        9 => array('/','view','home','GET','/0/x',array(),'0','x',array()),
        10 => array('/','view','home','GET','/a/b/c',array(),'a','b/c',array()),
        // URL encoding
        11 => array('/','v','home','GET','/%3C',array(),'<','',array()),
        12 => array('/','v','home','GET','/%25',array(),'%','',array()),
        // TODO: test parameter 'p' and 'a'
    );

    function __construct() {

        // add a base_url
        $c = count($this->cases);
        for($i=0; $i<$c; $i++) {
            $test = $this->cases[$i];
            $test[0] = '/base' . $test[0];
            $test[4] = '/base' . $test[4];
            $this->cases[$c+$i] = $test;
        }

    }

    function testQueries() {
        global $_SERVER, $_REQUEST;

        for($i=0; $i<count($this->cases); $i++) {
            $test = $this->cases[$i];

            $config = new Wigit\Config();
            if ($test[0] !== null) $config->base_url = $test[0];
            if ($test[1] !== null) $config->default_action = $test[1];
            if ($test[2] !== null) $config->default_page = $test[2];
            $_SERVER['REQUEST_METHOD'] = $test[3];
            $_SERVER['REQUEST_URI'] = $test[4];
            $_REQUEST = $test[5];

            $query = new Wigit\Query($config);

            $this->assertEqual($query->getAction(), $test[6], "T$i: %s");
            $this->assertEqual($query->getPagename(), $test[7], "T$i: %s");
            $this->assertEqual($query->getParameters(), $test[8], "T$i: %s");
        }
    }
}

?>
