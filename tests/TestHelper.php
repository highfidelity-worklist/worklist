<?php
/**
 * Tests
 *
 * @package UnitTests
 * @version $Id$
 */

/*
 * Start output buffering
 */
ob_start();
/*
 * Include PHPUnit dependencies
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Framework/IncompleteTestError.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/Runner/Version.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Util/Filter.php';

/*
 * Set error reporting to the desired comply level
 */
error_reporting( E_ALL | E_STRICT );

/*
 * Determine the root, library, and tests directories of the framework
 * distribution.
 */
$appRoot        = dirname(__FILE__) . '/..';
$appCoreTests   = "$appRoot/tests";

set_include_path(
    $appRoot . PATH_SEPARATOR .
    $appCoreTests . PATH_SEPARATOR .
    get_include_path()
);

/*
 * Omit from code coverage reports the contents of the tests directory
 */
foreach (array('php', 'phtml', 'csv') as $suffix) {
    PHPUnit_Util_Filter::addDirectoryToFilter($appCoreTests, ".$suffix");
}

/*
 * Load the user-defined test configuration file, if it exists; otherwise, load
 * the default configuration.
 */
if (is_readable($appCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php')) {
    require_once $appCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php';
} else {
    require_once $appCoreTests . DIRECTORY_SEPARATOR . 'TestConfiguration.php.dist';
}

if (defined('TESTS_GENERATE_REPORT') && TESTS_GENERATE_REPORT === true &&
    version_compare(PHPUnit_Runner_Version::id(), '3.1.6', '>=')) {
    // filter
}

/**
 * Tests base directory
 */
define('TESTS_BASE_DIRECTORY', dirname(__FILE__));
/**
 * App base directory
 */
define('TESTS_APPLICATION_BASE', $appRoot);
/*
 * Unset global variables that are no longer needed.
 */
unset($appRoot, $appCoreTests);
