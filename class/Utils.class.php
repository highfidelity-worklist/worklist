<?php

class Utils{
    static $keys = array(
        "about",
        "contactway",
        "payway",
        "skills",
        "timezone",
        "is_uscitizen",
        "has_w9approval",
        "phone",
        "smsaddr",
        "country",
        "provider",
        "paypal_email",
        "sms_flags",
        "findus",
        "int_code"
    );
    static function registerKey($key){
        return in_array($key, Utils::keys);
    }
}
