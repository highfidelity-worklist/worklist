<?php
//
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

require_once('authmail.php');
require_once('html2text.inc');
require_once('smslist.php');

/*  sl_send_email
 * 
 *  Check using Akismet if mail is probably spam, otherwise send an email 
 */
function sl_send_email($to, $subject, $html, $plain=null) {
    if (empty($to)) return false;

    $hash = md5(date('r', time()));
    $headers = "From: SendLove <love@sendlove.us>\n";
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

	mail($to,$subject,$body,$headers);

    return true;
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

