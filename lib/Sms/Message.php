<?php
/**
 * Sms message
 *
 * @package Sms
 * @version $Id$
 */
/**
 * User
 */
require_once 'classes/User.class.php';
/**
 * Sms message
 *
 * @package Sms
 */
class Sms_Message
{
    protected $phoneNumber;
    /**
     * @var User
     */
    protected $user;
    protected $subject;
    protected $message;

    public function __construct($recipient, $subject, $message)
    {
        $this->setRecipient($recipient)
             ->setSubject($subject)
             ->setMessage($message);
    }

    protected function setRecipient($recipient)
    {
        if ($recipient instanceof User) {
            $this->setUser($recipient);
        } else {
            $this->setPhoneNumber($recipient);
        }
        return $this;
    }

    protected function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $phoneNumber
     * @return Sms_Message
     */
    protected function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getPhoneNumber()
    {
        if (isset($this->phoneNumber)) {
            return $this->phoneNumber;
        } elseif (isset($this->user)) {
            return $this->user->getPhone();
        } else {
            return null;
        }
    }

    /**
     * @param string $subject
     * @return Sms_Message
     */
    protected function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $message
     * @return Sms_Message
     */
    protected function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
