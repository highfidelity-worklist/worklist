<?php
/**
 * Sms class
 *
 * @package Sms
 * @version $Id$
 */
require_once 'lib/Sms/Exception.php';
require_once 'lib/Sms/Backend.php';
/**
 * Sms class
 *
 * @package Sms
 */
class Sms
{
    public static $types = array(
        'email'
    );

    /**
     * Backend factory
     *
     * If type is not provided, try to find the best method.
     *
     * @param string $type
     * @return Sms_Backend
     */
    public static function createBackend($type = null, Array $options = null)
    {
        if ($type === null) {
            $type = self::getBackendType($options);
        }
        if (!in_array($type, self::$types)) {
            throw new Sms_Exception('Invalid backend type: '.$type);
        }
        $backendClass = 'Sms_Backend_' . ucfirst($type);
        if (!class_exists($backendClass)) {
            throw new Sms_Exception('Backend ' . $backendClass . ' not found.');
        }
        $backend = new $backendClass($options);
        return $backend;
    }

    /**
     * @param array $options
     * @return string backend type
     */
    protected static function getBackendType(Array $options = null)
    {
        // currently only use e-mail
        return 'email';
    }
}
