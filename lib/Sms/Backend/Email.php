<?php
/**
 * Sms backend e-mail to sms
 *
 * @package Sms
 * @version $Id$
 */
require_once 'lib/Sms/Backend.php';
require_once 'lib/Sms/Message.php';
require_once 'lib/Sms/Numberlist.php';
require_once 'Zend/Mail.php';
/**
 * Sms backend e-mail to sms
 *
 * @package Sms
 */
class Sms_Backend_Email implements Sms_Backend
{
    const TYPE = 'email';

    /**
     * @var Zend_Mail
     */
    protected $mail;

    protected $country;
    protected $provider;
    protected $target;
    protected $targetEmail;

    public function __construct(Array $options = null)
    {
        if (isset($options['mailFrom'])) {
            $this->getMail()->setFrom($options['mailFrom']);
        }
        if (isset($options['mailReplyTo'])) {
            $this->getMail()->setReplyTo($options['mailReplyTo']);
        }
    }

    public static function getType()
    {
        return self::TYPE;
    }

    /**
     * @param User $user
     * @throws Sms_Backend_Exception
     */
    public function setUserSettings(User $user)
    {
        try {
            $this->setCountry($user->getCountry())
                 ->setProvider($user->getProvider());
        } catch (Sms_Backend_Exception $e) {
            if (!$user->getSmsaddr()) {
                throw new Sms_Backend_Exception('Missing SMS address.');
            }
            $this->setTargetEmail($user->getSmsaddr());
        }
    }

    /**
     * @return array
     */
    public static function getCountries()
    {
        return array_keys(Sms_Numberlist::$providerList);
    }

    /**
     * @param string $country
     * @return array
     */
    public static function getProviders($country)
    {
        if (!isset(Sms_Numberlist::$providerList[$country])) {
            return false;
        }
        return array_keys(Sms_Numberlist::$providerList[$country]);
    }

    /**
     * @param Zend_Mail $mail
     * @return Sms_Backend_Mail
     */
    public function setMail(Zend_Mail $mail)
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * @return Zend_Mail
     */
    public function getMail()
    {
        if ($this->mail === null) {
            $this->mail = new Zend_Mail();
        }
        return $this->mail;
    }

    /**
     * @param string $country
     * @return Sms_Backend_Email
     * @throws Sms_Backend_Exception
     */
    public function setCountry($country)
    {
        if (!in_array($country, self::getCountries())) {
            throw new Sms_Backend_Exception('Invalid country.');
        }
        $this->country = $country;
        return $this;
    }

    /**
     * Sets provider and target
     *
     * @param string $provider
     * @return Sms_Backend_Email
     * @throws Sms_Backend_Exception
     */
    public function setProvider($provider)
    {
        if (!isset($this->country)) {
            throw new Sms_Backend_Exception('Missing country. Set country first.');
        }
        if (!in_array($provider, self::getProviders($this->country))) {
            throw new Sms_Backend_Exception('Invalid provider.');
        }
        $this->provider = $provider;
        $this->target   = Sms_Numberlist::$providerList[$this->country][$this->provider];
        return $this;
    }

    public function setTargetEmail($targetEmail)
    {
        $this->targetEmail = $targetEmail;
        return $this;
    }

    public function getTargetEmail()
    {
        return $this->targetEmail;
    }

    /**
     * Warning: This method also modifies the settings.
     *
     * @param Sms_Message $message
     * @return boolean
     */
    public function canSend(Sms_Message $message)
    {
        if ($user = $message->getUser()) {
            try {
                $this->setUserSettings($user);
            } catch (Sms_Backend_Exception $e) {
                return false;
            }
        }
        if ($this->target === null && $this->targetEmail === null) {
            return false;
        }
        return true;
    }

    /**
     * Sends a message.
     *
     * @param Sms_Message $message
     * @throws Sms_Backend_Exception
     * @return Sms_Backend_Email
     */
    public function send(Sms_Message $message)
    {
        if ($user = $message->getUser()) {
            $this->setUserSettings($user);
        }
        if ($this->target === null && $this->targetEmail === null) {
            throw new Sms_Backend_Exception('Missing target.');
        }
        if ($this->targetEmail === null) {
            if (!$message->getPhoneNumber()) {
                throw new Sms_Backend_Exception('Missing phone number.');
            }
            $targetEmail = str_replace('{n}', $message->getPhoneNumber(), $this->target);
        } else {
            $targetEmail = $this->targetEmail;
        }
        try {
               notify_sms_by_object($user, $message->getSubject(), $message->getMessage()) or error_log("failed to send SMS message by email");
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
