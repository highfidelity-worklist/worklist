<?php
/**
 * Sms backend: e-mail test
 *
 * @package Sms
 * @subpackage UnitTests
 * @version $Id$
 */
/**
 * PHPUnit
 */
require_once 'PHPUnit/Framework/TestCase.php';
/**
 * TestHelper
 */
require_once dirname(__FILE__) . '/../../TestHelper.php';
/**
 * Sms
 */
require_once 'lib/Sms.php';
/**
 * Sms backend: e-mail test
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_Backend_EmailTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sms_Backend_Email
     */
    protected $backend;

    public function setUp()
    {
        $this->backend = new Sms_Backend_Email();
    }

    public function testSetInvalidCountryThrowsException()
    {
        try {
            $this->backend->setCountry('invalid');
            $this->fail('Expecting exception.');
        } catch (Sms_Backend_Exception $e) {
        } catch (Exception $e) {
            $this->fail('Expecting Sms_Backend_Exception.');
        }
    }

    public function testSetProviderWithoutCountryThrowsException()
    {
        try {
            $this->backend->setProvider('invalid');
            $this->fail('Expecting exception.');
        } catch (Sms_Backend_Exception $e) {
        } catch (Exception $e) {
            $this->fail('Expecting Sms_Backend_Exception.');
        }
    }

    public function testMessageWithUserUtilizesSmsaddr()
    {
        $smsAddr = 'testaddr';

        $userStub = $this->getMock('User');
        $userStub->expects($this->any())
                 ->method('getSmsaddr')
                 ->will($this->returnValue($smsAddr));
        $this->backend->setUserSettings($userStub);

        $this->assertEquals($smsAddr, $this->backend->getTargetEmail());
    }
}
