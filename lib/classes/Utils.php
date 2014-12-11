<?php
class Utils{

    public static $keys = array(
        "about",
        "contactway",
        "payway",
        "timezone",
        "phone",
        "country",
        "city",
        "paypal_email",
        "findus",
        "int_code",
        "bidding_notif",
        "review_notif",
        "self_notif"
    );
    public static function registerKey($key){
        return in_array($key, self::$keys);
    }

    public static function setUserSession($id, $username, $nickname, $admin) {
        $_SESSION["userid"]   = $id;
        $_SESSION["username"] = $username;
        $_SESSION["nickname"] = $nickname;
        $_SESSION["admin"]    = $admin;
        // user just logged  in, let's update the last seen date in session
        // date will be checked against db in initUserById
        $_SESSION['last_seen'] = date('Y-m-d');

        $res = mysql_query("select * from ".USERS." where id='".mysql_real_escape_string($uid)."'");
        $user_row = (($res) ? mysql_fetch_assoc($res) : null);
        if (empty($user_row)) return;

        $_SESSION['username']           = $user_row['username'];
        $_SESSION['confirm_string']     = isset($user_row['confirm_string']) ? $user_row['confirm_string'] : 0;
        $_SESSION['nickname']           = $user_row['nickname'];
        $_SESSION['timezone']           = isset($user_row['timezone']) ? $user_row['timezone'] : 0;
        $_SESSION['is_admin']           = $user_row['is_admin'];
        $_SESSION['is_runner']          = $user_row['is_runner'];
        $_SESSION['is_payer']           = isset($user_row['is_payer']) ? intval($user_row['is_payer']) : 0;
    }

    function currentPageUrl() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") { $pageURL .= "s"; }

        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    /**
     * Returns a random string
     *
     * A-Z, a-z, 0-9:
     * Functions::randomString(10, 48, 122, array(58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96))
     *
     * Default is any printable ASCII character without whitespaces
     *
     * @param int   $len  The length of the string
     * @param int   $from The ASCII decimal range start
     * @param int   $to   The ASCII decimal range stop
     * @param array $skip ASCII decimals to skip
     *
     * @return string Random string
     */
    public static function randomString($len, $from = 33, $to = 126, $skip = array()) {
        $str = '';
        $i = 0;
        while ($i < $len) {
            $dec = rand($from, $to);
            if (in_array($dec, $skip))
                continue;
            $str .= chr($dec);
            $i++;
        }
        return $str;
    }

    /**
     * Encrypts a cleartext password via the crypt() function
     *
     * @param string $clearText Cleartext password
     * @return string Encrypted password
     */
    public static function encryptPassword($clearText) {
        switch (true) {
        case (defined('CRYPT_SHA512') && CRYPT_SHA512 == 1):
            $salt = '$6$' . self::randomString(16);
            break;

        case (defined('CRYPT_SHA256') && CRYPT_SHA256 == 1):
            $salt = '$5$' . self::randomString(16);
            break;

        case (defined('CRYPT_MD5') && CRYPT_MD5 == 1):
            $salt = '$1$' . self::randomString(12);
            break;

        case (defined('CRYPT_STD_DES') && CRYPT_STD_DES == 1):
            $salt = self::randomString(
                2,
                48,
                122,
                array(58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96)
            );
            break;
        }

        return crypt($clearText, $salt);
    }

    public static function redirect($url, $app_relative = true) {
        $redirect = ($app_relative ? WORKLIST_URL : '') . $url;
        header('Location: ' . $redirect);
        die;
    }

    /**
     * International phone number validation
     */
    public static function validPhone($number) {
        $number = preg_replace('/[_()\ -]+/', '', $number);
        return preg_match('/^[0-9]{6}[0-9]+$/', $number);
    }

    public static function getStats($req = 'table', $interval = 30) {
        if( $req == 'currentlink' ) {
            $query_b = mysql_query( "SELECT status FROM ".WORKLIST." WHERE status = 'Bidding'" );
            $query_w = mysql_query( "SELECT status FROM ".WORKLIST." WHERE status = 'In Progress' or status = 'Review' or status = 'QA Ready'" );
            $count_b = mysql_num_rows( $query_b );
            $count_w = mysql_num_rows( $query_w );
            return array(
                                'count_b' => $count_b,
                                'count_w' => $count_w
                                );

        } else if( $req == 'Bidding' ) {
            $query_b = mysql_query("SELECT id FROM ".WORKLIST." WHERE status = 'Bidding' and is_internal = 0");
            $results_b = array();
            while ($row = mysql_fetch_array($query_b, MYSQL_NUM)) {
                $results_b[] = $row[0];
            }
            return $results_b;

        } else if( $req == 'current' ) {
            $query_b = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'Bidding'");
            $query_w = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'In Progress'");
            $count_b = mysql_num_rows( $query_b );
            $count_w = mysql_num_rows( $query_w );
            $res = array( $count_b, $count_w );
            return $res;

        } else if( $req == 'fees' ) {
            // Get Average Fees in last 7 days
            $query = mysql_query( "SELECT AVG(amount) FROM ".FEES." LEFT JOIN ".WORKLIST." ON
                        ".FEES.".worklist_id = ".WORKLIST.".id WHERE date > DATE_SUB(NOW(),
                        INTERVAL 7 DAY) AND status = 'Done' AND `" . FEES . "`.`withdrawn` = 0" );

            $rt = mysql_fetch_assoc( $query );
            return $rt;

        } else if( $req == 'feeslist' ) {
            // Get Fees by person in last X days
            $interval = $interval ? $interval : 30;
            $query = mysql_query("SELECT nickname, SUM(amount) as total FROM ".FEES." ".
                        "LEFT JOIN ".WORKLIST." ON ".FEES.".worklist_id = ".WORKLIST.".id ".
                        "LEFT JOIN ".USERS." ON ".FEES.".user_id = ".USERS.".id ".
                        "WHERE date >= DATE_SUB(NOW(), INTERVAL $interval DAY) AND status = 'Done' AND `" . FEES . "`.`withdrawn` = 0 ".
                        "GROUP BY user_id ORDER BY total DESC");

            $tmpList = array();
            $feeList = array();
            while ($query && ($rt = mysql_fetch_assoc($query))) {
                $tmpList[] = array($rt['nickname'], $rt['total']);
            }

            $total = 0;
            for ($i = 0; $i < count($tmpList); $i++) {
                $total += $tmpList[$i][1];
            }
            $top10 = 0;
            for ($i = 0; $i < 10 && $i < count($tmpList); $i++) {
                $top10 += $tmpList[$i][1];
                $feeList[$i] = $tmpList[$i];
                $feeList[$i][2] = number_format($tmpList[$i][1] * 100 / $total, 2);
            }
            if (count($tmpList) > 10) {
                $feeList[10] = array('Other', number_format($total - $top10, 2), number_format(($total - $top10) * 100 / $total, 2));
            }
            return $feeList;

        } else if( $req == 'table' ) {
            // Get jobs done in last 7 days
            $fees_q = mysql_query( "SELECT `".WORKLIST."`.`id`,`summary`,`nickname` as nick,
                          (SELECT SUM(`amount`) FROM `".FEES."`
                           LEFT JOIN `".BIDS."` ON `".FEES."`.`bid_id`=`".BIDS."`.id
                           LEFT JOIN `".USERS."` ON `".USERS."`.`id`=`".FEES."`.`user_id`
                           WHERE `".BIDS."`.`worklist_id`=`".WORKLIST."`.`id`
                           AND `".USERS."`.`nickname`=`nick`) AS total,
                        TIMESTAMPDIFF(SECOND,`bid_done`,NOW()) as `delta`,`user_paid`
                        FROM `".BIDS."`
                        LEFT JOIN `".USERS."` ON `".BIDS."`.`bidder_id` = `".USERS."`.`id` LEFT JOIN `".WORKLIST."`
                        ON `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id`
                        LEFT JOIN `".FEES."` ON `".FEES."`.`bid_id`=`".BIDS."`.`id`
                        WHERE `status`='Done'
                        AND `bid_done` > DATE_SUB(NOW(), INTERVAL 7 DAY)
                        AND `accepted`='1'
                        ORDER BY `delta` ASC;" );
            $fees = array();
            // Prepare json
            while( $row = mysql_fetch_assoc( $fees_q ) )    {
                $fees[] = array( $row['id'], $row['summary'], $row['nick'], $row['total'], $row['delta'], $row['user_paid'] );
            }

            return $fees;

        } else if( $req == 'runners' )  {
            // Get Top 10 runners
            $info_q = mysql_query( "SELECT nickname AS nick, (SELECT COUNT(*) FROM ".FEES." LEFT JOIN ".USERS." ON
                        ".USERS.".id = ".FEES.".user_id WHERE ".USERS.".nickname=nick AND
                        ".USERS.".is_runner=1) AS fee_no, (SELECT COUNT(*) FROM ".FEES." LEFT JOIN
                        ".USERS." ON ".USERS.".id=".FEES.".user_id LEFT JOIN ".WORKLIST." ON
                        ".WORKLIST.".id=".FEES.".worklist_id WHERE ".WORKLIST.".status='In Progress'
                        AND ".USERS.".nickname=nick) AS working_no FROM ".USERS." ORDER BY fee_no DESC" );

            $info = array();
            // Get user nicknames
            while( $row = mysql_fetch_assoc( $info_q ) )    {
                if( count( $info ) < 10 )   {
                    if( !empty( $row['nick'] ) )    {
                        $info[] = array( $row['nick'],$row['fee_no'],$row['working_no'] );
                    }
                }
            }
            return $info;

        } else if( $req == 'mechanics' ) {
            // Get Top 10 mechanics
            $info_q = mysql_query( "SELECT nickname AS nick, (SELECT COUNT(*) FROM ".BIDS." LEFT JOIN ".USERS." ON
                        ".USERS.".id = ".BIDS.".bidder_id WHERE ".USERS.".nickname=nick
                        AND `".BIDS."`.`accepted`='1') AS bid_no,
                        (SELECT COUNT(*) FROM ".WORKLIST." LEFT JOIN ".USERS." ON
                        ".WORKLIST.".mechanic_id=".USERS.".id WHERE ".USERS.".nickname=nick AND
                        ".WORKLIST.".status='In Progress') AS work_no FROM ".USERS." ORDER BY work_no DESC" );

            $info = array();
            // Get user nicknames
            while( $row = mysql_fetch_assoc( $info_q ) )    {
                if( count( $info ) < 10 )   {
                    if( !empty( $row['nick'] ) )    {
                        $info[] = array( $row['nick'],$row['bid_no'],$row['work_no'] );
                    }
                }
            }
            return $info;

        } else if( $req == 'feeadders' ) {
            // Get the top 10 fee adders
            $info_q = mysql_query( "SELECT nickname AS nick,(SELECT COUNT(*) FROM ".FEES." LEFT JOIN ".USERS." ON
                        ".USERS.".id = ".FEES.".user_id WHERE ".USERS.".nickname=nick) AS fee_no,
                        (SELECT AVG(amount) FROM ".FEES." LEFT JOIN ".USERS." ON
                        ".USERS.".id=".FEES.".user_id WHERE ".USERS.".nickname=nick) AS amount
                        FROM ".USERS." ORDER BY fee_no DESC" );

            $info = array();
            while( $row = mysql_fetch_assoc( $info_q ) )    {
                if( count( $info ) < 10 )   {
                    if( !empty( $row['nick'] ) )    {
                        $info[] = array( $row['nick'],$row['fee_no'],$row['amount'] );
                    }
                }
            }
            return $info;

        } else if( $req == 'pastdue' ) {
            // Get the top 10 mechanics with "Past due" fees
            $info_q = mysql_query( "SELECT nickname AS nick,(SELECT COUNT(*) FROM ".BIDS." LEFT JOIN ".USERS." ON
                        ".USERS.".id=".BIDS.".bidder_id LEFT JOIN ".WORKLIST." ON
                        ".WORKLIST.".id=".BIDS.".worklist_id WHERE ".USERS.".nickname=nick
                        AND ".WORKLIST.".status='In Progress' AND `".BIDS."`.`accepted`='1'
                        AND bid_done < NOW()) AS past_due
                        FROM ".USERS." ORDER BY past_due DESC" );

            $info = array();
            while( $row = mysql_fetch_assoc( $info_q ) )    {
                if( count( $info ) < 10 )   {
                    if( !empty( $row['nick'] ) )    {
                        $info[] = array( $row['nick'],$row['past_due'] );
                    }
                }
            }
            return $info;
        }
    }

    /**
     * Email sending wrapper
     */
    public static function send_email($to, $subject, $html, $plain = null, $headers = array()) {
        //Validate arguments
        $html= str_replace(array('\n\r','\r\n','\n','\r'), '<br/>', $html);
        if (empty($to) ||
            empty($subject) ||
            (empty($html) && empty($plain) ||
            !is_array($headers))) {
            error_log("attempted to send an empty or misconfigured message");
            return false;
        }

        $nameAndAddressRegex = '/(.*)<(.*)>/';
        $toIncludesNameAndAddress = preg_match($nameAndAddressRegex, $to, $toDetails);

        if ($toIncludesNameAndAddress) {
            $toName = $toDetails[1];
            $toAddress = $toDetails[2];
        } else {
            $toName = $to;
            $toAddress = $to;
        }

        // If no 'From' address specified, use default
        if (empty($headers['From'])) {
            $fromName = DEFAULT_SENDER_NAME;
            $fromAddress = DEFAULT_SENDER;
        } else {
            $fromIncludesNameAndAddress = preg_match($nameAndAddressRegex, $headers['From'], $fromDetails);
            if ($fromIncludesNameAndAddress) {
                $fromName = str_replace('"', '', $fromDetails[1]);
                $fromAddress = str_replace(' ', '-', $fromDetails[2]);
            } else {
                $fromName = $headers['From'];
                $fromAddress = str_replace(' ', '-', $headers['From']);
            }
        }

        if (!empty($html)) {
            if (empty($plain)) {
                $h2t = new Html2Text(html_entity_decode($html, ENT_QUOTES), 75);
                $plain = $h2t->convert();
            }
        } else {
            if (empty($plain)) {
                // if both HTML & Plain bodies are empty, don't send mail
                return false;
            }
        }

        $curl = new CURLHandler();
        $postArray = array(
            'from' => $fromAddress,
            'fromname' => $fromName,
            'to' => $toAddress,
            'toname' => $toName,
            'subject' => $subject,
            'html' => $html,
            'text'=> $plain,
            'api_user' => SENDGRID_API_USER,
            'api_key' => SENDGRID_API_KEY
        );

        if (!empty($headers['Reply-To'])) {
            $replyToIncludesNameAndAddress = preg_match($nameAndAddressRegex, $headers['Reply-To'], $replyToDetails);
            if ($replyToIncludesNameAndAddress) {
                $postArray['replyto'] = str_replace(' ', '-', $replyToDetails[2]);
            } else {
                $postArray['replyto'] = $headers['Reply-To'];
            }
        }
    // check for copy, using bcc since cc is not present in Sendgrid api
        if (!empty($headers['Cc'])) {
            $ccIncludesNameAndAddress = preg_match($nameAndAddressRegex, $headers['Cc'], $ccDetails);
            if ($ccIncludesNameAndAddress) {
                $postArray['bcc'] = str_replace(' ', '-', $ccDetails[2]);
            } else {
                $postArray['bcc'] = $headers['Cc'];
            }
        }

        try {
            $result = json_decode(CURLHandler::Post(SENDGRID_API_URL, $postArray));
            if ($result->message == 'error') {
                throw new Exception(implode('; ', $result->errors));
            }
        } catch(Exception $e) {
            error_log("[ERROR] Unable to send message through SendGrid API - Exception: " . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Send email using email template
     * $template - name of the template to use, for example 'confirmation'
     * $data - array of key-value replacements for template
     *
     * @todo - Marco - Include headers argument to allow ,eg, sending bcc copies
     */
    public static function sendTemplateEmail($to, $template, $data = array(), $from = false){
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
        $xtag  = !empty($replacedTemplate['X-tag']) ?
                    $replacedTemplate['X-tag'] :
                    null;

        $headers = array();
        if (!empty($xtag)) {
            $headers['X-tag'] = $xtag;
        }
        if (!empty($from)) {
            $headers['From'] = $from;
        }

        $result = null;
        foreach($recipients as $recipient){
            if (! $result = Utils::send_email($recipient, $subject, $html, $plain, $headers)) {
                error_log("Template Utils::send_email failed");
            }
        }

        return $result;
    }

    public static function sendReviewNotification($reviewee_id, $type, $oReview) {
        $review = $oReview[0]['feeRange'] . " " . $oReview[0]['review'];
        $reviewee = new User();
        $reviewee->findUserById($reviewee_id);
        $worklist_link = WORKLIST_URL;

        $to = $reviewee->getNickname() . ' <' . $reviewee->getUsername() . '>';
        $body  = "<p>" . $review . "</p>";
        $nickname = $reviewee->getNickname();
        $headers = array();
        if ($type == "new") {
            $userinfo_link = WORKLIST_URL . 'user/?id=' . $reviewee->getId();
            $headers['From'] = 'worklist<donotreply@worklist.net>';
            $subject = 'New Peer Review';
            $journal = '@' . $nickname . " received a new review: " . $review;
            $body  = '<p>Hello ' . $nickname . ',</p><br />';
            $body  .= '<p>You have received a review from one of your peers in the Worklist.</p><br />';
            $body  .= '<p>To see your current user reviews, click <a href="' . $userinfo_link . '">here</a>.</p>';
            $body  .= '<p><a href="' . $userinfo_link . '">' . $userinfo_link . '</a></p><br />';
            $body  .= '<p><a href="' . WORKLIST_URL . '"jobs>worklist' . '</a></p>';
        } else if ($type == "update") {
            $subject = "A review of you has been updated";
            $journal = "A review of @" . $nickname . " has been updated: ". $review;
        } else {
            $subject = "One of your reviews has been deleted";
            $journal = "One review of @" . $nickname . " has been deleted: ". $review;
        }

        if (!Utils::send_email($to, $subject, $body, null, $headers)) {
            error_log("Utils::sendReviewNotification: Utils::send_email failed");
        }
        Utils::systemNotification($journal);
    }

    /**
     * Takes string input and makes links where it thinks they should go
     */
    public static function linkify($url, $author = null, $bot = false, $process = true) {
        $original = $url;

        if(!$process) {
            if (mb_detect_encoding($url, 'UTF-8', true) === FALSE) {
                $url = utf8_encode($url);
            }
            return '<a href="http://' . htmlentities($url, ENT_QUOTES, "UTF-8") . '">' . htmlspecialchars($url) . '</a>';
        }

        $class = '';
        $url = html_entity_decode($url, ENT_QUOTES);
        if (preg_match("/\<a href=\"([^\"]*)\"/i", $url) == 0) {
            // modified this so that it will exclude certain characters from the end of the url
            // add to this as you see fit as I assume the list is not exhaustive
            $regexp="/((?:(?:ht|f)tps?\:\/\/|www\.)\S+[^\s\.\)\"\'])/i";
            $url=  preg_replace($regexp, DELIMITER . '<a href="$0"' . $class . '>$0</a>' . DELIMITER, $url);

            $regexp="/href=\"(www\.\S+?)\"/i";
            $url = preg_replace($regexp,'href="http://$1"', $url);
        }

        $regexp="/(href=)(.)?((www\.)\S+(\.)\S+)/i";
        $url = preg_replace($regexp,'href="http://$3"', $url);

        // Replace '#<number>' with a link to the worklist item with the same number
        $regexp = "/\#([1-9][0-9]{4})(\s|[^0-9a-z]|$)/i";
        if (!function_exists('workitemLinkPregReplaceCallback')) {
            /**
             * Checks whether a #<number> string should be taken as a workitem link or not.
             * This function is used as a callback with preg_replace_callback (see below lines)
             */
            function workitemLinkPregReplaceCallback($matches) {
                $job_id = (int) $matches[1];
                if ($job_id < 99999 && WorkItem::idExists($job_id)) {
                    return
                        DELIMITER .
                        '<a href="' . WORKLIST_URL . $job_id . '"' .
                        ' class="worklist-item" id="worklist-' . $job_id . '" >#' . $job_id . '</a>' .
                        DELIMITER . $matches[2];
                } else {
                    return $matches[0];
                }
            }
        }
        $url = preg_replace_callback($regexp,  'workitemLinkPregReplaceCallback', $url);

        // Replace '##<projectName>##' with a link to the worklist project with the same name
        // This is used in situations where the project name has a space or spaces or no space
        $regexp = "/\#\#([A-Za-z0-9_ ]+)\#\#/";
        $link = DELIMITER . '<a href="' . WORKLIST_URL . '$1">$1</a>' . DELIMITER;
        $url = preg_replace($regexp,  $link, $url);

        // Replace '##<projectName>' with a link to the worklist project with the same name
        // This is used in situations where the first space encountered is assumed to
        // be the end of the project name. Left mainly for backward compatibility.
        $regexp = "/\#\#([A-Za-z0-9_]+)/";
        $link = DELIMITER . '<a href="' . WORKLIST_URL . '$1">$1</a>' . DELIMITER;
        $url = preg_replace($regexp,  $link, $url);

        // Replace '#<nick>/<url>' with a link to the author sandbox
        $regexp="/\#([A-Za-z]+)\/(\S*)/i";
        $url = preg_replace(
            $regexp, DELIMITER .
            '<a href="https://' . SANDBOX_SERVER . '/~$1/$2" class="sandbox-item" id="sandbox-$1">$1 : $2</a>' . DELIMITER,
            $url
        );

        // Replace '<repo> v####' with a link to the SVN server
        $regexp = '/([a-zA-Z0-9]+)\s[v]([0-9_]+)/i';
        $link = DELIMITER . '<a href="' . SVN_REV_URL . '$1&rev=$2">$1 v$2</a>' . DELIMITER;
        $url = preg_replace($regexp,  $link, $url);

        // Replace '#/<url>' with a link to the author sandbox
        $regexp="/\#\/(\S*)/i";
        if (strpos(SERVER_BASE, '~') === false) {
            $url = preg_replace(
                $regexp, DELIMITER .
                '<a href="' . SERVER_BASE . '~' . $author . '/$1" class="sandbox-item" id="sandbox-$1">'.$author.' : $1</a>' . DELIMITER,
                $url
            );
        } else { // link on a sand box :
            $url = preg_replace(
                $regexp, DELIMITER .
                '<a href="' . SERVER_BASE . '/../~' . $author . '/$1" class="sandbox-item" id="sandbox-$1" >'.$author.' : $1</a>' . DELIMITER,
                $url
            );
        }

        $regexp="/\b(?<=mailto:)([A-Za-z0-9_\-\.\+])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})/i";
        if(preg_match($regexp,$url)){
            $regexp="/\b(mailto:)(?=([A-Za-z0-9_\-\.\+])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4}))/i";
            $url=preg_replace($regexp,"",$url);
        }

        $regexp = "/\b([A-Za-z0-9_\-\.\+])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})/i";
        $url = preg_replace($regexp, DELIMITER . '<a href="mailto:$0">$0</a>' . DELIMITER, $url);

        // find anything that looks like a link and add target=_blank so it will open in a new window
        $url = htmlspecialchars_decode($url);

        $url = preg_replace("/<a\s+href=\"/", "<a target=\"_blank\" href=\"" , $url);
        if (mb_detect_encoding($url, 'UTF-8', true) === FALSE) {
            $url = utf8_encode($url);
        }
        if (!$bot) {
            $url = htmlentities($url, ENT_QUOTES, "UTF-8");
        }
        $url = nl2br($url);
        $reg = '/' . DELIMITER . '.+' . DELIMITER . '/';
        if (!function_exists('workitemLinkPregReplaceCallback')) {
            function decodeDelimitedLinks($matches) {
                $result = preg_replace('/' . DELIMITER . '/', '', $matches[0]);
                return html_entity_decode($result, ENT_QUOTES);
            }
        }
        $url = preg_replace_callback($reg, 'decodeDelimitedLinks', $url);

        // mentions - @username, comments and job descriptions
        $url = preg_replace(
            '/(^|\s)@([a-zA-Z0-9][a-zA-Z0-9\-]+)/',
            '$1<a href="' . WORKLIST_URL . 'user/$2">@$2</a>',
        $url);

        return $url;
    }

    public static function validateAPIKey() {
        if(! isset($_REQUEST["api_key"])) {
            error_log("No api key defined.");
            header('HTTP/1.1 401 Unauthorized', true, 401);
            die("No api key defined.");
        } else if(strcmp($_REQUEST["api_key"],API_KEY) != 0 ) {
            error_log("Wrong api key provided.");
            header('HTTP/1.1 401 Unauthorized', true, 401);
            die("Wrong api key provided.");
        } else {
            return true;
        }
    }

    public static function checkLogin() {
        if (! Session::uid()) {
            $_SESSION = array();
            session_destroy();
            if (!empty($_POST)) {
                $request_ip = $_SERVER['REMOTE_ADDR'];
                $request_uri = $_SERVER['REQUEST_URI'];
                error_log('Possible hack attempt from ' . $request_ip . ' on: ' . $request_uri);
                error_log(json_encode($_REQUEST));
                die('You are not authorized to post to this URL. Click ' .
                    '<a href="' . SERVER_URL . '">here</a> to go to the main page. ' . "\n");
            }
            Utils::redirect('./github/login?expired=1&redir=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public static function systemNotification($message) {
        $entry = new EntryModel();
        return $entry->notify($message);
    }

    public static function relativeTime($time, $withIn = true, $justNow = true, $withAgo = true, $specific = true) {
        $secs = abs($time);
        $mins = 60;
        $hour = $mins * 60;
        $day = $hour * 24;
        $week = $day * 7;
        $month = $day * 30;
        $year = $day * 365;

        // years
        $segments = array();
        $segments['yr']   = intval($secs / $year);
        $secs %= $year;
        // month
        $segments['mnth'] = intval($secs / $month);
        $secs %= $month;
        if (!$segments['yr']) {
            $segments['day']  = intval($secs / $day);
            $secs %= $day;
            if (!$segments['mnth']) {
                $segments['hr']   = intval($secs / $hour);
                $secs %= $hour;
                if (!$segments['day']) {
                    $segments['min']  = intval($secs / $mins);
                    $secs %= $mins;
                    if (!$segments['hr'] && !$segments['min']) {
                        $segments['sec']  = $secs;
                    }
                }
            }
        }

        $relTime = '';
        foreach ($segments as $unit=>$cnt) {
            if ($segments[$unit]) {
                if (strlen($relTime)) {
                    $relTime .= ', ';
                }
                $relTime .= "$cnt $unit";
                if ($cnt > 1) {
                    $relTime .= 's';
                }
                if (!$specific) {
                    break;
                }
            }
        }
        if (!empty($relTime)) {
            return ($time < 0) ? ($withAgo ? '' : '-') . ("$relTime " . ($withAgo ? 'ago' : '')) : ($withIn ? "in $relTime" : $relTime);
        } else {
            return $justNow ? 'just now' : '';
        }
    }

    public static function formatableRelativeTime($timestamp, $detailLevel = 1) {
        $periods = array("sec", "min", "hr", "day", "week", "mnth", "yr", "decade");
        $lengths = array("60", "60", "24", "7", "4.357", "12", "10");
        $now = time();
        if(empty($timestamp)) {
            return "Unknown time";
        }
        if($now > $timestamp) {
            $difference = $now - $timestamp;
            $tense = "";
        } else {
            $difference = $timestamp - $now;
            $tense = "from now";
        }
        if ($difference == 0) {
            return "1 second ago";
        }
        $remainders = array();
        for($j = 0; $j < count($lengths); $j++) {
            $remainders[$j] = floor(fmod($difference, $lengths[$j]));
            $difference = floor($difference / $lengths[$j]);
        }
        $difference = round($difference);
        $remainders[] = $difference;
        $string = "";
        for ($i = count($remainders) - 1; $i >= 0; $i--) {
            if ($remainders[$i]) {
                $string .= $remainders[$i] . " " . $periods[$i];
                if($remainders[$i] != 1) {
                    $string .= "s";
                }
                $string .= " ";
                $detailLevel--;
                if ($detailLevel <= 0) {
                    break;
                }
            }
        }
        return $string . $tense;
    }

    /**
     * Performs a CURL request given an url and post data.
     * Returns the results.
     */
    public static function postRequest($url, $post_data, $options = array(), $curlopt_timeout = 30) {
        if (!function_exists('curl_init')) {
            error_log('Curl is not enabled.');
            return 'error: curl is not enabled.';
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $curlopt_timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (count($options)) {
            curl_setopt_array($ch, $options);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function initUserById($userid) {
        $res = mysql_query("select * from ".USERS." where id='".mysql_real_escape_string($userid)."'");
        $user_row = (($res) ? mysql_fetch_assoc($res) : null);
        if (empty($user_row)) return;

        $_SESSION['username']           = $user_row['username'];
        $_SESSION['userid']             = $user_row['id'];
        $_SESSION['confirm_string']     = $user_row['confirm_string'];
        $_SESSION['nickname']           = $user_row['nickname'];
        $_SESSION['timezone']           = $user_row['timezone'];
        $_SESSION['is_runner']          = intval($user_row['is_runner']);
        $_SESSION['is_payer']           = intval($user_row['is_payer']);

        // set the session variable for the inline message for new users before last seen is updated
        if ($user_row['last_seen'] === null) {
            $_SESSION['inlineHide'] = 0;
        } else {
            $_SESSION['inlineHide'] = 1;
        }

        $last_seen_db = substr($user_row['last_seen'], 0, 10);
        $today = date('Y-m-d');

        if ($last_seen_db != $today) {
            $res = mysql_query("UPDATE ".USERS." SET last_seen = NOW() WHERE id={$userid}");
        }
        $_SESSION['last_seen'] = $today;
    }

    public static function getTimeZoneDateTime($GMT) {
        $timezones = array(
            '-1200'=>'Pacific/Kwajalein',
            '-1100'=>'Pacific/Samoa',
            '-1000'=>'Pacific/Honolulu',
            '-0900'=>'America/Juneau',
            '-0800'=>'America/Los_Angeles',
            '-0700'=>'America/Denver',
            '-0600'=>'America/Mexico_City',
            '-0500'=>'America/New_York',
            '-0400'=>'America/Caracas',
            '-0330'=>'America/St_Johns',
            '-0300'=>'America/Argentina/Buenos_Aires',
            '-0200'=>'Atlantic/Azores',// no cities here so just picking an hour ahead
            '-0100'=>'Atlantic/Azores',
            '+0000'=>'Europe/London',
            '+0100'=>'Europe/Paris',
            '+0200'=>'Europe/Helsinki',
            '+0300'=>'Europe/Moscow',
            '+0330'=>'Asia/Tehran',
            '+0400'=>'Asia/Baku',
            '+0430'=>'Asia/Kabul',
            '+0500'=>'Asia/Karachi',
            '+0530'=>'Asia/Calcutta',
            '+0600'=>'Asia/Colombo',
            '+0700'=>'Asia/Bangkok',
            '+0800'=>'Asia/Singapore',
            '+0900'=>'Asia/Tokyo',
            '+0930'=>'Australia/Darwin',
            '+1000'=>'Pacific/Guam',
            '+1100'=>'Asia/Magadan',
            '+1200'=>'Asia/Kamchatka'
        );
        if(isset($timezones[$GMT])){
            return $timezones[$GMT];
        } else {
            return date_default_timezone_get();
        }
    }
}
