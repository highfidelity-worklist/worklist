<?php
/**
 * Sms class
 *
 * @package Sms
 * @version $Id$
 */
/**
 * Sms_Exception
 */
require_once 'lib/Sms/Exception.php';
/**
 * Sms_Message
 */
require_once 'lib/Sms/Message.php';
/**
 * Sms_Backend
 */
require_once 'lib/Sms/Backend.php';
/**
 * Sms class
 *
 * @package Sms
 */
class Sms
{
    public static $types = array(
        'clickatell',
        'email'
    );

    protected static $options = array();

    /**
     * Backend factory
     *
     * If type is not provided, try to find the best method.
     *
     * @param string $type
     * @return Sms_Backend
     */
    public static function createBackend(Sms_Message $message, $type = null, Array $options = array())
    {
        if (!$options) {
            $options = self::$options;
        }
        if ($type === null) {
            $type = self::getBackendType($message, $options);
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

    public static function setOptions(Array $options)
    {
        self::$options = $options;
    }

    public static function send(Sms_Message $message, Array $options = array())
    {
        foreach (self::$types as $type) {
            $backend = self::createBackend($message, $type, $options);
            if (!$backend->canSend($message)) {
                continue;
            }
            try {
                if ($backend->send($message)) {
                    return true;
                }
            } catch (Sms_Backend_Exception $e) {
            }
        }
        return false;
    }

    /**
     * @param array $options
     * @return string backend type
     */
    protected static function getBackendType(Sms_Message $message, Array $options = null)
    {
        // currently only use e-mail
        return 'email';
    }
}
