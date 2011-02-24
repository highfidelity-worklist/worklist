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
            throw new Project_Exception('There is no project by that name.');
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
            SELECT p.project_id, p.name, p.description, p.budget, p.repository, p.contact_info, p.last_commit, p.active, p.owner_id
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
             ->setOwnerId($row['owner_id']);
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
    

    protected function insert() {
        $query = "INSERT INTO ".PROJECTS." (name, description, budget, repository, contact_info, active, owner_id ) ".
            "VALUES (".
            "'".mysql_real_escape_string($this->getName())."', ".
            "'".mysql_real_escape_string($this->getDescription())."', ".
            "'".mysql_real_escape_string($this->getBudget())."', ".
            "'".mysql_real_escape_string($this->getRepository())."', ".
            "'".mysql_real_escape_string($this->getContactInfo())."', ".
            "'".mysql_real_escape_string($this->getActive())."', ".
            "'".mysql_real_escape_string($this->getOwnerId())."', ".
            "NOW())";
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
                active='".intval($this->getActive())."',
                owner_id='".intval($this->getOwnerId())."'
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
            ORDER BY `name`";
        $result = mysql_query($query);

        if (mysql_num_rows($result)) {
            while ($project = mysql_fetch_assoc($result)) {
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

}// end of the class