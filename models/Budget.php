<?php
/**
 * Worklist
 * Copyright (c) 2011 LoveMachine, LLc.
 * All rights reserved.
 */
if (!defined('BUDGETS'))   define('BUDGETS', 'budgets');
 
class Budget extends DataObject {
    public $id;
    public $giver_id;
    public $receiver_id;
    public $amount;
    public $reason;
    public $notes;
    public $transfer_date;
    public $source_budget_id;
    public $source_data;
    public $active;
    public $seed;
    
    public $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        $this->table_name = BUDGETS;
    }
    
    /**
     * Destructor
     */
    public function __destructor() {
        parent::__destruct();
    }

    
    /**
     * Load a review by id
     */
    public function loadById($budget_id) {
        $objectData = $this->dbFetchArray(" `id`={$budget_id} ");
        return $this->loadObject($objectData);
    }

    /**
     * Get an index for review
     */
    public function getIndex($reviewee_id) {
        $objectData = $this->dbFetchArray(" `id`={$budget_id} ");

        if (!$objectData && is_array($objectData)) {
            return null;
        }
        
        return $objectData;
    }

    public function getAllocatedFunds() {
        $allocatedFunds = -1;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `allocated` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . 
                WORKLIST . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = `' . 
                WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("WORKING", "FUNCTIONAL", "REVIEW", "COMPLETED") AND `' . 
                FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $allocatedFunds = $row['allocated'];
        }
        return $allocatedFunds;
    }

    public function getSubmittedFunds() {
        $submittedFunds = -1;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . 
                WORKLIST . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $submittedFunds = $row['submitted'];
        }
        return $submittedFunds;
    }

    public function getPaidFunds() {
        $paidFunds = -1;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . 
                WORKLIST . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $paidFunds = $row['paid'];
        }
        return $paidFunds;
    }

    public function getTransferedFunds() {
        $transferedFunds = -1;
        $sql = 'SELECT SUM(`' . BUDGETS . '`.`amount`) AS `transfered` FROM `' . BUDGETS . '` WHERE `' . 
                BUDGETS . '`.`source_budget_id` = ' . $this->id ;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $transferedFunds = $row['transfered'];
        } else {
            error_log("getTransferedFunds error:" . $sql);
        }
        return $transferedFunds;
    }

    public function getRemainingFunds() {
        $allocated = $this->getAllocatedFunds();
        $submitted = $this->getSubmittedFunds();
        $paid = $this->getPaidFunds();
        $transfered = $this->getTransferedFunds();
        //$transfered = 0;
        $remaining = $this->amount - $allocated - $submitted - $paid - $transfered;
        return $remaining;
    }
    
    public function insertNew($values) {
        return $this->dbInsert($values);
    }
}
?>
