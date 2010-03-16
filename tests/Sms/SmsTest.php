<?php
/**
 * Sms test
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
 * Sms test
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_SmsTest extends PHPUnit_Framework_TestCase
{
    public function testFactoryCreatesEmailBackend()
    {
        $backend = Sms::createBackend();
        $this->assertEquals('Sms_Backend_Email', get_class($backend));
    }
}
