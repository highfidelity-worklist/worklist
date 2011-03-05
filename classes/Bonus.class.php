<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

class Bonus
{
    public static function markPaidByList($ids, $paid=1, $summary=false) {
        $summaryData = array();
        foreach ($ids as $id) {
            $single_summary = self::markPaidById($id, $paid, true);
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

    public static function markPaidById($id, $paid=1, $summary=false) {
        $id = intval($id);
        $paid = intval($paid);

        $select_query = '
            SELECT
                *
            FROM
                '.BONUS_PAYMENTS.'
            WHERE
                id = '.$id.'
            ';
        $select_query = mysql_fetch_array(mysql_query($select_query));
        
        $receiver_id = $select_query['receiver_id'];
        $amount      = $select_query['amount'];

        $update_query = '
            UPDATE
                '.BONUS_PAYMENTS.'
            SET
                paid = '.$paid.'
            WHERE
                id = '.$id.'
            LIMIT 1
            ';

        $result = mysql_unbuffered_query($update_query);

        if (!$result) {
            error_log("ERORR: bonus_payments table update failed! (id # ".$id);
            return false;
        }

        if ($summary) {
            return array($receiver_id, $amount);
        } else {
            return !empty($rt);
        }
    }
}
