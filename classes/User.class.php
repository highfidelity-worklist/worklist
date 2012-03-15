<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

//  This class handles a User if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your (at least public) methods - maybe another developer
//  needs them too.

class User {
    protected $id;
    protected $username;
    protected $password;
    protected $added;
    protected $budget;
    protected $nickname;
    protected $confirm;
    protected $confirm_string;
    protected $about;
    protected $contactway;
    protected $payway;
    protected $skills;
    protected $timezone;
    protected $w9_status;
    protected $w9_accepted;
    protected $first_name;
    protected $last_name;
    protected $is_runner;
    protected $is_payer;
    protected $is_active;
    protected $is_admin;
    protected $last_seen;
    protected $journal_nick;
    protected $is_guest;
    protected $int_code;
    protected $phone;
    protected $smsaddr;
    protected $country;
    protected $city;
    protected $provider;
    protected $has_sandbox;
    protected $unixusername;
    protected $forgot_hash;
    protected $forgot_expire;
    protected $projects;
    protected $projects_checkedout;
    protected $filter;
    protected $avatar;
    protected $annual_salary;
    protected $picture;
    protected $manager;
    protected $referred_by;
    protected $paypal;
    protected $paypal_email;
    protected $paypal_verified;
    protected $paypal_hash;
    protected $notifications;
    protected $has_W2;
    protected $findus;
    /**
     * All about budget
     */
    protected $remainingFunds;
    protected $allocated;
    protected $submitted;
    protected $paid;
    protected $transfered;
    protected $allFees;
    /**
     * With this constructor you can create a user by passing an array.
     *
     * @param array $options
     * @return User $this
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
        return $this;
    }

    /**
     * This method fetches a user by his id.
     *
     * @param (integer) $id Id
     * @return (mixed) Either the User or false.
     */
    public function findUserById($id)
    {
        $where = sprintf('`id` = %d', (int)$id);
        return $this->loadUser($where);
    }

    /**
     * This method fetches a user by his nickname.
     *
     * @param (string) $nick Nickname
     * @return (mixed) Either the User or false.
     */
    public function findUserByNickname($nick) {
        $nick = mysql_real_escape_string((string)$nick);
        $where = sprintf('`nickname` = "%s"', $nick);
        return $this->loadUser($where);
    }

    /**
     * This method fetches a user by his username.
     *
     * @param (string) $user Username
     * @return (mixed) Either the User or false.
     */
    public function findUserByUsername($user)
    {
        $user = mysql_real_escape_string((string)$user);
        $where = sprintf('`username` = "%s"', $user);
        return $this->loadUser($where);
    }

    public function findUserByPPUsername($paypal_email, $hash) {
        $paypal_email = mysql_real_escape_string((string) $paypal_email);
        $hash = mysql_real_escape_string((string) $hash);
        $where = sprintf('`paypal_email` = "%s" && `paypal_hash` = "%s"', $paypal_email, $hash);
        return $this->loadUser($where);
    }

    /**
     * TODO:
     * I'm not sure why the __get() overload isn't always being applied, but
     * the error log is showing entries like:
     * PHP Fatal error:  Call to undefined method User::isEligible() in \
     *      .../worklist/workitem.inc on line 1718
     * Determine the cause and fix properly.
     * -Alexi 2011-11-22
     */
    public function isEligible() {
        return $this->getIsEligible();
    }

    public function getIsEligible() {
        if ($this->getHas_W2()) {
            return true;
        }

        if ($this->isUsCitizen()) {
            if (! $this->isW9Approved()) {
                return false;
            }
        }
    
        if ($this->isPaypalVerified()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Use this method to update or insert a user.
     *
     * @return (boolean)
     */
    public function save() {
        if (null === $this->getId()) {
            $id = $this->insert();
            if ($id !== false) {
                $this->setId($id);
                return true;
            }
            return false;
        } else {
            return $this->update();
        }
    }

    /**
     * A method to check if this user is a US citizen.
     *
     * @return (boolean)
     */
    public function isUsCitizen() {
        if ($this->getCountry() === 'US') {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user has a W9 approval.
     *
     * @return (boolean)
     */
    public function isW9Approved() {
        if ($this->getW9_status() === 'approved') {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user is a Runner.
     *
     * @return (boolean)
     */
    public function isRunner()
    {
        if ((int)$this->getIs_runner() === 1) {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user is a payer.
     *
     * @return (boolean)
     */
    public function isPayer()
    {
        if ((int)$this->getIs_payer() === 1) {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user is active or not.
     * Attention a user can also be secured and it would return false!
     *
     * @return (boolean)
     */
    public function isActive()
    {
        if ((int)$this->getIs_active() === 1) {
            return true;
        }
        return false;
    }
    
    /**
     * Authenticates against given password
     *
     * @param string $password Cleartext password
     *
     * @throws User_Exception
     * @return boolean
     */
    public function authenticate($password) {
        if (substr($this->getPassword(), 0, 7) == '{crypt}') {
            $encrypted = substr($this->getPassword(), 7);
            return ($encrypted == crypt($password, $encrypted));
        } else {
            return (sha1($password) == $this->getPassword());
        }
    }

    /**
     * Checks if the setter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method($value);
    }

    /**
     * Checks if the getter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     * 
     * TODO: Determine if this is worth keeping
     * What value does this provide? If you try to access a property that
     * doesn't exist, you'll get an exception anyway. This also adds a layer
     * of confusion to developers who don't know that we've overridden
     * the -> operator and that they need to name their function
     * getWhatever(), but call it by a different name: $this->whatever().
     * -alexi 2011-11-22
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method();
    }

    /**
     * Automatically sets the options array
     * Array: Name => Value
     *
     * @param array $options
     * @return User $this
     */
    private function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * @return the $id
     */
    public function getId() {
        return $this->id;
    }
    
    public function getPicture() {
         return $this->picture;
    }
    
    public function setPicture($picture) {
         $this->picture = $picture;
    }

    /**
     * @param $id the $id to set
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return the $username
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param $username the $username to set
     */
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    /**
     * @return the $password
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param $password the $password to set
     */
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    /**
     * @return the $added
     */
    public function getAdded() {
        return $this->added;
    }

    /**
     * @param $added the $added to set
     */
    public function setAdded($added) {
        $this->added = $added;
        return $this;
    }

    public function getBudget() {
        return $this->budget;
    }

    public function setBudget($budget) {
        $this->budget = $budget;
        return $this;
    }
    
    public function updateBudget($amount, $budget_id = 0, $budgetDepletedMessage = true) {
        $budgetDepletedSent = false;
        $this->setBudget($this->getBudget() + $amount);
        $this->save();
        if ($budget_id > 0) {
            $budget = new Budget();
            if ($budget->loadById($budget_id) ) {
                $remainingFunds = $budget->getRemainingFunds();
                $budget->remaining = $remainingFunds;
                $budget->save("id");
                if ($remainingFunds + $amount <= 0 && $budgetDepletedMessage == true) {
                    $runnerNickname = $this->getNickname();                    
                    $subject = "Depleted - Budget " . $budget_id . " (For " . $budget->reason . ")";
                    $link = SECURE_SERVER_URL . "team.php?showUser=".$this->getId() . "&tab=tabBudgetHistory";
                    $body  = '<p>Hi ' . $runnerNickname . '</p>';
                    $body .= "<p>Budget " . $budget_id . " for " . $budget->reason . "<br/> is now depleted.</p>";
                    $body .= '<p>If your budget has gone under 0.00, you will need to ask the user who ' .
                            'granted you the Budget to close out this budget for you.</p>';
                    $body .= '<p>To go to the Team Page, click <a href="' . $link . '">here</a></p>';
                    $body .= '<p>- Worklist.net</p>';               
                    $plain  = 'Hi ' . $runnerNickname . '\n\n';
                    $plain .= "Budget " . $budget_id . " for " . $budget->reason . "\n is now depleted.\n\n";
                    $plain .= 'If your budget has gone under 0.00, you will need to ask the user who ' .
                            'granted you the Budget to close out this budget for you.\n\n';
                    $plain .= 'To go to the Team Page, click ' . $link . "\n\n";
                    $plain .= '- Worklist.net\n\n';                
                    if (!send_email($this->getUsername(), $subject, $body, $plain)) { 
                        error_log("User.class.php: send_email failed on depleted Runner warning");
                    }
                    $budgetDepletedSent = true;
                }
            } else {
                error_log("User.class.php: send_email failed on depleted budget Runner warning - invalid budget id:" . $budget_id);
            }
        }
        
        if ($this->getBudget() <= 0 && $budgetDepletedSent === false) {  // Send email
//            $payersList = User::getPayerList();
            $payerList = array();

            $payersList[] = $this;
            $runnerNickname = $this->getNickname();
            
            foreach ($payersList as $payer) {
                $subject = $runnerNickname . "'s budget depleted";
            
                $link = SECURE_SERVER_URL . "team.php?showUser=".$this->getId() . "&tab=tabBudgetHistory";
            
                $body  = '<p>Hello,</p>';
                $body .= '<p>' . $runnerNickname . '\'s current budget is now depleted.</p>';
                $body .= '<p>To go to the Team Page, click <a href="' . $link . '">here</a></p>';
            
                $plain  = 'Hello,\n\n';
                $plain .= $runnerNickname . '\'s current budget is now depleted.\n\n';
                $plain .= 'To go to the Team Page, click ' . $link . "\n\n";
                        
                if (! send_email($payer->getUsername(), $subject, $body, $plain)) { 
                    error_log("signup.php: send_email failed on depleted Runner warning");
                }
            }
        
        }
        
    }

    public function getRemainingFunds()
    {
        if (null === $this->remainingFunds) {
            $this->setRemainingFunds();
        }
        return $this->remainingFunds;
    }

    public function setRemainingFundsWithoutBudget()
    {
        $sql = 'SELECT `budget` FROM `' . USERS . '` WHERE `id` = "' . $this->getId() . '";';
        $result = mysql_query($sql);
        
        $this->remainingFunds = 0;
        $remaining = null;

        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);

            $allFunds = $row['budget'];

            $allocatedFunds = 0;
            $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `allocated` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . 
                    WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . 
                    WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("WORKING", "FUNCTIONAL", "REVIEW", "COMPLETED") AND `' . 
                    FEES . '`.`withdrawn` != 1;';
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $allocatedFunds = $row['allocated'];
            }

            $submittedFunds = 0;
            $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . 
                    WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                    '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1;';
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $submittedFunds = $row['submitted'];
            }
            
            $paidFunds = 0;
            $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . 
                    WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                    '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1;';
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $paidFunds = $row['paid'];
            }
            
            $transferedFunds = 0;
            $sql = 'SELECT SUM(`' . BUDGETS . '`.`amount`) AS `transfered` FROM `' . BUDGETS . '` WHERE `' . 
                    BUDGETS . '`.`giver_id` = ' . $this->getId();
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $transferedFunds = $row['transfered'];
            }
            
            $receivedFunds = 0;
            $sql = 'SELECT SUM(`' . BUDGETS . '`.`amount`) AS `received` FROM `' . BUDGETS . '` WHERE `' . 
                    BUDGETS . '`.`receiver_id` = ' . $this->getId();
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $receivedFunds = $row['received'];
            }

            $this->setAllocated($allocatedFunds);
            $this->setSubmitted($submittedFunds);
            $this->setPaid($paidFunds);
            $this->setTransfered($transferedFunds);
            $remaining = $receivedFunds - $allocatedFunds - $submittedFunds - $paidFunds - $transferedFunds;
            $this->remainingFunds = $this->getBudget();
        }

        return $remaining;
    }


    public function setRemainingFunds()
    {
        
        $this->remainingFunds = 0;
        $remaining = null;
        $remainingFunds = 0;

        $budget_filter = " AND " . WORKLIST . ".budget_id > 0 AND " . BUDGETS . ".id = " . WORKLIST . ".budget_id AND " .
            BUDGETS . ".active = 1  ";

        $allocatedFunds = 0;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `allocated` FROM `' . FEES . '`, `' . WORKLIST . '`, `' . BUDGETS . '` WHERE `' . 
                WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . 
                WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("WORKING", "FUNCTIONAL", "REVIEW", "COMPLETED") AND `' . 
                FEES . '`.`withdrawn` != 1 ' . $budget_filter;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $allocatedFunds = $row['allocated'];
        }

        $submittedFunds = 0;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '`, `' . WORKLIST . '`, `' . BUDGETS . '` WHERE `' . 
                WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1 ' . $budget_filter;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $submittedFunds = $row['submitted'];
        }
        
        $paidFunds = 0;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '`, `' . WORKLIST . '`, `' . BUDGETS . '` WHERE `' . 
                WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1 ' . $budget_filter;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $paidFunds = $row['paid'];
        }
        
        $transferedFunds = 0;
        $sql = 'SELECT SUM(`' . BUDGETS . '`.`amount`) AS `transfered` FROM `' . BUDGETS . '` WHERE `' . 
                BUDGETS . '`.`giver_id` = ' . $this->getId() . " AND " . BUDGETS . ".active = 1  ";
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $transferedFunds = $row['transfered'];
        }
        
        $receivedFunds = 0;
        $sql = 'SELECT SUM(`' . BUDGETS . '`.`amount`) AS `received` FROM `' . BUDGETS . '` WHERE `' . 
                BUDGETS . '`.`receiver_id` = ' . $this->getId() . " AND " . BUDGETS . ".active = 1  ";
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $receivedFunds = $row['received'];
        }
        
        $remainingFunds = 0;
        $sql = 'SELECT SUM(`' . BUDGETS . '`.`remaining`) AS `remaining` FROM `' . BUDGETS . '` WHERE `' . 
                BUDGETS . '`.`receiver_id` = ' . $this->getId() . " AND " . BUDGETS . ".active = 1  ";
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $remainingFunds = $row['remaining'];
        }

        $this->setAllocated($allocatedFunds);
        $this->setSubmitted($submittedFunds);
        $this->setPaid($paidFunds);
        $this->setTransfered($transferedFunds);
        $remaining = $receivedFunds - $allocatedFunds - $submittedFunds - $paidFunds - $transferedFunds;
        $this->remainingFunds = $this->getBudget();

        return $remainingFunds;
    }

    public function getTransfered()
    {
        if (null === $this->transfered) {
            $this->setTransfered(0);
        }
        return $this->transfered;
    }

    public function getAllocated()
    {
        if (null === $this->allocated) {
            $this->setAllocated(0);
        }
        return $this->allocated;
    }

    public function setAllocated($value)
    {
        $this->allocated = $value;
        return $this;
    }

    public function setTransfered($value)
    {
        $this->transfered = $value;
        return $this;
    }

    public function getSubmitted()
    {
        if (null === $this->submitted) {
            $this->setSubmitted(0);
        }
        return $this->submitted;
    }

    public function setSubmitted($value)
    {
        $this->submitted = $value;
        return $this;
    }

    public function getPaid()
    {
        if (null === $this->paid) {
            $this->setPaid(0);
        }
        return $this->paid;
    }

    public function setPaid($value)
    {
        $this->paid = $value;
        return $this;
    }
    

    public function getTotalGiven()
    {
        $sql = 'SELECT SUM(`amount`) AS `given`
                FROM `' . BUDGETS . '`
                WHERE `receiver_id` = ' . $this->getId() . ' AND `giver_id` = ' . $_SESSION['userid'] . ';';

        $res = mysql_query($sql);
            if ($res && $row = mysql_fetch_assoc($res)) {
                return $row['given'];
            }
        return false;
    }

    public function getLastGiven()
    {
        $sql = 'SELECT `amount`, DATE_FORMAT(`transfer_date`, "%M %d, %Y") AS `date` 
                FROM `' . BUDGETS . '` 
                WHERE `receiver_id` = ' . $this->getId() . ' AND `giver_id` = ' . $_SESSION['userid'] . '
                ORDER BY `transfer_date` DESC LIMIT 1;';
        $res = mysql_query($sql);
            if ($res && $row = mysql_fetch_assoc($res)) {
                return $row['amount'];
            }
       return false;
    }
    
    public function getMaxDate()
    {
        $sql = 'SELECT DATE_FORMAT(`transfer_date`, "%M %d, %Y") AS `date` 
                FROM `' . BUDGETS . '`
                WHERE `receiver_id` = ' . $this->getId() . ' AND `giver_id` = ' . $_SESSION['userid'] . '
                ORDER BY `date` DESC LIMIT 1;';
        $res = mysql_query($sql);
            if ($res && $row = mysql_fetch_assoc($res)) {
                return $row['date'];
            }
        return false;
    }

    public function getReason()
    {
        $sql = 'SELECT `reason` 
                FROM `' . BUDGETS . '`
                WHERE `receiver_id` = ' . $this->getId() . ' AND `giver_id` = '.$_SESSION['userid'].'
                ORDER BY `transfer_date` DESC LIMIT 1;';
        $res = mysql_query($sql);
            if ($res && $row = mysql_fetch_assoc($res)) {
                return $row['reason'];
            }
        return false;
    }

    public function getBudgetCombo($budget_id = 0)
    {
// Query to get User's Budget entries
        $query =  ' SELECT amount, remaining, reason, id '
                . ' FROM ' . BUDGETS 
                . ' WHERE receiver_id = ' . $_SESSION['userid']
                . ' AND active = 1 '
                . ' ORDER BY id DESC ';
        $result = mysql_query($query);
        $ret = "";
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                if (isset($budget_id) && $budget_id == $row['id']) {
                    $selected = "selected='selected'";
                } else {
                    $selected = "";
                }
                $ret .= '<option value="' . $row['id'] . '" ' . $selected . ' data-amount="' . $row['remaining'] . '">' . 
                        $row['id'] . "|" . $row['reason'] . "|" . $row['remaining'] . '</option>\n';
            }
        }
        return $ret;
    }
   

    /**
     * @return the $nickname
     */
    public function getNickname() {
        return $this->getSubNickname($this->nickname);
    }

    /**
     * @param $nickname the $nickname to set
     */
    public function setNickname($nickname) {
        $this->nickname = $nickname;
        return $this;
    }

    /**
     * @return the $nickname
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * @param $nickname the $nickname to set
     */
    public function setCity($city) {
        $this->city = $city;
        return $this;
    }

    /**
     * @return the $confirm
     */
    public function getConfirm() {
        return $this->confirm;
    }

    /**
     * @param $confirm the $confirm to set
     */
    public function setConfirm($confirm) {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * @return the $confirm_string
     */
    public function getConfirm_string() {
        return $this->confirm_string;
    }

    /**
     * @param $confirm_string the $confirm_string to set
     */
    public function setConfirm_string($confirm_string) {
        $this->confirm_string = $confirm_string;
        return $this;
    }

    public function getForgot_hash() {
        return $this->forgot_hash;
    }

    /**
     * @param $token
     */
    public function setForgot_hash($token) {
        $this->forgot_hash = $token;
        return $this;
    }

    /**
     * @return the $about
     */
    public function getAbout() {
        return $this->about;
    }

    /**
     * @param $about the $about to set
     */
    public function setAbout($about) {
        $this->about = $about;
        return $this;
    }
    
    /**
     * @return the $findus
     */
    public function getFindus() {
        return $this->findus;
    }

    /**
     * @param $findus to set
     */
    public function setFindus($findus) {
        $this->findus = $findus;
        return $this;
    }

    /**
     * @return the $contactway
     */
    public function getContactway() {
        return $this->contactway;
    }

    /**
     * @param $contactway the $contactway to set
     */
    public function setContactway($contactway) {
        $this->contactway = $contactway;
        return $this;
    }

    /**
     * @return the $payway
     */
    public function getPayway() {
        return $this->payway;
    }

    /**
     * @param $payway the $payway to set
     */
    public function setPayway($payway) {
        $this->payway = $payway;
        return $this;
    }

    /**
     * @return the $skills
     */
    public function getSkills() {
        return $this->skills;
    }

    /**
     * @param $skills the $skills to set
     */
    public function setSkills($skills) {
        $this->skills = $skills;
        return $this;
    }

    /**
     * @return the $timezone
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * @param $timezone the $timezone to set
     */
    public function setTimezone($timezone) {
        $this->timezone = $timezone;
        return $this;
    }

    public function getW9_status() {
        return $this->w9_status;
    }

    public function setW9_status($status) {
        $this->w9_status = $status;
        return $this;
    }
    
    /**
     * @return the $w9_accepted
     */
    public function getW9_accepted() {
        return $this->w9_accepted;
    }

    /**
     * @param $w9_accepted the $w9_accepted to set
     */
    public function setW9_accepted($w9_accepted) {
        $this->w9_accepted = $w9_accepted;
        return $this;
    }
    
    /**
     * @return the $first_name
     */
    public function getFirst_name() {
        return $this->first_name;
    }

    /**
     * @param $first_name the $first_name to set
     */
    public function setFirst_name($first_name) {
        $this->first_name = $first_name;
        return $this;
    }    

    /**
     * @return the $last_name
     */
    public function getLast_name() {
        return $this->last_name;
    }

    /**
     * @param $last_name the $last_name to set
     */
    public function setLast_name($last_name) {
        $this->last_name = $last_name;
        return $this;
    } 

    /**
     * @return the $is_active
     */
    public function getIs_active() {
        return $this->is_active;
    }

    /**
     * @param $is_active the $is_active to set
     */
    public function setIs_active($is_active) {
        $this->is_active = $is_active;
        return $this;
    }
    
    public function getLast_seen() {
        return $this->last_seen;
    }
    
    public function setLast_seen($last_seen) {
        $this->last_seen = $last_seen;
        return $this;
    }
    
    public function getTimeLastSeen() {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), `last_seen`) AS last_seen FROM " . USERS ." WHERE id = " . $this->getId();
        $query = mysql_query($sql);
        
        if ($query && mysql_num_rows($query) > 0) {
            $row = mysql_fetch_assoc($query);
            return $row['last_seen'];
        } else {
            return false;
        }
    }

    /**
     * @return the $is_runner
     */
    public function getIs_runner() {
        return $this->is_runner;
    }

    /**
     * @param $is_runner the $is_runner to set
     */
    public function setIs_runner($is_runner) {
        $this->is_runner = $is_runner;
        return $this;
    }


    /**
     * @return the $is_admin
     */
    public function getIs_admin() {
        return $this->is_admin;
    }

    /**
     * @param $is_admin the $is_admin to set
     */
    public function setIs_admin($is_admin) {
        $this->is_admin = $is_admin;
        return $this;
    }

    public function getPaypal() {
        return $this->paypal;
    }

    public function setPaypal($paypal) {
        $this->paypal = $paypal;
        return $this;
    }

    public function getPaypal_email() {
        return $this->paypal_email;
    }

    public function setPaypal_email($paypal_email) {
        $this->paypal_email = $paypal_email;
        return $this;
    }

    public function getPaypal_hash() {
        return $this->paypal_hash;
    }

    public function setPaypal_hash($paypal_hash) {
        $this->paypal_hash = $paypal_hash;
        return $this;
    }

    public function getPaypal_verified() {
        return $this->paypal_verified;
    }
    
    public function isPaypalVerified() {
        if ((int)$this->getPaypal_verified() === 1) {
            return true;
        }
        return false;
    }

    public function setPaypal_verified($paypal_verified) {
        $this->paypal_verified = $paypal_verified;
        return $this;
    }

    /**
     * @return the $is_payer
     */
    public function getIs_payer() {
        return $this->is_payer;
    }

    /**
     * @param $is_payer the $is_payer to set
     */
    public function setIs_payer($is_payer) {
        $this->is_payer = $is_payer;
        return $this;
    }
    /**
     * @return the $unixusername
     */
    public function getAnnual_salary() {
        return $this->annual_salary;
    }

    /**
     * @param $unixusername: unix username to set
     */
    public function setAnnual_salary($annual_salary) {
        $this->annual_salary = $annual_salary;
        return $this;
    }
    /**
     * @return the $journal_nick
     */
    public function getJournal_nick() {
        return $this->journal_nick;
    }

    /**
     * @param $journal_nick the $journal_nick to set
     */
    public function setJournal_nick($journal_nick) {
        $this->journal_nick = $journal_nick;
        return $this;
    }

    /**
     * @return the $is_guest
     */
    public function getIs_guest() {
        return $this->is_guest;
    }

    /**
     * @param $is_guest the $is_guest to set
     */
    public function setIs_guest($is_guest) {
        $this->is_guest = $is_guest;
        return $this;
    }

    /**
     * @return string
     */
    public function getInt_code()
    {
        return $this->int_code;
    }

    /**
     * @param string $intCode
     * @return User
     */
    public function setInt_code($intCode)
    {
        $this->int_code = $intCode;
        return $this;
    }

    /**
     * @return the $phone
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * @param $phone the $phone to set
     */
    public function setPhone($phone) {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return the $smsaddr
     */
    public function getSmsaddr() {
        return $this->smsaddr;
    }

    /**
     * @param $smsaddr the $smsaddr to set
     */
    public function setSmsaddr($smsaddr) {
        $this->smsaddr = $smsaddr;
        return $this;
    }

    /**
     * @return the $country
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @param $country the $country to set
     */
    public function setCountry($country) {
        $this->country = $country;
        return $this;
    }

    /**
     * @return the $provider
     */
    public function getProvider() {
        return $this->provider;
    }

    /**
     * @param $provider the $provider to set
     */
    public function setProvider($provider) {
        $this->provider = $provider;
        return $this;
    }
    
    /**
     * @return the $manager
     */
    public function getManager() {
        return $this->manager;
    }
    
    /**
     * @param $manager the $manager to set
     */
    public function setManager($manager) {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return the $referrer
     */
    public function getReferred_by() {
        return $this->referred_by;
    }
    
    /**
     * @param $referred_by the $referred_by to set
     */
    public function setReferred_by($referred_by) {
        $this->referred_by = $referred_by;
        return $this;
    }
    /**
     * @return the $has_sandbox
     */
    public function getHas_sandbox() {
        return $this->has_sandbox;
    }

    /**
     * @param $sendbox_status: status of the sandbox
     */
    public function setHas_sandbox($sendbox_status) {
        $this->has_sandbox = $sendbox_status;
        return $this;
    }

    /**
     * @return the $unixusername
     */
    public function getUnixusername() {
        return $this->unixusername;
    }

    /**
     * @param $unixusername: unix username to set
     */
    public function setUnixusername($unixusername) {
        $this->unixusername = $unixusername;
        return $this;
    }

    /**
     * Given a user's chosen nickname, generate their unixusername. 
     * This is done by:
     *  - lowercasing their nickname
     *  - stripping non-alphanumeric
     *  - verifying uniqueness in passwd file & user table
     *  - if not unique, append a number :/
     *      (not the greatest, but it can be changed later)
     *
     */
    public function generateUnixUsername($nickname) {
        // lowercase
        $unixname = strtolower($nickname);

        // find alphanumeric-only parts to use as unixname
        $disallowed_characters = "/[^a-z0-9]/";
        $unixname = preg_replace($disallowed_characters, "", $unixname);

        // make sure first character is alpha character (can't start w/ a #)
        if (preg_match("/^[a-z]/", $unixname) == 0) {
            // lets not be fancy.. just prepend an "a" to their name.
            $unixname = "a".$unixname;
        }

        // append numbers to the end of the name if it's not unique
        // to both the password file AND the user table
        // Test SanboxUtil last since that could be a remote call
        require_once "sandbox-util-class.php";
        $attempted_unixname = $unixname;
        $x = 0;
        while (User::unixusernameExists($attempted_unixname) ||
               SandBoxUtil::inPasswdFile($attempted_unixname)) {
               
            $x++;
            $attempted_unixname = $unixname.$x;
        }
        $unixname = $attempted_unixname;

        return $unixname;
    }

    /**
     * @return true if the supplied username is in the database
     *
     */
    public function unixusernameExists($username) {
        $username = mysql_real_escape_string($username);
        $query_string = "
            SELECT
                id
            FROM
                ".USERS."
            WHERE
                unixusername='".$username."'";
        
        $query = mysql_query($query_string);
        
        if (mysql_num_rows($query) > 0) {
            return true;
        }
        return false;
    }


    /**
     * @return the $projects_checkedout
     */
    public function getProjects_checkedout() {
        $query = mysql_query("SELECT `project_id`, `checked_out` FROM `".PROJECT_USERS."`
            WHERE `user_id`=" . $this->getId() . "
            AND `checked_out` = 1");
            
        if ($query && mysql_num_rows($query)) {
            while ($row = mysql_fetch_assoc($query)) {
                $this->projects[] = $row;
            }
        
        } else {
            return null;
        }
        return $this->projects;
    }
    
    public function getProjects() {
        return $this->projects ;
    }

    /**
     * @param $projects_checkedout: projects checked out for user
     */
    public function setProjects_checkedout($projects_checkedout) {
        $this->projects_checkedout = $projects_checkedout;
        return $this;
    }

    public function isProjectCheckedOut($project_id) {
        foreach ($this->projects as $project) {
            if ($project['project_id'] == $project_id) {
                if ($project['checked_out'] == 1) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    public function checkoutProject($project_id) {
    
        $query = mysql_query("INSERT INTO `".PROJECT_USERS."` VALUES ('', ".$this->getId().", ".$project_id.", 1)");
        if ($query) {
            return mysql_insert_id();
        } else {
            return false;
        }
    
    }

    /**
     * @return the $filter
     */
    public function getFilter() {
        return $this->filter;
    }
    
    /**
     * Get a list of active users.
     * 
     * @param $active int Show only active users if 1
     * @param $populate int Populate a user by id
     * @return array Userlist
     *
     */
    public static function getUserList($populate = 0, $active = 0) {
        $where = 'WHERE `' . USERS . '`.`confirm` = 1 AND `' . USERS . '`.`is_active` > 0';
        if ($active) {
           $where .= ' AND (`dates`.`date` > DATE_SUB(NOW(), INTERVAL 30 DAY) || `'.USERS.'`.`last_seen` > DATE_SUB(NOW(), INTERVAL 30 DAY) )';
        }
        $sql = "
            SELECT `" . USERS . "`.* FROM `" . USERS . "` 
            LEFT JOIN (
                SELECT `user_id`, MAX(`paid_date`) AS `date` FROM `".FEES."` 
                WHERE `paid_date` IS NOT NULL AND `paid` = 1 AND 
                    `withdrawn` != 1 GROUP BY `user_id`
            ) AS `dates` ON `".USERS."`.id = `dates`.user_id
            $where 
            ORDER BY `nickname` ASC";
        $result = mysql_query($sql);
        $i =  (int) $populate > 0 ? (int) 1 : 0;
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            if ($populate != $row['id']) {
                $userlist[$i++] = $user->setOptions($row);
            } else {
                $userlist[0] = $user->setOptions($row);
            }
        }
        ksort($userlist);
        return ((!empty($userlist)) ? $userlist : false);
    }
    
    public static function getRunnerlist()
    {
        $runnerlist = array();
        $sql = 'SELECT `' . USERS . '`.`id` FROM `' . USERS . '` WHERE `' . USERS . '`.`is_runner` = 1;';
        $result = mysql_query($sql);
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            $runnerlist[] = $user->findUserById($row['id']);
        }
        return ((!empty($runnerlist)) ? $runnerlist : false);
    }

    public static function getPayerList() {
        $payerlist = array();
        $sql = 'SELECT `' . USERS . '`.`id` FROM `' . USERS . '` WHERE `' . USERS . '`.`is_payer` = 1;';
        $result = mysql_query($sql);
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            $payerlist[] = $user->findUserById($row['id']);
        }
        return ((!empty($payerlist)) ? $payerlist : false);
    }

    /**
     * @param $filter the $filter to set
     */
    public function setFilter($filter) {
        $this->filter = $filter;
    }

    private function loadUser($where)
    {
        // now we build the sql query
        $sql = 'SELECT * FROM `' . USERS . '` WHERE ' . $where . ' LIMIT 1;';
        // and get the result
        $result = mysql_query($sql);

        if ($result && (mysql_num_rows($result) == 1)) {
            $options = mysql_fetch_assoc($result);
            $this->setOptions($options);
            return $this;
        }
        return false;
    }

    private function getUserColumns()
    {
        $columns = array();
        $result = mysql_query('SHOW COLUMNS FROM `' . USERS);
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $columns[] = $row;
            }
            return $columns;
        }
        return false;
    }

    private function prepareData()
    {
        $columns = $this->getUserColumns();
        $cols = array(); $values = array();
        foreach ($columns as $col) {
            $method = 'get' . ucfirst($col['Field']);
            if (method_exists($this, $method) && (null !== $this->$method())) {
                $cols[] = $col['Field'];
                if (preg_match('/(char|text|blob)/i', $col['Type']) === 1) {
                    $values[] = mysql_real_escape_string($this->$method());
                } else {
                    $values[] = $this->$method();
                }
            }
        }
        return array(
            'columns' => $cols,
            'values' => $values
        );
    }

    private function insert()
    {
        $data = $this->prepareData();
        $sql = 'INSERT INTO `' . USERS . '` (`' . implode('`,`', $data['columns']) . '`) VALUES ("' . implode('","', $data['values']) . '")';
        $result = mysql_query($sql);
        if ($result) {
            return mysql_insert_id();
        }
        return false;
    }

    private function update()
    {
        $flag = false;
        $data = $this->prepareData();
        $sql = 'UPDATE `' . USERS . '` SET ';
        foreach ($data['columns'] as $index => $column) {
            if ($column == 'id') {
                continue;
            }
            if ($flag === true) {
                $sql .= ', ';
            }
            if( $column == "w9_accepted" && $data['values'][$index] == "NOW()" ){
                $sql .= '`' . $column . '` = ' . $data['values'][$index];
            } else {
                $sql .= '`' . $column . '` = "' . $data['values'][$index] . '"';
            }
            $flag = true;
        }
        $sql .= ' WHERE `id` = ' . (int)$this->getId() . ';';
        $result = mysql_query($sql);
        if ($result) {
            return true;
        }
        return false;
    }

//Garth
    /**
     * Checks to see if image exists in the cloud
     * @param string $imageName
     * @return bool
     */
    protected function imageExistsS3($imageName) {
       //Don't look for resizeds since we already looked in the db
       if (strpos($imageName,'w:')) { error_log("S3: don't look for thumbnails $imageName"); return false; }

       require_once(APP_PATH . "/lib/S3/S3.php");

       S3::setAuth(S3_ACCESS_KEY, S3_SECRET_KEY);

       try {
           if (! $result = S3::getObject(S3_BUCKET,'image/'.$imageName,false)) {
               error_log("image not found on s3");
               return false;
           } ;
           //Use to Debug S3 filecheck
           //error_log("imageExistsS3: $imageName . ".print_r($result->code,true));
           if ($result->code==200) {
               return true;
           }
           return false;
       } catch ( Exception $e ) {
           throw new Exception("imageExistsS3:getObject caught: $e imageName");
       }
    }

    /**
     * @return the $avatar
     */
    public function getAvatar($w = 50, $h = 50)
    {
        if (empty($this->picture)) {
            return SERVER_URL ."thumb.php?src=no_picture.png&h=".$h."&w=".$w."&zc=0";
        }
        if ($this->imageExistsS3($this->picture)) {
            return APP_IMAGE_URL . $this->picture;
        } else {
            return SERVER_URL ."thumb.php?src=".$this->picture."&h=".$h."&w=".$w."&zc=0";
        }
    }
    
    /**
     * Retrieves the url to the avatar
     */
    public function setAvatar()
    {
        $this->avatar = APP_IMAGE_URL . $this->picture;
        return $this;
    }
    /**
     * @return the $notifications
     */
    public function getNotifications() {
        return $this->notifications;
    }

    /**
     * @param $notifications the $notifications to set
     */
    public function setNotifications($notifications) {
        $this->notifications = $notifications;
        return $this;
    }
    
    /**
     * Return a trimmed version of the nickname
     */
    public function getSubNickname($nickname) {
        if (strlen($nickname) > 13) {
            return substr($nickname, 0, 13) . '...';
        } else {
            return $nickname;
        }
    }
    /**
     * @return the $has_W2
     */
    public function getHas_W2() {
        return $this->has_W2;
    }

    /**
     * @param $has_W2 the $has_W2 to set
     */
    public function setHas_W2($has_W2) {
        $this->has_W2 = $has_W2;
        return $this;
    }

    public function isRunnerOfWorkitem($workitem) {
        if (!is_object($workitem->getRunner())) {
            return false;
        }
        if ($this->id == 0 || $this->id != $workitem->getRunner()->getId()) {
            return false;
        }
        return true;
    }

/* Updates the current calling user status, saves it to the database and sends a message to the journal
 * @param status is the text status submitted to be udpated
 */
    public static function update_status($status = "") {
        if (isset($_SESSION['userid'])){
            if ($status != "") {
                $journal_message =  $_SESSION['nickname'] . ' is ' . $status;

            // Insert new status to the database
                $insert = "INSERT INTO " . USER_STATUS . "(id, status, timeplaced) VALUES(" . $_SESSION['userid'] . ", '" .  mysql_real_escape_string($status) . "', NOW())";
                if (!mysql_query($insert)) {
                    error_log("update_status.mysq: ".mysql_error());
                }

            //Send message to the Journal
                $journal_message = sendJournalNotification($journal_message);
                if ($journal_message != 'ok') {
                    return;
                }
            }
        }
        return;
    }
}
