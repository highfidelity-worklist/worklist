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

    public function getUserSystemsDictionary($user_id) {
        $systemsArray = $this->getUserSystems($user_id);
        $systemsDictionary = array();
        foreach ($systemsArray as $system) {
            $systemsDictionary[$system->id] = $system;
        }
        return $systemsDictionary;
    }

    public function getUserSystemsWithPlaceholder($user_id) {
        $systemsArray = $this->getUserSystems($user_id);

        $placeholderSystem = new UserSystemModel();
        $placeholderSystem->is_placeholder = true;
        $systemsArray[] = $placeholderSystem;

        return $systemsArray;
    }

    public function storeUsersSystemsSettings($user_id, $system_id_array, $system_operating_systems_array, $system_hardware_array, $system_delete_array) {
        $userSystemsDictionary = $this->getUserSystemsDictionary($user_id);

        $last_system_index = 0;
        foreach ($system_id_array as $i => $system_id) {
            $system_operating_systems = $system_operating_systems_array[$i];
            $system_hardware = $system_hardware_array[$i];
            $system_delete = intval($system_delete_array[$i]);

            if ($system_delete) {
                if (array_key_exists($system_id, $userSystemsDictionary)) {
                    $system = $userSystemsDictionary[$system_id];
                    $system->removeRow(' id = '.$system->id.' ');
                }
            } elseif (array_key_exists($system_id, $userSystemsDictionary)) {
                $system = $userSystemsDictionary[$system_id];
                $system->user_id = $user_id;
                $system->operating_systems = $system_operating_systems;
                $system->hardware = $system_hardware;
                $system->index = ++$last_system_index;
                $system->save("id");
            } elseif (!empty($system_operating_systems) || !empty($system_hardware)) {
                $system = new UserSystemModel();
                $system->dbInsert(array(
                    'user_id' => $user_id,
                    'operating_systems' => $system_operating_systems,
                    'hardware' => $system_hardware,
                    'index' => ++$last_system_index
                ));
            }
        }
    }
}