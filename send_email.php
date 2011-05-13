<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

require_once('html2text.inc');
require_once('smslist.php');

/*  send_email
 * 
 *  send email using local mail()
 */
function send_email($to, $subject, $html, $plain=null, $headers = array()) {
    //Validate arguments
    if (empty($to) || 
        empty($subject) ||
        (empty($html) && empty($plain) ||
        !is_array($headers))) {
        error_log("attempted to send an empty or misconfigured message");
        return false;
    }

    $hash = md5(date('r', time()));

    // If no 'From' address specified, use default
    if (empty($headers['From'])) { 
        $headers['From'] = DEFAULT_SENDER;
    }
    if (empty($headers['X-tag'])) {
        $headers['X-tag']='worklist';
    } else {
        $headers['X-tag'] .= ', worklist';
    }
    $headers['From'] = "Worklist <worklist@sendlove.us>";
    if (!empty($html)) {
        if (empty($plain)) {
            $h2t = new html2text($html, 75);
            $plain = $h2t->convert();
        }

        $headers["Content-Type"] = "multipart/alternative; boundary=\"PHP-alt-$hash\"";
        $body = "
--PHP-alt-$hash
Content-Type: text/plain; charset=\"iso-8859-1\"
Content-Transfer-Encoding: 7bit

".$plain."

--PHP-alt-$hash
Content-Type: text/html; charset=\"iso-8859-1\"
Content-Transfer-Encoding: 7bit

".$html."

--PHP-alt-$hash--";
    } else {
        if (empty($plain)) {
            // if both HTML & Plain bodies are empty, don't send mail
            return false;
        }
        $body = $plain;
    }

    // Implode header array into a string for mail()
    $header_string = "";
    foreach ($headers as $header=>$value) {
        $header_string .= "{$header}: {$value}\r\n";
    }
    if (mail($to,$subject,$body,$header_string,'-f'.DEFAULT_SENDER)) {
        return true;
    }
    return false;
}

/* notify_sms functions
 *
 * Notify by user_id or by user object
 *
 */
function notify_sms_by_id($user_id, $smssubject, $smsbody)
{
    //Fetch phone info using user_id
    $sql = 'SELECT 
             phone, country, provider, smsaddr 
            FROM 
              '.USERS.' 
            WHERE
             id = '. mysql_real_escape_string($user_id);

    $res = mysql_query($sql);
    $phone_info = mysql_fetch_object($res);
    if (is_object($phone_info)) {
        if (! notify_sms_by_object($phone_info, $smssubject, $smsbody) ) {
            error_log("notify_sms_by_id: notify_sms_by_object failed. Not sending SMS. ${smssubject} ${smsbody} Session info: ". var_export($_SESSION));
        } else {
            return true;
        }
    } else {
        error_log("notify_sms_by_id: Query '${sql}' failed. Not sending SMS." .
                  " ${smssubject} ${smsbody} Session info: ". var_export($_SESSION));
    }
}

function notify_sms_by_object($user_obj, $smssubject, $smsbody)
{ 
    global $smslist;
    $smssubject = strip_tags($smssubject);
    $smsbody    = strip_tags($smsbody);

    if ($user_obj->getSmsaddr()) {
        $smsaddr = $user_obj->getSmsaddr();
    } else {
        $provider = $user_obj->getProvider();
        if ( !empty($provider)) {
            if ($provider{0} != '+') {
                $smsaddr = str_replace('{n}', $user_obj->getPhone(), $smslist[$user_obj->getCountry()][$provider]);
            } else {
                $smsaddr = substr($provider, 1);
            }
        } else {
            return false;
        }
    }


    return send_email($smsaddr, 
        $smssubject,
        '',
        $smsbody,
        array(
            "From" => SMS_SENDER,
            "X-tag" => 'sms',
    ));
}

/*  sendTemplateEmail - send email using email template
 *  $template - name of the template to use, for example 'confirmation'
 *  $data - array of key-value replacements for template
 */ 

function sendTemplateEmail($to, $template, $data){
 include (dirname(__FILE__) . "/email/en.php");

    $recipients = is_array($to) ? $to : array($to);

    $replacedTemplate = !empty($data) ?
                        templateReplace($emailTemplates[$template], $data) :
                        $emailTemplates[$template];

    $subject = $replacedTemplate['subject'];
    $html = $replacedTemplate['body'];
    $plain = !empty($replacedTemplate['plain']) ?
                $replacedTemplate['plain'] :
                null;
    $xtag = !empty($replacedTemplate['X-tag']) ?
                $replacedTemplate['X-tag'] :
                null;
    if (!empty($xtag)) {
        $headers=array('X-tag',$xtag);
    } else {
        $headers=array();
    }

    $result = null;
    foreach($recipients as $recipient){
        $headers['To']="<$recipient>";
        if (! $result = send_email($recipient, $subject, $html, $plain,$headers)) { error_log("send_email:Template: send_email failed"); }
    }

    return $result;
}

/* templateReplace - function to replace all occurencies of 
 * {key} with value from $replacements array
 * for example: if $replacements is array('nickname' => 'John')
 * function will replace {nickname} in $templateData array with 'John'
 */

function templateReplace($templateData, $replacements){

    foreach($templateData as &$templateIndice){
        foreach($replacements as $find => $replacement){

            $pattern = array(
                        '/\{' . preg_quote($find) . '\}/',
                        '/\{' . preg_quote(strtoupper($find)) . '\}/',
                            );
            $templateIndice = preg_replace($pattern, $replacement, $templateIndice);
        }
    }

    return $templateData;
}

