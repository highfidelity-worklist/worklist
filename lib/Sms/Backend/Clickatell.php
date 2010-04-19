<?php
/**
 * Clickatell sms backend
 *
 * @package Sms
 * @version $Id$
 */
require_once 'lib/Sms/Backend.php';
require_once 'lib/Sms/Message.php';
require_once 'lib/Sms/Numberlist.php';
/**
 * Clickatell sms backend
 *
 * @package Sms
 */
class Sms_Backend_Clickatell implements Sms_Backend
{
    const TYPE = 'clickatell';

    /**
     * @var SoapClient
     */
    protected $soap;
    protected $soapClientOptions = array();

    protected $apiWSDL;
    protected $apiLocation;
    protected $apiId;
    protected $username;
    protected $password;
    protected $sessionId;

    protected $balanceThreshold = 10;

    public function __construct(Array $options = null)
    {
        if (isset($options['clickatellApiWSDL'])) {
            $this->apiWSDL = $options['clickatellApiWSDL'];
        }
        if (isset($options['clickatellApiLocation'])) {
            $this->apiLocation = $options['clickatellApiLocation'];
        }
        if (isset($options['clickatellSoapClientOptions'])) {
            $this->soapClientOptions = $options['clickatellSoapClientOptions'];
        }
        if (isset($options['clickatellApiId'])) {
            $this->setApiId($options['clickatellApiId']);
        }
        if (isset($options['clickatellUsername'])) {
            $this->setUsername($options['clickatellUsername']);
        }
        if (isset($options['clickatellPassword'])) {
            $this->setPassword($options['clickatellPassword']);
        }
        if (isset($options['clickatellBalanceThreshold'])) {
            $this->balanceThreshold = (int)$options['clickatellBalanceThreshold'];
        }
    }

    /**
     * Determines if all necessary credentials are present
     *
     * @return boolean
     */
    public function canConnect()
    {
        if (!isset($this->apiId)) {
            return false;
        }
        if (!isset($this->apiWSDL)) {
            return false;
        }
        if (!isset($this->username)) {
            return false;
        }
        if (!isset($this->password)) {
            return false;
        }
        return true;
    }

    /**
     * @param Sms_Message $message
     * @return boolean
     */
    public function canSend(Sms_Message $message)
    {
        if (!$this->canConnect()) {
            return false;
        }
        $phone = $message->getPhoneNumber();
        if (empty($phone) && (!$user = $message->getUser() || !$this->getUserPhone($user))) {
            return false;
        }
        if ($this->getBalance() < $this->balanceThreshold) {
            return false;
        }
        return true;
    }

    public function getUserPhone(User $user)
    {
        if (!$intCode = $user->getInt_code()) {
            return false;
        }
        if (!$phone = $user->getPhone()) {
            return false;
        }
        if (strpos($phone, '0') === 0) {
            $phone = substr($phone, 1);
        }
        return $intCode . $phone;
    }

    public function send(Sms_Message $message)
    {
        if ($user = $message->getUser()) {
            if (!$to = $this->getUserPhone($user)) {
                throw new Sms_Backend_Exception('Can not determine user number.');
            }
        } else {
            $to = $message->getPhoneNumber();
        }
        $soap = $this->getSoapClient();
        $args = array(
            'session_id' => $this->getSessionId(),
            'api_id'     => null,
            'user'       => null,
            'password'   => null,
            'to'         => array($to),
            'from'       => null,
            'text'       => $message->getSubject() . ': ' . $message->getMessage()
        );
        $response = $soap->__soapCall('sendMsg', $args);
        if (is_array($response)) {
            foreach ($response as $rspLine) {
                if (substr_count($rspLine, 'ERR') > 0) {
                    throw new Sms_Backend_Exception($response);
                }
            }
        } elseif (substr_count($response, 'ERR') > 0) {
            throw new Sms_Backend_Exception($response);
        }
        return true;
    }

    public static function getType()
    {
        return self::TYPE;
    }

    public function setApiId($apiId)
    {
        $this->apiId = $apiId;
        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function getSessionId()
    {
        if ($this->sessionId === null) {
            $this->authenticate();
        }
        return $this->sessionId;
    }

    /**
     * @return SoapClient
     */
    public function getSoapClient()
    {
        if ($this->apiWSDL === null) {
            throw new Sms_Backend_Exception('Missing WSDL.');
        }
        if ($this->soap === null) {
            $this->soap = new SoapClient($this->apiWSDL, $this->soapClientOptions);
            if ($this->apiLocation) {
                $this->soap->__setLocation($this->apiLocation);
            }
        }
        return $this->soap;
    }

    public function authenticate()
    {
        $soap = $this->getSoapClient();
        $response = $soap->auth($this->apiId, $this->username, $this->password);
        if (substr_count($response, 'ERR') > 0) {
            throw new Sms_Backend_Exception($response);
        }
        $this->sessionId = str_replace('OK: ', '', $response);
        return true;
    }

    public function ping()
    {
        $sessionId = $this->getSessionId();
        $soap = $this->getSoapClient();
        $response = $soap->ping($sessionId);
        if (substr_count($response, 'ERR') > 0) {
            throw new Sms_Backend_Exception($response);
        }
        return true;
    }

    public function getBalance()
    {
        $soap = $this->getSoapClient();
        $response = $soap->getBalance($this->getSessionId());
        if (substr_count($response, 'ERR') > 0) {
            throw new Sms_Backend_Exception($response);
        }
        $matches = array();
        if (!preg_match('/^Credit: ([0-9.]+)$/', $response, $matches)) {
            throw new Sms_Backend_Exception('Unknown format: ' . $response);
        }
        return (float)$matches[1];
    }
}
