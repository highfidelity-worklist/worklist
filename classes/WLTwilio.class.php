<?php

class WLTwilio {
    public function __construct() {
        if (!defined("TWILIO_SID") || !defined("TWILIO_TOKEN")) {
            throw new Exception("Twilio SID and/or Token not defined.");
        }
    }

    public function send_sms($phone,
                             $message,
                             $from_phone=TWILIO_DEFAULT_SMS_FROM) {

        try {
            if (is_numeric($phone) || (is_string($phone) && $phone[0] != '+')) {
                $phone = '+' . $phone;
            }
            $client = new Services_Twilio(TWILIO_SID, TWILIO_TOKEN);
            $message = $client->account->sms_messages->create(
              $from_phone,
              $phone,
              $message
            );
        } catch (Exception $e) {
            if (substr_count($e->getMessage(), 'not a valid phone number')) {
                return null;
            } else {
                error_log($e);
                return false;
            }
        }

        return true;
    }
}

?>
