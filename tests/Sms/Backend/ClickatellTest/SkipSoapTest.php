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
/**
 * Skip SOAP testing
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_Backend_ClickatellTest_SkipSoapTest extends PHPUnit_Framework_TestCase
{
    public function testSkipped()
    {
        $this->markTestSkipped('Skipped clickatell SOAP testing');
    }
}