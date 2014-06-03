<?php

require_once('models/DataObject.php');

class UserSystemModel extends DataObject {
    public $id;
    public $user_id;
    public $operating_systems;
    public $hardware;
    public $index;

    public function __construct() {
        parent::__construct();

        $this->table_name = USER_SYSTEMS;
    }

    public function getClassName() {
        return 'UserSystemModel';
    }

    public function getUserSystems($user_id) {
        $fetchedSystemsArray = $this->dbFetchArray(" " . USER_SYSTEMS . ".user_id={$user_id}");
        $systemsArray = array();
        foreach ($fetchedSystemsArray as $systemData) {
            $system = new UserSystemModel();
            $system->loadObject(array($systemData));
            $systemsArray[] = $system;
        }
        return $systemsArray;
    }
}