<?php
//
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

require_once('html2text.php');

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
            $h2t = new html2text($html);
            $plain = $h2t->get_text();
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
