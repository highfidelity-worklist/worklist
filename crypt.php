<?php 
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

if (!defined("KEY"))	define("KEY", "654321");

function vEncrypt($string, $key = KEY) {
    $result = '';
    for ($i = 1; $i <= strlen($string); $i++) {
        $char = substr($string, $i - 1, 1);
        $keychar = substr($key, ($i % strlen($key)) - 1, 1);
        $char = chr(ord($char) + ord($keychar));
        $result .= $char;
    }
    return $result;
}

function vDecrypt($string, $key = KEY) {
    $result = '';
    for ($i = 1; $i <= strlen($string); $i++) {
        $char = substr($string, $i - 1, 1);
        $keychar = substr($key, ($i % strlen($key)) - 1, 1);
        $char = chr(ord($char) - ord($keychar));
        $result .= $char;
    }
    return $result;
}
?>