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
    protected $is_uscitizen;
    protected $has_w9approval;
    protected $is_runner;
    protected $is_payer;
    protected $is_active;
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
    /**
     * All about budget
     */
    protected $remainingFunds;
    protected $allocated;
    protected $submitted;
    protected $paid;
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
    public function findUserByNickname($nick)
    {
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

    public function findUserByPPUsername($paypal_email) {
        $paypal_email = mysql_real_escape_string((string)$paypal_email);
        $where = sprintf('`paypal_email` = "%s"', $paypal_email);
        return $this->loadUser($where);
    }

    public function isEligible() {
    
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
    public function save()
    {
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
    public function isUsCitizen()
    {
        if ((int)$this->getIs_uscitizen() === 1) {
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
        if ((int)$this->getHas_w9approval() === 1) {
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
    
    public function updateBudget($amount) {

        $this->setBudget($this->getBudget() + $amount);
        $this->save();
        
        if($this->getBudget() <= 0 ) {  // Send email
//            $payersList = User::getPayerList();
            $payerList = array();

            $payersList[] = $this;
            $runnerNickname = $this->getNickname();
            
            foreach ($payersList as $payer) {
                $subject = $runnerNickname . "'s budget depleted";
            
                $link = SECURE_SERVER_URL . "team.php?showUser=".$this->getId();;
            
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

    public function setRemainingFunds()
    {
        $sql = 'SELECT `budget` FROM `' . USERS . '` WHERE `id` = "' . $this->getId() . '";';
        $result = mysql_query($sql);
        
        $this->remainingFunds = 0;

        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);

            $allFunds = $row['budget'];

            $allocatedFunds = 0;
            $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `allocated` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("WORKING", "REVIEW", "COMPLETED") AND `' . FEES . '`.`withdrawn` != 1;';
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $allocatedFunds = $row['allocated'];
            }

            $submittedFunds = 0;
            $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1;';
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $submittedFunds = $row['submitted'];
            }
            
            $paidFunds = 0;
            $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' . WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("DONE") AND `' . FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1;';
            $result = mysql_query($sql);
            if ($result && (mysql_num_rows($result) == 1)) {
                $row = mysql_fetch_assoc($result);
                $paidFunds = $row['paid'];
            }

            $this->setAllocated($allocatedFunds);
            $this->setSubmitted($submittedFunds);
            $this->setPaid($paidFunds);

            //$this->remainingFunds = $allFunds - ($allocatedFunds + $submittedFunds + $paidFunds);
            $this->remainingFunds = $this->getBudget();
        }

        return $this;
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

    /**
     * @return the $is_uscitizen
     */
    public function getIs_uscitizen() {
        return $this->is_uscitizen;
    }

    /**
     * @param $is_uscitizen the $is_uscitizen to set
     */
    public function setIs_uscitizen($is_uscitizen) {
        $this->is_uscitizen = $is_uscitizen;
        return $this;
    }

    /**
     * @return the $has_w9approval
     */
    public function getHas_w9approval() {
        return $this->has_w9approval;
    }

    /**
     * @param $has_w9approval the $has_w9approval to set
     */
    public function setHas_w9approval($has_w9approval) {
        $this->has_w9approval = $has_w9approval;
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
        
        if (mysql_num_rows($query) > 0) {
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
        require_once "sandbox-util-class.php";
        $attempted_unixname = $unixname;
        $x = 0;
        while (SandBoxUtil::inPasswdFile($attempted_unixname) ||
               User::unixusernameExists($attempted_unixname)) {
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
            
        if (mysql_num_rows($query)) {
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
        $where = 'WHERE `confirm`= 1 AND `is_active` = 1';
        if ($active) {
           $where .= ' AND `date` > DATE_SUB(NOW(), INTERVAL 30 DAY)';
        }
        $result = mysql_query("
            SELECT `".USERS."`.`id` FROM `".USERS."` 
            LEFT JOIN (SELECT `user_id`,MAX(`paid_date`) AS `date` FROM `".FEES."` WHERE `paid_date` IS NOT NULL AND `paid` = 1 AND `withdrawn` != 1 GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
            $where ORDER BY `nickname` ASC");
        $i = (((int)$populate > 0) ? (int)1 : 0);
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            if ($populate != $row['id']) {
                $userlist[$i++] = $user->findUserById($row['id']);
            } else {
                $userlist[0] = $user->findUserById($row['id']);
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
            $sql .= '`' . $column . '` = "' . $data['values'][$index] . '"';
            $flag = true;
        }
        $sql .= ' WHERE `id` = ' . (int)$this->getId() . ';';
        $result = mysql_query($sql);
        if ($result) {
            return true;
        }
        return false;
    }
    /**
     * @return the $avatar
     */
    public function getAvatar($w = 50, $h = 50)
    {
        return SERVER_URL."thumb.php?src=".$this->picture."&h=".$h."&w=".$w."&zc=0";
    }
    
    /**
     * Retrieves the url to the avatar
     */
    public function setAvatar()
    {
        $this->avatar = SERVER_URL."thumb.php?src=".$this->picture."&h=80&w=80&zc=0";
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
    
}
