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
 * Send message
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_Backend_ClickatellTest_SendMessageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sms_Backend_Clickatell
     */
    protected $backend;
    /**
     * @var Sms_Message
     */
    protected $message;

    public function setUp()
    {
        $options = array(
            'clickatellApiWSDL'     => TESTS_SMS_CLICKATELL_SOAP_WSDL,
            'clickatellApiLocation' => TESTS_SMS_CLICKATELL_SOAP_LOCATION,
            'clickatellApiId'       => TESTS_SMS_CLICKATELL_SOAP_API_ID,
            'clickatellUsername'    => TESTS_SMS_CLICKATELL_SOAP_USERNAME,
            'clickatellPassword'    => TESTS_SMS_CLICKATELL_SOAP_PASSWORD,
            'clickatellSoapClientOptions' => array(
                'trace' => true
            )
        );
        $this->backend = new Sms_Backend_Clickatell($options);
        $this->message = new Sms_Message(TESTS_SMS_CLICKATELL_SOAP_PHONE, 'test', 'testmessage');
    }

    public function testCanSendMessage()
    {
        try {
            $this->assertTrue($this->backend->send($this->message));
        } catch (Sms_Backend_Exception $e) {
            $this->fail(
                'Failed : '
                . $e->getMessage()
                . "\n" . $this->backend->getSoapClient()->__getLastRequest());
        }
    }
}
