<?php

require_once(__DIR__.'/test.php');

require_once(__DIR__.'/../library/Wigit/Config.php');
require_once(__DIR__.'/../library/Wigit/Core.php');

class CoreTest extends TestBase {
    function testNames() {
        $names = array("."=>"%2E","/"=>"%2F","ac/dc"=>"ac%2Fdc");

        $config = new Wigit\Config();
        $wigit = new Wigit\Core($config);

        foreach( $names as $from => $to) {
            $this->assertEqual( $wigit->nameToFile($from), $to );
            $this->assertEqual( $wigit->fileToName($to), $from );
        }
    }
}

?>