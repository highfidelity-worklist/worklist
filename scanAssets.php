<?php

if (count(get_included_files()) == 1) { 
          header('HTTP/1.0 403 Forbidden');
          exit;
}

class scanAssets {

    public function scanAll() {
        error_reporting(E_ALL | E_STRICT);
        set_time_limit(15*60);
        date_default_timezone_set('UTC');
        require_once(dirname(__FILE__).'/send_email.php');
        require_once(dirname(__FILE__).'/config.php');
        $con = mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
        if (!$con) {
	        die('Could not connect: ' . mysql_error());
        }
        mysql_select_db(DB_NAME, $con);

        $scan_files = array(); 
        $sql_get_files = 'SELECT `id`, `userid`,(SELECT `username` FROM `'.USERS.'` where `id`=files.userid)
        AS `useremail`, (select `summary` from `'.WORKLIST.'` where `id`=files.workitem) AS `workitemsummary`,
        `url`, `title`, `description`  FROM `'.FILES.'` WHERE `is_scanned` = 0 LIMIT 10';
        $result = mysql_query($sql_get_files);
        // Read the file names of all the files available. 
        while ($row = mysql_fetch_assoc($result)) {
    
            $file_name  = pathinfo(parse_url($row['url'],PHP_URL_PATH),PATHINFO_BASENAME);  
            $scan_files[] = $file_name;
    
            // Get the full path and prepare it for the command line. 
            $real_path = realpath (dirname(__FILE__) .'/uploads/'. $file_name); 
            $safe_path = escapeshellarg($real_path); 
            // Reset the values. 
            $return = -1; 
            $out = '';
            $message = '';
            $subject = '';
            $cmd = '/usr/local/bin/clamscan ' . $safe_path; 
            if (!empty($safe_path) && file_exists($real_path) && filesize($real_path) > 0 ) {
                // Execute the command.  
                exec ($cmd, $out, $return);
                
                if ($return == 0) { //if clean update db 
                    $sql = 'UPDATE `'.FILES.'` SET is_scanned = 1, scan_result = 0 WHERE `id` = '. $row['id'];
                    $subject = 'Upload Report: Ok';
                    $message = '';
                } else if ($return == 1) { // If the file contains a virus send email to the user and update db. 
                    $message = 'The file {$file_name} ( '. $row['title'] . ') that you uploaded in the workitem:"' . $row['workitemsummary'] . '" was scanned and found to be containing a virus and will be quarantined. Please upload a clean copy of the file.'; 
                    $subject = 'Upload Report: Infected';
                    $sql = 'UPDATE `'.FILES.'` SET is_scanned = 1, scan_result = 1 WHERE `id` = '. $row['id'];
                } else { //unknown error
                    $sql = 'UPDATE `'.FILES.'` SET is_scanned = 1, scan_result = 2 WHERE `id` = '. $row['id'];
                    $subject = 'Upload Report: Error';
                    $message = 'The file '. $file_name .' ( '. $row['title'] .') that you uploaded in the workitem:"' . $row['workitemsummary'] . '" caused an unknown error during scanning. Please upload a clean copy of the file.'; 
                }
            } else {
                // If the file does not exist/0 bytes, mark it with a new status so we don't keep iterating on it - garth
                error_log("failed test: $safe_path " . file_exists($safe_path) . " " .filesize($safe_path));
                $sql = 'UPDATE `'.FILES.'` SET is_scanned = 4, scan_result = 0 WHERE `id` = '. $row['id'];
                $out = '<p>File->'. $safe_path .' not found.</p>';
            }
    
            mysql_query($sql) or die(mysql_error() .':'. $sql);
            
            //send mail if there's a problem
            if (!empty($message)) {
                if(!send_email($row['useremail'], $subject, $message)) {
                    error_log("cron scanAssets.php: send_email failed");
                    echo '<br>Email not sent->'.$row['useremail'];
                } else {
                    echo '<br>Email sent->'.$row['useremail'];
                }
            }
            print('<br>'. $row['title'] .' <p>'. $subject .'</p>');
            print_r($out);
        }
    } //End of method
} //End of Class

?>
