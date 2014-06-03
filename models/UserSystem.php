<?php

class UserSystemModel extends Model {
    protected $table = USER_SYSTEMS;
    public $is_placeholder = false;

    public function getClassName() {
        return 'UserSystemModel';
    }

    public function getHardwareSafeHTML() {
        return nl2br(htmlspecialchars($this->hardware));
    }

    public function numberOfUserSystems($user_id) {
        $sql = "SELECT COUNT(*) FROM `" . $this->table . "` WHERE user_id = {$user_id}";
        $result = mysql_query($sql);
        if ($result) {
            $row = mysql_fetch_row($result);
            return intval($row[0]);
        }
        return 0;
    }

    public function getUserSystems($user_id) {
        $user_id = intval($user_id);
        return $this->loadMany("user_id = {$user_id}");
    }

    public function getUserSystemsJSON($user_id) {
        $json = array();
        $userSystems = $this->getUserSystems($user_id);
        foreach ($userSystems as $system) {
            $json[] = array(
                'id' => $system->id,
                'user_id' => $system->user_id,
                'operating_systems' => $system->operating_systems,
                'hardware' => $system->hardware,
                'index' => $system->index,
            );
        }
        return $json;
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
                    $system->removeRows(' id = '.$system->id.' ');
                }
            } elseif (array_key_exists($system_id, $userSystemsDictionary)) {
                $system = $userSystemsDictionary[$system_id];
                $system->user_id = $user_id;
                $system->operating_systems = $system_operating_systems;
                $system->hardware = $system_hardware;
                $system->index = ++$last_system_index;
                $system->save();
            } elseif (!empty($system_operating_systems) || !empty($system_hardware)) {
                $system = new UserSystemModel();
                $system->user_id = $user_id;
                $system->operating_systems = $system_operating_systems;
                $system->hardware = $system_hardware;
                $system->index = ++$last_system_index;
                $system->insert();
            }
        }
    }

    public function removeRows($condition) {
        $sql = "DELETE FROM `" . $this->table . "` WHERE {$condition}";
        return mysql_query($sql);
    }
}