<?php
/**
 * Tests
 *
 * @package Sms
 * @subpackage UnitTests
 * @version $Id$
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    /**
     * The default test method
     */
    define('PHPUnit_MAIN_METHOD', 'Sms_AllTests::main');
}
/**
 * Test helper
 */
require_once dirname(__FILE__) . '/../TestHelper.php';
/**
 * Sms_SmsTest
 */
require_once 'SmsTest.php';
/**
 * Sms_MessageTest
 */
require_once 'MessageTest.php';
/**
 * Sms_Backend_EmailTest
 */
require_once 'Sms/Backend/EmailTest.php';
require_once 'Sms/Backend/ClickatellTest.php';
/**
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_AllTests
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
        $suite = new PHPUnit_Framework_TestSuite();

        $suite->addTestSuite('Sms_SmsTest');
        $suite->addTestSuite('Sms_MessageTest');
        $suite->addTestSuite('Sms_Backend_EmailTest');
        $suite->addTestSuite('Sms_Backend_ClickatellTest');
        if (defined('TESTS_SMS_CLICKATELL_SOAP') && TESTS_SMS_CLICKATELL_SOAP) {
            require_once 'Sms/Backend/ClickatellTest/SoapTest.php';
            $suite->addTestSuite('Sms_Backend_ClickatellTest_SoapTest');
        } else {
            require_once 'Sms/Backend/ClickatellTest/SkipSoapTest.php';
            $suite->addTestSuite('Sms_Backend_ClickatellTest_SkipSoapTest');
        }
        if (defined('TESTS_SMS_CLICKATELL_SOAP_MESSAGE') && TESTS_SMS_CLICKATELL_SOAP_MESSAGE) {
            require_once 'Sms/Backend/ClickatellTest/SendMessageTest.php';
            $suite->addTestSuite('Sms_Backend_ClickatellTest_SendMessageTest');
        } else {
            require_once 'Sms/Backend/ClickatellTest/SkipSendMessageTest.php';
            $suite->addTestSuite('Sms_Backend_ClickatellTest_SkipSendMessageTest');
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Sms_AllTests::main') {
    Sms_AllTests::main();
}
?>
