<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
include_once('apifunctions.php');

class Fee
{
    public static function markPaidByList($fee_ids, $user_paid=0, $paid_notes='', $paid=1) {
        $summaryData = array();
        foreach ($fee_ids as $fee_id) {
            $summary = self::markPaidById($fee_id, $user_paid, $paid_notes, $paid, true);
            if ($summary[0] != 0) {
                if (isset($summaryData[$summary[0]])) {
                    $summaryData[$summary[0]][0] += $summary[1];
                    $summaryData[$summary[0]][1] += $summary[2];
                } else {
                    $summaryData[$summary[0]][0] = $summary[1];
                    $summaryData[$summary[0]][1] = $summary[2];
                }
            }
        }

        return $summaryData;
    }

    public static function markPaidById($fee_id, $user_paid=0, $paid_notes='', $paid=1, $summary=false) {
        $fee_id = intval($fee_id);
        $user_paid = intval($user_paid);
        $user_paid = $user_paid == 0 ? $_SESSION['userid'] : $user_paid;
        $paid_notes = mysql_real_escape_string($paid_notes);
        $paid = intval($paid);
    
        $user_id = 0;
        $amount = 0;
        $points = 0;
        $query = "SELECT `user_id`, `worklist_id`, `amount`, `paid`, `expense`, `rewarder` FROM `".FEES."` WHERE `id`=$fee_id";
        $rt = mysql_query($query);
        if ($rt && ($row = mysql_fetch_assoc($rt))) {
            $query = "UPDATE `".FEES."` SET `user_paid`=$user_paid, `notes`='$paid_notes', `paid`=$paid, paid_date = NOW() WHERE `id`=$fee_id";
            $rt = mysql_query($query);

            /* Add rewarder points and log */
            if ($rt) {
                /* Don't do update reward point or budget:
                 *  1) for expenses,
                 *  2) for rewarder payments,
                 *  3) there is no real change.
                 */
                if (!$row['expense'] && !$row['rewarder'] && $paid != $row['paid']) {
                    $user_id = $row['user_id'];
                    $worklist_id = $row['worklist_id'];
                    $amount = $row['amount'];

                    /* Find the runner for this task so we can adjust their budget. */
                    $query = "SELECT `runner_id` FROM `".WORKLIST."` WHERE `id`=$worklist_id";
                    $rt = mysql_query($query);
                    if ($rt && ($row = mysql_fetch_assoc($rt))) {
                        $runner_id = $row['runner_id'];
                    } else {
                        $runner_id = 0;
                    }

                    /* If we're unmarking the fee paid, deduct the points. */
                    if ($paid == 0) {
                        $amount = $amount * -1;
                    }

                    $points = intval($amount);

                    addRewarderBalance($user_id, $amount);

                    if ($runner_id != 0) {
                        mysql_unbuffered_query("UPDATE `".USERS."` SET `budget`=`budget`-$amount WHERE `id`=$runner_id");
                    }

		            //  Auto populate rewarder with team members of this task
		             PopulateRewarderTeam($user_id, $worklist_id);
                    // do it with new Rewarder API
                }
            } else {
                return false;
            }
        }

        if ($summary) {
            return array($user_id, $amount, $points);
        } else {
            return !empty($rt);
        }
    }

}
