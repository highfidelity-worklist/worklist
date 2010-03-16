<?php
/**
 * Tests
 *
 * @package Core
 * @subpackage UnitTests
 * @version $Id$
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    /**
     * The default test method
     */
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}
/**
 * Test helper
 */
require_once 'TestHelper.php';
/**
 * Sms
 */
require_once 'Sms/AllTests.php';
/**
 * @package core
 * @subpackage UnitTests
 */
class AllTests
{
    public static function main()
    {
        $parameters = array();

        if (TESTS_GENERATE_REPORT && extension_loaded('xdebug')) {
            $parameters['reportDirectory'] = TESTS_GENERATE_REPORT_TARGET;
        }

        PHPUnit_TextUI_TestRunner::run(self::suite(), $parameters);
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('loveworklist');

        $suite->addTest(Sms_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
?>