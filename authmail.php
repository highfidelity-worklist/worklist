<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

ini_set('display_errors', 0);

//Include PEAR:Mail
require_once "Mail.php";

//send_authmail(array('server'=>'localhost','sender'=>'authuser'),$to,$subject,$body,'');


function send_authmail($auth,$to,$subject,$body,$headers) {
 if (!is_array($auth) || (!empty($headers) && !is_array($headers)) || empty($to) || empty($body)) {
  error_log("failing send_auth: ".$auth." ".$headers." ".$to." ".$body);
  return false;
 }

global $mail_auth;
global $mail_user;

//set local variables for auth[server] and auth[sender] and test [0] if no match
//fail if [0] no match

error_log("sender: ".$auth['sender']);
error_log("mailuser: ".$mail_user[$auth['sender']]['from']);


if (!empty($mail_auth)) {reset($mail_auth);}
if (!empty($mail_user)) {reset($mail_user);}

if (!isset($headers['From'])) {
  if (!empty($mail_user[$auth['sender']]['from'])) {
    $headers['From'] = $mail_user[$auth['sender']]['from'];
    error_log("From defined from auth sender");
  } else if (!empty($mail_user[key($mail_user)]['from'])) {
    $headers['From'] = $mail_user[key($mail_user)]['from'];
    error_log("From defined from mail_user");
  } else {
    error_log("From not defined, exiting");
    exit;
  }
} else {
  error_log("From defined before authmail called");
}
$oldheaders="From: ".$headers['From']."\n";;

if (!isset($headers['Reply-To'])) {
  if (!empty($mail_user[$auth['sender']]['replyto'])) {
    $headers['Reply-To'] = $mail_user[$auth['sender']]['replyto'];
    $oldheaders.="Reply-To: ".$headers['Reply-To']."\n";
    error_log("Reply-To defined from auth sender");
  } else if (!empty($mail_user[key($mail_user)]['replyto'])) {
    $headers['Reply-To'] = $mail_user[key($mail_user)]['replyto'];
    $oldheaders.="Reply-To: ".$headers['Reply-To']."\n";;
    error_log("Reply-To defined from mail_user");
  } else if (!empty($headers['From'])) {
    $headers['Reply-To'] = $headers['From'];
    error_log("Reply-To copied from From");
  }
} else {
  error_log("Reply-To defined before authmail called");
  $oldheaders.="Reply-To: ".$headers['Reply-To']."\n";;
}
if (!isset($headers['Subject'])) {
  if (!empty($subject)) {
    $headers['Subject'] = $subject;
    error_log("Subject defined from argument passed to function");
  } else if (!empty($mail_user[$auth['sender']]['subject'])) {
    $headers['Subject'] = $mail_user[$auth['sender']]['subject'];
    $oldheaders.="Subject: ".$headers['Subject']."\n";;
    error_log("Subject defined from auth sender");
  } else if (!empty($mail_user[key($mail_user)]['subject'])) {
    $headers['Subject'] = $mail_user[key($mail_user)]['subject'];
    $oldheaders.="Subject: ".$headers['Subject']."\n";;
    error_log("Subject defined from mail_user");
  }
} else {
  error_log("Subject defined before authmail called");
  $oldheaders.="Subject: ".$headers['Subject']."\n";;
}
if (!isset($headers['Content-Type'])) {
  if (!empty($mail_user[$auth['sender']]['Content-Type'])) {
    $headers['Content-Type'] = $mail_user[$auth['sender']]['Content-Type'];
    $oldheaders.="Content-Type: ".$headers['Content-Type']."\n";;
    error_log("Content-Type defined from auth sender");
  } else if (!empty($mail_user[key($mail_user)]['Content-Type'])) {
    $headers['Content-Type'] = $mail_user[$auth[key($auth)]]['Content-Type'];
    $oldheaders.="Content-Type: ".$headers['Content-Type']."\n";;
    error_log("Content-Type defined from mail_user");
  }
} else {
  error_log("Content-Type defined before authmail called");
  $oldheaders.="Content-Type: ".$headers['Content-Type']."\n";;
}
  

if (isset($auth['server']) && isset($mail_auth[$auth['server']])) {
  error_log("authmail imported using mailauth=".$auth['server']);
  $smtpauth=$mail_auth[$auth['server']];
  error_log("test: ".$smtpauth['username']);
} else if (isset($mail_auth['localhost'])) {
  error_log("authmail defaulting to  mailauth=localhost");
  $smtpauth=$mail_auth['localhost'];
} else {
  $smtpauth = array ('host'=>'localhost','auth'=>false);
  error_log("using php mail");
  @mail($to,$subject,$body,$oldheaders);
  return;
}


$smtp = Mail::factory('smtp',$smtpauth);
//$mail = $smtp->send($to, $headers, $body);
@$smtp->send($to, $headers, $body);

if (PEAR::isError($mail)) {
  error_log(PEAR::isError($mail));
  error_log($mail->getMessage());
 }

}

