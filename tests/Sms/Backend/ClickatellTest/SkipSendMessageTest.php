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
 * Skip send message
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_Backend_ClickatellTest_SkipSendMessageTest extends PHPUnit_Framework_TestCase
{
    public function testSkipped()
    {
        $this->markTestSkipped('Skipped message sending.');
    }
}
