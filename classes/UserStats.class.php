<?php
define ('SL_OK', 'ok');
define ('SL_ERROR', 'error');

class UserStats{

    protected $userId;
    protected $itemsPerPage = 20;

    public function __construct($userId){
        $this->setUserId($userId);
    }

    public function setUserId($userId){
        $this->userId = $userId;
    }

    public function setItemsPerPage($number){
        $this->itemsPerPage = $number;
    }

    public function getItemsPerPage(){
        return $this->itemsPerPage;
    }

    public function getTotalJobsCount(){
        $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
                ."WHERE (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId})"
                ."AND `status` IN ('WORKING','REVIEW','COMPLETED','DONE')"; 
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return $row[0];
        }
        return false;
    }

    // wrapper for getJobsCount to get number of jobs in an active status
    public function getActiveJobsCount(){
    $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
                . "WHERE (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId}) AND `status` IN ('WORKING','REVIEW')";
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return $row[0];
        }
        return false;
    }

    public function getTotalEarnings(){
        $sql = "SELECT SUM(amount) FROM `fees` "
                . "WHERE `paid` = 1 AND `withdrawn`=0 AND `expense`=0 "
                . "AND `user_id` = {$this->userId}";
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return (int) $row[0];
        }
        return false;
    }

    // gets sum of paid earnings between specified period
    // start date and end date are included
    public function getEarningsForPeriod($startDate, $endDate){

        $startDate = date("Y-m-d", $startDate);
        $endDate = date("Y-m-d", $endDate);

        $sql = "SELECT SUM(amount) FROM `" . FEES . "` "
                . "WHERE `paid` = 1 AND `withdrawn`=0 AND `expense`=0 "
                . "AND `paid_date` >= '$startDate' AND `paid_date` <= '$endDate'"
                . "AND `user_id` = {$this->userId}";

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return (int) $row[0];
        }
        return false;
    }

    // gets earning for a number of days back
    // getLatestEarnings(30) will give earnings (paid) for last 30 days
    public function getLatestEarnings($daysCount){
        return $this->getEarningsForPeriod(strtotime("- $daysCount days"), time());
    }

    // gets list of fees and jobs associated with them for the preiod
    // start date and end date are included
    public function getEarningsJobsForPeriod($startDate, $endDate, $page = 1){

        $startDate = date("Y-m-d", $startDate);
        $endDate = date("Y-m-d", $endDate);

        $count = 0;
        $sql = "SELECT COUNT(*) FROM `" . FEES . "` "
                . "WHERE `paid` = 1 AND `withdrawn`=0 AND `expense`=0
                        AND `paid_date` >= '$startDate' AND `paid_date` <= '$endDate'
                        AND `user_id` = {$this->userId}";

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            $count = $row[0];
        }

        $sql = "SELECT DISTINCT `worklist_id`, `amount`, `summary`, `paid_date`,
                    DATE_FORMAT(`paid_date`, '%m/%d/%Y') AS `paid_formatted`,
                    `cn`.`nickname` AS `creator_nickname`, `rn`.`nickname` AS `runner_nickname`
                        FROM `" . FEES . "`
                        LEFT JOIN `" . WORKLIST . "` ON `worklist_id` = `worklist`.`id`
                        LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
                        LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
                        WHERE `" . FEES . "`.`paid` = 1 AND `withdrawn`=0 AND `expense`=0
                        AND `paid_date` >= '$startDate' AND `paid_date` <= '$endDate'
                        AND `user_id` = {$this->userId} ORDER BY `paid_date` DESC 
                        LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

        $itemsArray = array();
        $res = mysql_query($sql);
        if($res ){
            while($row = mysql_fetch_assoc($res)){
                $itemsArray[] = $row;
            }
            return array(
                        'count' => $count,
                        'pages' => ceil($count/$this->itemsPerPage),
                        'page' => $page,
                        'joblist' => $itemsArray);
        }
        return false;
    }

    // gets list of fees and jobs associated with them for a number of days back
    // works similar to getLatestEarnings(30) - will give earnings with jobs (paid) for last 30 days
    public function getLatestEarningsJobs($daysCount, $page = 1){
        return $this->getEarningsJobsForPeriod(strtotime("- $daysCount days"), time(), $page);
    }

    // get number of total love received by user using sendlove api
    public function getLoveCount(){
        $data = $this->sendloveApiRequest('getcount');
        if($data){
            return (int) $data['count'];
        }
        return false;
    }

    public function getUniqueLoveCount(){
       $data = $this->sendloveApiRequest('getuniquecount');
	    if($data){
            return (int) $data['count'];
        }
        return false;
    }


    // get total love received by user using sendlove api
    public function getTotalLove($page = 1){
        $data = $this->sendloveApiRequest('getlove', $page);
        if($data){
            return $data;
        }
        return false;
    }

    // sends a request to sendlove api and returns data in case of success
    // false - if something went wrong
    public function sendloveApiRequest($action, $page = 1){

        $user = new User();
        $user->findUserById($this->userId);

        $params = array (
            'action' => $action,
            'api_key' => SENDLOVE_API_KEY,
            'page' => $page,
            'perpage' => $this->itemsPerPage,
            'username' => $user->getUsername());
        $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
        $sendlove_rsp = postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER, $referer));
        $rsp = json_decode ($sendlove_rsp, true);

        if($rsp['status'] == SL_OK){
            return $rsp['data'];
        }else{
            return false;
        }
    }

    public function getJobsCount(){

        $count = 0;
        $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
                . "WHERE `mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId} "
                ."AND `status` IN ('WORKING', 'REVIEW', 'COMPLETED','DONE') 
                AND `creator_id` != `mechanic_id` ";

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            $count = $row[0];
        }
        return $count;
    }

    public function getUserItems($status, $page = 1){

        $count = $this->getJobsCount($status);

        $sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `cn`.`nickname` AS `creator_nickname`, 
            `rn`.`nickname` AS `runner_nickname`,
            DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "` 
            LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
            LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
            WHERE (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId})
            AND `status` IN ('WORKING', 'REVIEW', 'COMPLETED','DONE') ORDER BY `id` DESC 
            LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

        $itemsArray = array();
        $res = mysql_query($sql);
        if($res ){
            while($row = mysql_fetch_assoc($res)){
                $itemsArray[] = $row;
            }
            return array(
                        'count' => $count, 
                        'pages' => ceil($count/$this->itemsPerPage), 
                        'page' => $page, 
                        'joblist' => $itemsArray);
        }
        return false;
    }
    
public function getActiveUserItems($status, $page = 1){

        $count = $this->getActiveJobsCount($status);

        $sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `cn`.`nickname` AS `creator_nickname`, 
            `rn`.`nickname` AS `runner_nickname`,
            DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "` 
            LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
            LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
            WHERE (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId}) AND `status` IN ('WORKING','REVIEW') ORDER BY `id` DESC "
            . "LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

        $itemsArray = array();
        $res = mysql_query($sql);
        if($res ){
            while($row = mysql_fetch_assoc($res)){
                $itemsArray[] = $row;
            }
            return array(
                        'count' => $count, 
                        'pages' => ceil($count/$this->itemsPerPage), 
                        'page' => $page, 
                        'joblist' => $itemsArray);
        }
        return false;
    }
    
public function getBonusPaymentsTotal() {
 
$sql = "SELECT
    IFNULL(`rewarder`.`sum`,0)AS `bonus_tot`
    FROM `".USERS."` 
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE (`withdrawn`=0 AND `paid` = 1 AND `user_id` = {$this->userId}) AND `rewarder`=1 OR `bonus`=1 GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`
    WHERE `id` = {$this->userId}";
$res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return (int) $row[0];
        }
        return false;
    }
}
