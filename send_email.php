<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

require_once('authmail.php');
require_once('html2text.inc');
require_once('smslist.php');

// email templates
require_once(dirname(__FILE__) . "/email/en.php");

//This is not using akismet any longer and defaults to php built in mechanism that will not work everywhere
/*  sl_send_email
 * 
 *  Check using Akismet if mail is probably spam, otherwise send an email 
 */
function sl_send_email($to, $subject, $html, $plain=null, $headers = '') {
    if (empty($to)) return false;

    $hash = md5(date('r', time()));
    $headers .= "From: SendLove <love@sendlove.us>\n";
    if (!empty($html)) {
        if (empty($plain)) {
            $h2t = new html2text($html, 75);
            $plain = $h2t->convert();
        }

        $headers .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-$hash\"\n";
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
        $body = $plain;
    }

	if (mail($to,$subject,$body,$headers)) {
	    return true;
	}
	return false;
}

/* sl_notify_sms functions
 *
 * Notify by user_id or by user object
 *
 */
function sl_notify_sms_by_id($user_id, $smssubject, $smsbody)
{
    //Fetch phone info using user_id
    $sql = 'SELECT phone, country, provider, smsaddr FROM '.USERS.' WHERE id = '. mysql_real_escape_string($user_id);
    $res = mysql_query($sql);
    $phone_info = mysql_fetch_object($res);
    if (is_object($phone_info))
    {
	sl_notify_sms_by_object($phone_info, $smssubject, $smsbody);
    } else {
	error_log("sl_notify_sms_by_id: Query '$sql' failed. Not sending SMS. ${smssubject} ${smsbody} Session info: ". var_export($_SESSION));
    }
}

function sl_notify_sms_by_object($user_obj, $smssubject, $smsbody)
{
    global $smslist;

    if ($user_obj->smsaddr)
    {
       $smsaddr = $user_obj->smsaddr;
    } else {
		if( !empty($user_obj->provider))	{
			if ($user_obj->provider{0} != '+') {
				$smsaddr = str_replace('{n}', $user_obj->phone, $smslist[$user_obj->country][$user_obj->provider]);
			} else {
				$smsaddr = substr($user_obj->provider, 1);
			}
		}	else	{
			return;
		}
    }

    send_authmail(array('sender'=>'smsuser', 'server'=>'gmail-ssl-smsuser'),
    					$smsaddr, strip_tags($smssubject), strip_tags($smsbody), '');
}

/*  sendTemplateEmail - send email using email template
 *  $template - name of the template to use, for example 'confirmation'
 *  $data - array of key-value replacements for template
 */ 

function sendTemplateEmail($to, $template, $data){

    $recipients = is_array($to) ? $to : array($to);
    global $emailTemplates;

    $replacedTemplate = !empty($data) ?
                        templateReplace($emailTemplates[$template], $data) :
                        $emailTemplates[$template];

    $subject = $replacedTemplate['subject'];
    $html = $replacedTemplate['body'];
    $plain = isset($replacedTemplate['plain']) ?
                $replacedTemplate['plain'] :
                null;

    $result = null;
    foreach($recipients as $recipient){
        $result = sl_send_email($recipient, $subject, $html, $plain);
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

