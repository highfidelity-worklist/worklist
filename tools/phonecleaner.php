<?php

if (php_sapi_name() != 'cli') {
    die('Can only be called from command line!');
}

$application_path = dirname(dirname(__FILE__)) . '/';
require_once ($application_path . 'config.php');

/**
 * Cleans each phone number less than 6 digits
 */
function cleanShortNumbers() {
    $min_digits = 6;
    
    try {
        echo 'Cleaning short phone numbers (less than ' . $min_digits . ' digits)...';
        $cond = "LENGTH(TRIM(u.`phone`)) < " . $min_digits;
        $sql = "
            UPDATE `" . USERS . "` u 
            SET u.`phone` = ''
            WHERE " . $cond;
        
        mysql_query($sql);
        $rows = mysql_affected_rows();
        echo ' Done!, ' . $rows . " entries processed.\n";        
    } catch(Exception $e) {
        echo ' FAILED: ' . $e->getMessage() . "\n";
    }
}

/**
 * Marks US phone numbers with at least 6 digits as verified
 */
function markUSNumbersAsVerified() {
    try {
        echo 'Updating US phone numbers as verified...';
        $cond = "int_code = 1
            AND LENGTH(u.`phone`) > 0";
        $sql = "
            UPDATE `" . USERS . "` u 
            SET u.`phone_confirm_string` = '',
                u.`phone_verified` = NOW(),
                u.`phone_rejected` = '0000-00-00 00:00:00'
            WHERE " . $cond;
        
        mysql_query($sql);
        $rows = mysql_affected_rows();
        echo ' Done!, ' . $rows . " entries processed.\n";        
    } catch(Exception $e) {
        echo ' FAILED: ' . $e->getMessage() . "\n";
    }
}

/**
 * Cleans phone numbers for non-US inactive users
 */
function cleanInactiveUsersPhones() {
    try {
        echo 'Cleaning phone numbers for inactive users outside US...';
        
        $cond = "int_code <> 1
            AND LENGTH(u.`phone`) > 0
            AND 0 = (
                SELECT COUNT(*) 
                FROM `" . FEES . "` f 
                WHERE f.`user_id`=u.`id`
                  AND f.`paid` = 1
                  AND DATEDIFF(NOW(), f.`paid_date`) <= 30
            )";
        $sql = "
            SELECT COUNT(*) AS c 
            FROM `" . USERS .  "` u
            WHERE " . $cond;

        $res = mysql_query($sql);
        $row = mysql_fetch_array($res);
        
        if ($row['c'] > 0) {
            $answer = 'n';
            echo "\n";
            echo "=======\n";
            echo "Warning: I'm about to clean " . $row['c'] . " phone numbers!\n";
            echo "=======\n";
            echo "Are you sure you want to continue? (y/n) [N]: ";
            fscanf(STDIN, '%s', $answer);
            
            if (strlen(trim($answer)) == 1 && strtolower(trim($answer)) == 'y') {
                echo "Cleaning...";
                
                // we just clean phone numbers this time
                $sql = "
                    UPDATE `" . USERS . "` u 
                    SET u.`phone` = '' 
                    WHERE " . $cond;
                
                mysql_query($sql);
                $rows = mysql_affected_rows();
                echo ' Done!, ' . $rows . " entries processed.\n";
            } else {
                echo "Aborted!\n";
            }
        } else {
            echo "no matching entries found.\n";
        }
    } catch(Exception $e) {
        echo ' FAILED: ' . $e->getMessage() . "\n";
    }
}

/**
 * Cleans phone numbers for non-US active users with unverified phones
 */
function cleanUnverifiedPhones() {
    try {
        echo 'Cleaning unverified phones for non-US active users & notifying...';
        
        $cond = "int_code <> 1
            AND LENGTH(u.`phone`) > 0
            AND (u.`phone_verified` IS NULL OR u.`phone_verified` = '0000-00-00 00:00:00')
            AND 0 < (
                SELECT COUNT(*) 
                FROM " . FEES . " f 
                WHERE f.`user_id`=u.`id`
                  AND f.`paid` = 1
                  AND DATEDIFF(NOW(), f.`paid_date`) <= 30
            )";
        $sql = "
            SELECT COUNT(*) AS c 
            FROM " . USERS .  " u
            WHERE " . $cond;
        
        $res = mysql_query($sql);
        $row = mysql_fetch_array($res);
        
        if ($row['c'] > 0) {
            $answer = 'n';
            echo "\n";
            echo "=======\n";
            echo "Warning: I'm about to clean " . $row['c'] . " phone numbers and send them an e-mail!\n";
            echo "=======\n";
            echo "Are you sure you want to continue? (y/n) [N]: ";
            fscanf(STDIN, '%s', $answer);
            if (strlen(trim($answer)) == 1 && strtolower(trim($answer)) == 'y') {
                echo "Cleaning...\n";
                
                $sql = "SELECT `id` FROM `" . USERS . "` u WHERE " . $cond;
                $res = mysql_query($sql);
                $rows = 0;
                while (($row = mysql_fetch_array($res)) !== false) {
                    $user = new User();
                    $id = $row['id'];
                    $user->findUserById($id);
                    $user->setPhone('')->save();
                    $rows++;
                    
                    $email = $user->getUsername(); if($email != 'kordero@gmail.com') continue;
                    $msg = 
                        "Dear worklister,\n\n" .
                        "We're updating the phone records on file for our active users, and that means you! If you want " .
                        "to continue receiving SMS notifications, please go to your settings page and add your phone number, " .
                        "you will receive a link you must follow to validate it. This way you'll help us know which numbers are being " .
                        "actively used.\n\n" .
                        "Remember, SMS service is available in a growing list of countries!\n\n" .
                        "See you in the worklist!\n" .
                        "--\n" .
                    send_email($email, 'Worklist phones maintenance', nl2br($msg), $msg);
                }
                echo ' Done!, ' . $rows . " entries processed\n";
            } else {
                echo "Aborted\n";
            }
        } else {
            echo "no matching entries found.\n";
        }
    } catch(Exception $e) {
        echo ' FAILED: ' . $e->getMessage() . "\n";
    }
}

/**
 * Cleans phone validating data for empty phone numbers
 * (this is the last process that should be ran after each 
 * phone number cleaning process defined above) 
 */
function cleanValidatingDataForEmptyPhones() {
    try {
        echo 'Cleaning validation data for empty phones...';
        
        $sql = "
            UPDATE " . USERS . "
            SET `phone_rejected` = '0000-00-00 00:00:00',
                `phone_verified` = '0000-00-00 00:00:00',
                `phone_confirm_string` = ''
            WHERE LENGTH(TRIM(`phone`)) = 0 OR `phone` IS NULL;";
        mysql_query($sql);
        echo ' Done!, ' . mysql_affected_rows() . " entries processed.\n";
    } catch (Exception $e) {
        echo ' FAILED: ' . $e->getMessage() . "\n";
    }
}

cleanShortNumbers();
markUSNumbersAsVerified();
cleanInactiveUsersPhones();
cleanUnverifiedPhones();
cleanValidatingDataForEmptyPhones();

echo "\nProcess done.\n";
