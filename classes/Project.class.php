<?php
//  vim:ts=4:et

/** 
 * Project
 *
 * @package Project
 * @version $Id$
 */
require_once('lib/Project/Exception.php');
/**
 * Project
 *
 * @package Project
 */
class Project {
    protected $project_id;
    protected $name;
    protected $description;
    protected $budget;
    protected $repository;
    protected $contact_info;
    protected $last_commit;
    protected $active;
    protected $owner_id;
    protected $fund_id;
    protected $testflight_team_token;
    protected $logo;
    protected $cr_anyone;
    protected $cr_project_admin;
    protected $cr_3_favorites;
    protected $cr_job_runner;

    public function __construct($id = null) {
        if (!mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if (!mysql_select_db(DB_NAME)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if ($id !== null) {
            $this->load($id);
        }
    }

    static public function getById($project_id) {
        $project = new Project();
        $project->loadById($project_id);
        return $project;
    }

    public function loadById($id) {
        return $this->load($id);
    }
    
    public function loadByName($name) {
        $query = "SELECT project_id FROM `".PROJECTS."` WHERE `name`='" . $name . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result)) {
            $row = mysql_fetch_assoc($result);
            $project_id = $row['project_id'];
            $this->load($project_id);
        } else {
            throw new Project_Exception('There is no project by that name (' . $name . ')');
        }
    }

    public function loadByRepo($repo) {
        $query = "SELECT `project_id` FROM `".PROJECTS."` WHERE `repository`='" . $repo . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result)) {
            $row = mysql_fetch_assoc($result);
            $project_id = $row['project_id'];
            $this->load($project_id);
        } else {
            throw new Project_Exception('There is no project with that repository');
        }
    }

    protected function load($project_id = null) {
        if ($project_id === null && ! $this->project_id) {
            throw new Project_Exception('Missing project id.');
        } elseif ($project_id === null) {
            $project_id = $this->project_id;
        }

        $query = "
            SELECT p.project_id, p.name, p.description, p.budget, p.repository, p.contact_info, p.last_commit, p.active, p.owner_id, 
            p.fund_id, p.testflight_team_token, p.logo, p.cr_anyone, p.cr_3_favorites, p.cr_project_admin, p.cr_job_runner
            FROM  ".PROJECTS. " as p
            WHERE p.project_id = '" . (int)$project_id . "'";
        $res = mysql_query($query);

        if (!$res) {
            throw new Project_Exception('MySQL error.');
        }

        $row = mysql_fetch_assoc($res);
        if (! $row) {
            throw new Project_Exception('Invalid project id.');
        }

        $this->setProjectId($row['project_id'])
             ->setName($row['name'])
             ->setDescription($row['description'])
             ->setBudget($row['budget'])
             ->setRepository($row['repository'])
             ->setContactInfo($row['contact_info'])
             ->setLastCommit($row['last_commit'])
             ->setActive($row['active'])
             ->setTestFlightTeamToken($row['testflight_team_token'])
             ->setLogo($row['logo'])
             ->setOwnerId($row['owner_id'])
             ->setFundId($row['fund_id']);
             $this->setCrAnyone($row['cr_anyone']);
             $this->setCrFav($row['cr_3_favorites']);
             $this->setCrAdmin($row['cr_project_admin']);
             $this->setCrRunner($row['cr_job_runner']);
             
        return true;
    }

    public function idExists($project_id) {
        $query = "
            SELECT project_id
            FROM ".PROJECTS."
            WHERE project_id=".(int)$project_id;
        
        $res = mysql_query($query);
        if (!$res) {
            throw new Project_Exception('MySQL error.');
        }
        $row = mysql_fetch_row($res);
        return (boolean)$row[0];
    }

    public function setProjectId($project_id) {
        $this->project_id = (int)$project_id;
        return $this;
    }

    public function getProjectId() {
        return $this->project_id;
    }
    
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    public function getDescription() {
        return $this->description;
    }    
    
    public function setBudget($budget) {
        $this->budget = $budget;
        return $this;
    }
    
    public function getBudget() {
        return $this->budget;
    }
       
    public function setRepository($repository) {
        $this->repository = $repository;
        return $this;
    }

    public function getRepository() {
        return $this->repository;
    }            
    
    public function setContactInfo($contact_info) {
        $this->contact_info = $contact_info;
        return $this;
    }
    
    public function getContactInfo() {
        return $this->contact_info;
    }
    
    public function setLastCommit($date) {
        $this->last_commit = $date;
        return $this;
    }
    
    public function getLastCommit() {
        return $this->last_commit;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }

    public function getActive() {
        return $this->active;
    }    

    public function setOwnerId($owner_id) {
        $this->owner_id = $owner_id;
        return $this;
    }

    public function getOwnerId() {
        return $this->owner_id;
    }
    
    public function setFundId($fund_id) {
        $this->fund_id = $fund_id;
    }

    public function getFundId() {
        return $this->fund_id;
    }
    
    public function setTestFlightTeamToken($testflight_team_token) {
        $this->testflight_team_token = $testflight_team_token;
        return $this;
    }

    public function getTestFlightTeamToken() {
        return $this->testflight_team_token;
    }

    public function setLogo($logo) {
        $this->logo = $logo;
        return $this;
    }

    public function getLogo() {
        return $this->logo;
    } 

    public function setCrAnyone($cr_anyone) {
        $this->cr_anyone = $cr_anyone;
        return $this;
    }

    public function getCrAnyone() {
        return $this->cr_anyone;
    } 
    
    public function setCrFav($cr_3_favorites) {
        $this->cr_3_favorites = $cr_3_favorites;
        return $this;
    }

    public function getCrFav() {
        return $this->cr_3_favorites;
    }

    public function setCrAdmin($cr_project_admin) {
        $this->cr_project_admin = $cr_project_admin;
        return $this;
    }

    public function getCrAdmin() {
        return $this->cr_project_admin;
    }
    
    public function setCrRunner($cr_job_runner) {
        $this->cr_job_runner = $cr_job_runner;
        return $this;
    }

    public function getCrRunner() {
        return $this->cr_job_runner;
    }

    protected function insert() {
        $query = "INSERT INTO ".PROJECTS." (name, description, budget, repository, contact_info, active, owner_id, testflight_team_token,
         logo, last_commit, cr_anyone, cr_3_favorites, cr_project_admin, cr_job_runner) ".
            "VALUES (".
            "'".mysql_real_escape_string($this->getName())."', ".
            "'".mysql_real_escape_string($this->getDescription())."', ".
            "'".mysql_real_escape_string($this->getBudget())."', ".
            "'".mysql_real_escape_string($this->getRepository())."', ".
            "'".mysql_real_escape_string($this->getContactInfo())."', ".
            "'".mysql_real_escape_string($this->getActive())."', ".
            "'".mysql_real_escape_string($this->getOwnerId())."', ".
            "'".mysql_real_escape_string($this->getTestFlightTeamToken())."', ".
            "'".mysql_real_escape_string($this->getLogo())."', ".
            "NOW(), ".
            "'".intval($this->getCrAnyone())."', ".
            "'".intval($this->getCrFav())."', ".
            "'".intval($this->getCrAdmin())."', ".
            "'".intval($this->getCrRunner())."')";
        $rt = mysql_query($query);
        $this->id = mysql_insert_id();

        return $rt ? 1 : 0;
    }

    protected function update() {

        $query = "
            UPDATE ".PROJECTS." 
            SET
                name='".mysql_real_escape_string($this->getName())."',
                description='".mysql_real_escape_string($this->getDescription())."',
                budget='".mysql_real_escape_string($this->getBudget())."',
                repository='" .mysql_real_escape_string($this->getRepository())."',
                contact_info='".mysql_real_escape_string($this->getContactInfo())."',
                last_commit='".mysql_real_escape_string($this->getLastCommit())."',
                testflight_team_token='".mysql_real_escape_string($this->getTestFlightTeamToken())."',
                logo='".mysql_real_escape_string($this->getLogo())."',
                active='".intval($this->getActive())."',
                owner_id='".intval($this->getOwnerId())."',
                cr_anyone='".intval($this->getCrAnyone())."',
                cr_3_favorites='".intval($this->getCrFav())."',
                cr_project_admin='".intval($this->getCrAdmin())."',
                cr_job_runner='".intval($this->getCrRunner())."'
            WHERE project_id=" . $this->getProjectId();
        $result = mysql_query($query);
        return mysql_query($query) ? 1 : 0;
    }

    public function save() {
        if (isset($this->project_id)) {
            if ($this->idExists($this->getProjectId())) {
                return $this->update();
            } else {
                return $this->insert();
            }
        } else {
            return $this->insert();
        }
    }

/**
    Return an array of all projects containing all fields
**/    
    public function getProjects($active = false) {
    
        $where = ' ';
        if ($active) {
            $where = ' WHERE active=1 AND DATE(`last_commit`) BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND NOW() ';
        }
    
        $query = "
            SELECT *
            FROM `".PROJECTS."`" 
            . $where . "
            ORDER BY `last_commit` DESC";
        $result = mysql_query($query);

        if (mysql_num_rows($result)) {
            while ($project = mysql_fetch_assoc($result)) {
                $biddingQuery = "SELECT status FROM " . WORKLIST . " WHERE project_id = " . $project['project_id'] . 
                                " AND status = 'BIDDING'";
                $biddingQueryResult = mysql_query($biddingQuery);
                $underwayQuery = "SELECT status FROM " . WORKLIST . " WHERE project_id = " . $project['project_id'] . 
                                 " AND status IN ('WORKING', 'REVIEW', 'FUNCTIONAL')";
                $underwayQueryResult = mysql_query($underwayQuery);
                $bCount = mysql_num_rows($biddingQueryResult);
                $uCount = mysql_num_rows($underwayQueryResult);
                $project['bCount'] = $bCount;
                $project['uCount'] = $uCount;            
                $projects[$project['project_id']] = $project;
            }
            return $projects;
        }
        return false;
    }

/**
 *  Return an array of repositories
 */
    public function getRepositoryList() {
    
        $query = "
            SELECT `repository`
            FROM `".PROJECTS."`
            ORDER BY `repository`";

        $repos = array();

        $result = mysql_query($query);
        if (mysql_num_rows($result)) {
            while ($project = mysql_fetch_assoc($result)) {
                $repos[] = $project['repository'];
            }
            return $repos;
        }
        return false;
    }

/**
 * Build a project URL based on project_id
 */
    public function getProjectUrl($project_id) {
        $query = "SELECT * FROM `".PROJECTS."` WHERE `project_id`=". $project_id;
        $result = mysql_query($query);
        if (mysql_num_rows($result)) {
            $project = mysql_fetch_array($result);
            return SERVER_URL . $project['name'];
        } else {
            return false;
        }
    }

    public function getRepoUrl() {
        return SVN_BASE_URL . $this->repository;
    }

/**
 * Return project_id based on repository name
 */
    public function getIdFromRepo($repo) {
        $query = "SELECT `project_id` FROM `".PROJECTS."` WHERE `repository`='".$repo."'";
        $result = mysql_query($query);
        if (mysql_num_rows($result)) {
            $row = mysql_fetch_assoc($result);
            $project_id = $row['project_id'];
            return $project_id;
        } else {
            return false;
        }
    }

/**
  Determine if the given user_id owns the project
*/
    public function isOwner($user_id = false) {
        if ($user_id == $this->getOwnerId()) {
            return true;
        }
        
        return false;
    }

    /**
     * new function for getting roles for the project <mikewasmike 15-JUN-2011>
     * @param int $project_id
     * @return array|null
    */
    public function getRoles($project_id, $where = ''){
        $query = "SELECT * FROM `".ROLES."` WHERE `project_id`={$project_id}";
        if (!empty($where)) {
            $query .= " AND ". $where;
        }
        $result_query = mysql_query($query);
            if ($result_query) {
            $temp_array = array();
            while ($row = mysql_fetch_assoc($result_query)) {
                    $temp_array[] = $row;
            }
            return $temp_array;
        } else {
            return null;
        }
    }
    
   /**
     * new function for adding roles in the project <mikewasmike 15-JUN-2011>
     * @param int $project_id
     * @param varchar $role_title
     * @param decimal $percentage
     * @param decimal $min_amount
     * @return int|null
    */
    public function addRole($project_id,$role_title,$percentage,$min_amount){
        $query = "INSERT INTO `".ROLES."` (id,`project_id`,`role_title`,`percentage`,`min_amount`)  VALUES(NULL,'$project_id','$role_title','$percentage','$min_amount')";
        return mysql_query($query) ? mysql_insert_id() : null;
    }
    
   /**
     * new function for editing roles in the project <mikewasmike 15-JUN-2011>
     * @param int $role_id
     * @param varchar $role_title
     * @param decimal $percentage
     * @param decimal $min_amount
     * @return 1|0
    */
    public function editRole($role_id,$role_title,$percentage,$min_amount){
        $query = "UPDATE `".ROLES."` SET `role_title`='$role_title',`percentage`='$percentage',`min_amount`='$min_amount' WHERE `id`={$role_id}";
        return mysql_query($query) ? 1 : 0;
    }
    
   /**
     * new function for deleting roles in the project <mikewasmike 15-JUN-2011>
     * @param int $role_id
     * @return 1|0
    */
    public function deleteRole($role_id){
        $query = "DELETE FROM `".ROLES."`  WHERE `id`={$role_id}";
        return mysql_query($query) ? 1 : 0;
    }
    
    public function getFundName() {
        $query = "SELECT `name` FROM `" . FUNDS . "` WHERE `id` = {$this->getFundId()}";
        if ($result = mysql_query($query)) {
            $fund = mysql_fetch_assoc($result);
            return $fund['name'];
        } else {
            return false;
        }
    }
    
    public function getTotalJobs() {
        $query = "SELECT COUNT(p.project_id) AS jobCount FROM " . WORKLIST . " w 
                  LEFT JOIN " . PROJECTS . " p ON w.project_id = p.project_id  
                  WHERE w.status <> 'DRAFT' AND p.project_id = " . $this->getProjectId();
        if($result = mysql_query($query)) {
            $count = mysql_fetch_assoc($result);
            return $count['jobCount'];
        } else {
            return 0;
        }
    }
    
    public function getAvgBidFee() {
        $query = "SELECT AVG(b.bid_amount) AS avgBidFeePerProject FROM " . BIDS . " b 
                  LEFT OUTER JOIN " . WORKLIST . " w on b.worklist_id = w.id 
                  LEFT OUTER JOIN " . PROJECTS . " p on w.project_id = p.project_id 
                  WHERE p.project_id = " . $this->getProjectId() . " AND b.accepted = 1";
        if($result = mysql_query($query)) {
            $count = mysql_fetch_assoc($result);
            return $count['avgBidFeePerProject'];
        } else {
            return 0;
        }
    }
    
    public function getAvgJobTime() {
        $jobDiffSum = 0;
        $doneQuery = "SELECT w.id, s.change_date AS doneDate FROM " . STATUS_LOG . " s 
                     LEFT JOIN " . WORKLIST . " w ON s.worklist_id = w.id 
                     LEFT JOIN " . PROJECTS . " p on p.project_id = w.project_id 
                     WHERE s.status = 'DONE' AND p.project_id = " . $this->getProjectId();
        if($doneResult = mysql_query($doneQuery)) {
            $totalJobsDone = mysql_num_rows($doneResult);
            while($doneRow = mysql_fetch_assoc($doneResult)) {
                $doneDate = $doneRow['doneDate'];
                $workingQuery = "SELECT MAX(`date`) AS workingDate FROM fees 
                                WHERE worklist_id = " . $doneRow['id'] . " 
                                AND `desc` = 'Accepted Bid'";
                if($workingResult = mysql_query($workingQuery)) {
                    $workingQueryRow = mysql_fetch_assoc($workingResult);
                    $workingDate = $workingQueryRow['workingDate'];
                    $w_date = strtotime($workingDate);                      
                } else {
                    return false;
                }
                $d_date = strtotime($doneDate);
                $currentJobDiff = abs($d_date - $w_date);
                $jobDiffSum = $jobDiffSum + $currentJobDiff;
            }
        } else {
            return false;
        }
        if($totalJobsDone > 0) {
            return relativeTime($jobDiffSum/$totalJobsDone,false,true,false);
        } else {
            return false;
        }
    }
    
    public function getDevelopers() {
        $query = "SELECT DISTINCT u.id, u.nickname, 
                 (SELECT COUNT(*) FROM " . WORKLIST . " w 
                 LEFT JOIN " . PROJECTS . " p on w.project_id = p.project_id 
                 WHERE ( w.mechanic_id = u.id OR w.creator_id = u.id) 
                 AND w.status IN ('WORKING', 'FUNCTIONAL', 'REVIEW', 'COMPLETED', 'DONE') 
                 AND p.project_id = "  . $this->getProjectId() . ") as totalJobCount,
                 (SELECT SUM(F.amount) FROM " . FEES . " F 
                 LEFT OUTER JOIN " . WORKLIST . " w on F.worklist_id = w.id 
                 LEFT JOIN " . PROJECTS . " p on p.project_id = w.project_id 
                 WHERE (F.paid = 1 AND F.withdrawn = 0 AND F.expense = 0 AND F.user_id = u.id) 
                 AND p.project_id = " . $this->getProjectId() . ") as totalEarnings
                 FROM " . BIDS . " b LEFT JOIN " . WORKLIST . " w ON b.worklist_id = w.id 
                 LEFT JOIN " . PROJECTS . " p ON p.project_id = w.project_id 
                 LEFT JOIN " . USERS . " u ON b.bidder_id = u.id 
                 WHERE b.accepted = 1 AND p.project_id = " . $this->getProjectId() . " ORDER BY totalEarnings DESC";
        if($result = mysql_query($query)) {
            while($row = mysql_fetch_assoc($result)) {
                $developers[$row['id']] = $row;
            }
        } else {
            return false;
        }
        return $developers;
    }
  
    public function getDevelopersLastActivity($userId) {
        $sql = "SELECT MAX(change_date) FROM " . STATUS_LOG . " s 
                LEFT JOIN " . WORKLIST . " w ON s.worklist_id = w.id 
                LEFT JOIN " . PROJECTS . " p on p.project_id = w.project_id 
                WHERE s.user_id = '$userId' AND p.project_id = " . $this->getProjectId();
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            $lastActivity = strtotime($row[0]);
            $rightNow = time();
            if($lastActivity == '') {
                return false;
            } else {
                if(($rightNow - $lastActivity) > 604800) { //if greater than a week i.e. 7*24*60*60 in seconds
                    return date('d-F-Y', $lastActivity);
                } else {
                    return (formatableRelativeTime($lastActivity, 2) . " ago");
                }
            }
        }
        return false;
    } 
    
    
}// end of the class