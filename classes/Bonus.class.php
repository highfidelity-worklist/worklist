<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

class Bonus
{
    public static function markPaidByList($ids, $user_paid = 0, $paid = 1, $summary = false, $fund_id = false) {
        $summaryData = array();
        foreach ($ids as $id) {
            $single_summary = self::markPaidById($id, $user_paid, $paid, true, $fund_id);
            // $summary = [receiver_id, amount]
            if (isset($summaryData[$single_summary[0]])) {
                $summaryData[$single_summary[0]][0] += $single_summary[1];
            } else {
                $summaryData[$single_summary[0]][0] = $single_summary[1];
            }
        }

        if ($summary) {
            return $summaryData;
        } else {
            return true;
        }
    }

    public static function markPaidById($id, $user_paid = 0, $paid = 1, $summary = false, $fund_id = false) {
        $id = (int) $id;
        $paid = (int) $paid;
        $user_paid = (int) $user_paid;
        $user_paid = $user_paid == 0 ? $_SESSION['userid'] : $user_paid;

        $select_query = "
            SELECT
                *
            FROM
                " . FEES . "
            WHERE
                id = {$id} AND
                AND bonus = 1";

        $select_query = mysql_fetch_array(mysql_query($select_query));

        $receiver_id = $select_query['user_id'];
        $amount      = $select_query['amount'];

        $update_query = "
            UPDATE
                " . FEES . "
            SET
                `user_paid` = {$user_paid},
                `paid` = {$paid},
                `fund_id` = {$fund_id},
                `paid_date` = NOW()
            WHERE
                id = {$id} AND
                bonus = 1
            LIMIT 1
            ';

        $result = mysql_unbuffered_query($update_query);

        if (!$result) {
            error_log("ERORR: fees table update failed! (id # ".$id);
            return false;
        }

        if ($summary) {
            return array($receiver_id, $amount);
        } else {
            return !empty($rt);
        }
    }
}
