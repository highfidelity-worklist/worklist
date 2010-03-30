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
 * User
 */
require_once 'classes/User.class.php';
/**
 * Sms backend: e-mail test
 *
 * @package Sms
 * @subpackage UnitTests
 */
class Sms_Backend_ClickatellTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sms_Backend_Clickatell
     */
    protected $backend;

    public function setUp()
    {
        $this->backend = new Sms_Backend_Clickatell();
    }

    public function testGetUserPhone()
    {
        $expect = '123456789';
        $intCode = '123';
        $number  = '0456789';
        $user = new User();
        $user->setInt_code($intCode)
             ->setPhone($number);
        $this->assertEquals($expect, $this->backend->getUserPhone($user));
    }
}
