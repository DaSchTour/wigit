<?php

/**
 * Initialize testing environment and run all tests if required.
 *
 * You can call this file from command line or browser or include
 * it to get defined a 'TestBase' class for all unit tests.
 *
 * The current version does only support SimpleTest
 */

$is_included = count(get_included_files()) > 1;
$simpletest = __DIR__.'/simpletest/autorun.php';

if (file_exists($simpletest)) {
    require_once($simpletest);
    class TestBase extends UnitTestCase { }
} else {
    throw new Exception("No testing framework found! Please get SimpleTest");
}


if (!$is_included) {

    // SimpleTest test suite
    class FileTestSuite extends TestSuite {
        function FileTestSuite() {
            $this->TestSuite('All file tests');
            $this->addFile(__DIR__.'/query.php');
            # ...add more files...
        }
    }

}

?>