<?php

/*class SendLoveModel extends Model {
    protected $table = USERS_LOVE;
    public $is_placeholder = false;


*/

if (!defined('USERS_LOVE)'))   define('USERS_LOVE', 'users_love');

class sendLove extends DataObject {
    public $love_id;
    public $to_id;
    public $from_id;
    public $message;
    public $date_sent;

    public $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->table_name = USERS_LOVE;
    }

    /**
     * Destructor
     */
    public function __destructor() {
        parent::__destruct();
    }

    /**
     * Load love by id
     */
    public function loadById($from_id,$to_id) {
        $objectData = $this->dbFetchArray(" `to_id`={$to_id} AND `from_id`={$from_id}  ");
        return $this->loadObject($objectData);
    }
    public function insertNew($values) {
        return $this->dbInsert($values);
    }

    public function getIndex($to_id) {
        $objectData = $this->dbFetchArray(" `to_id`={$to_id} ORDER BY `date_sent DESC` ");

        if (!$objectData && is_array($objectData)) {
            return null;
        }

        return $objectData;
    }

    //get user love count
    public function getUserLoveCount() {
        $sql = "SELECT COUNT love_id FROM " . USERS_LOVE . " WHERE to_id = " . (int)$this->getId();
        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_row($res);
            return $row[0];
        }
    }
}

?>
