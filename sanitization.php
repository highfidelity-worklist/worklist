<?php

function sanitizeInput() {
    sanitizeRequestInput('notes', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('status', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    sanitizeRequestInput('summary', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('bid_fee_desc', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('invite', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    sanitizeRequestInput('skills', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('sandbox', FILTER_SANITIZE_URL, 0);
    sanitizeRequestInput('skills', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('nickname', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    sanitizeRequestInput('city', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    sanitizeRequestInput('comment', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('done_in', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    sanitizeRequestInput('bid_expires', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    sanitizeRequestInput('fee_desc', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('crfee_desc', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    // pingtask.php
    sanitizeRequestInput('msg', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    sanitizeRequestInput('withdraw_bid_reason', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
    // addproject.php
    $_REQUEST['description'] = htmlspecialchars($_REQUEST['description']);

}

function sanitizeRequestInput($fieldName, $option=0, $flags=0) {
    if(isset($_REQUEST["$fieldName"])) {
        $_REQUEST[$fieldName] = filter_var($_REQUEST[$fieldName], $option, $flags);
    } 
}
sanitizeInput();
