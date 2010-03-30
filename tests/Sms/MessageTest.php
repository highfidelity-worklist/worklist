<?php
/**
 * Sms message test
 *
 * @package Sms
 * @subpackage UnitTests
 * @version $Id$
 */
/**
 * PHPUnit test case
 */
require_once 'PHPUnit/Framework/TestCase.php';
/**
 * Test helper
 */
require_once dirname(__FILE__) . '/../TestHelper.php';
/**
 * Sms lib
 */
require_once 'lib/Sms.php';
/**
 * Sms message test
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_MessageTest extends PHPUnit_Framework_TestCase
{
    public function testGetPhoneNumberReturnsUserPhone()
    {
        $userPhone = 'userPhone';

        $userStub = $this->getMock('User');
        $userStub->expects($this->any())
                 ->method('getPhone')
                 ->will($this->returnValue($userPhone));
        $message = new Sms_Message($userStub, null, null);
        $this->assertEquals($userPhone, $message->getPhoneNumber());
    }

    public function testSetSubject()
    {
        $subject = 'testSubject';
        $message = new Sms_Message('test', $subject, null);
        $this->assertEquals($subject, $message->getSubject());
    }

    public function setSetMessage()
    {
        $text = 'testMessage';
        $message = new Sms_Message('test', null, $text);
        $this->assertEquals($text, $message->getMessage());
    }
}
