<?php
/**
 * Skip curl testing
 *
 * @package Sms
 * @subpackage UnitTests
 * @version $Id$
 */
/**
 * PHPUnit
 */
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'lib/Sms.php';
/**
 * SOAP test
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_Backend_ClickatellTest_SoapTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sms_Backend_Clickatell
     */
    protected $backend;

    public function setUp()
    {
        $options = array(
            'clickatellApiWSDL'    => TESTS_SMS_CLICKATELL_SOAP_WSDL,
            'clickatellApiId'      => TESTS_SMS_CLICKATELL_SOAP_API_ID,
            'clickatellUsername'   => TESTS_SMS_CLICKATELL_SOAP_USERNAME,
            'clickatellPassword'   => TESTS_SMS_CLICKATELL_SOAP_PASSWORD
        );
        $this->backend = new Sms_Backend_Clickatell($options);
    }

    public function testCanAuthenticate()
    {
        try {
            $this->assertTrue($this->backend->authenticate());
        } catch (Sms_Backend_Exception $e) {
            $this->fail('Failed : ' . $e->getMessage());
        }
        $this->assertTrue($this->backend->ping());
    }

    public function testCanGetBalance()
    {
        try {
            $balance = $this->backend->getBalance();
        } catch (Sms_Backend_Exception $e) {
            $this->fail('Failed : ' . $e->getMessage());
        }
        $this->assertType('float', $balance);
    }
}
