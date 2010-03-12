<?php
/**
 * Workitem
 *
 * @package Workitem
 * @version $Id$
 */
require_once 'lib/Workitem/Exception.php';
/**
 * Workitem
 *
 * @package Workitem
 */
class WorkItem
{
    protected $id;
    protected $summary;
    protected $creatorId;
    protected $ownerId;
    protected $status;
    protected $notes;

    public function __construct($id = null)
    {
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

    public function loadById($id)
    {
        return $this->load($id);
    }

    protected function load($id = null)
    {
        if ($id === null && !$this->id) {
            throw new Workitem_Exception('Missing workitem id.');
        } elseif ($id === null) {
            $id = $this->id;
        }
        $query = "
SELECT
    w.id,
    w.summary,
    w.owner_id,
    w.status,
    w.notes
FROM  ".WORKLIST. " as w
WHERE w.id = '" . (int)$id . "'";
        $res = mysql_query($query);
        if (!$res) {
            throw new Workitem_Exception('MySQL error.');
        }
        $row = mysql_fetch_assoc($res);
        if (!$row) {
            throw new Workitem_Exception('Invalid workitem id.');
        }
        $this->setId($row['id'])
             ->setSummary($row['summary'])
             ->setOwnerId($row['owner_id'])
             ->setStatus($row['status'])
             ->setNotes($row['notes']);
        return true;
    }

    public function idExists($id)
    {
        $query = '
SELECT COUNT(*)
FROM ' . WORKLIST . '
WHERE id = ' . (int)$id;
        $res = mysql_query($query);
        if (!$res) {
            throw new Workitem_Exception('MySQL error.');
        }
        $row = mysql_fetch_row($res);
        return (boolean)$row[0];
    }

    public function setId($id)
    {
        $this->id = (int)$id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
        return $this;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function setCreatorId($creatorId)
    {
        $this->creatorId = (int)$creatorId;
        return $this;
    }

    public function getCreatorId()
    {
        return $this->creatorId;
    }

    public function setOwnerId($ownerId)
    {
        $this->ownerId = (int)$ownerId;
        return $this;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    protected function insert()
    {
        $query = '
INSERT INTO ' .WORKLIST. ' (
    summary,
    creator_id,
    owner_id,
    status,
    notes,
    created )
VALUES (
        ' . mysql_real_escape_string($this->getSummary()).',
        ' . mysql_real_escape_string($this->getCreatorId()) . ',
        ' . mysql_real_escape_string($this->getOwnerId()) . ',
        ' . mysql_real_escape_string($this->getStatus()) . ',
        ' . mysql_real_escape_string($this->getNotes()) . ',
        NOW()
)';
        return mysql_query($query) ? 1 : 0;
    }

    protected function update()
    {
        $query = '
UPDATE '.WORKLIST.' SET
    summary= "'. mysql_real_escape_string($this->getSummary()).'",
    notes="'.mysql_real_escape_string($this->getNotes()).'",
    status="' .mysql_real_escape_string($this->getStatus()).'" ';

        $query .= ' WHERE id='.$this->getId();
        return mysql_query($query) ? 1 : 0;
    }

    public function save()
    {
        if ($this->idExists($this->getId())) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
     * @param int $worklist_id
     * @return array|null
     */
    public function getWorkItem($worklist_id)
    {
        $query = "SELECT w.id, w.summary,w.owner_id, w.mechanic_id, u.nickname, w.status, w.notes
			  FROM  ".WORKLIST. " as w
			  LEFT JOIN ".USERS." as u ON w.owner_id = u.id
			  WHERE w.id = '$worklist_id'";
        $result_query = mysql_query($query);
        $row =  $result_query ? mysql_fetch_assoc($result_query) : null;
        return !empty($row) ? $row : null;
    }

    /**
     * @param int $worklist_id
     * @return array|null
     */
    public function getBids($worklist_id)
    {
        $query = "SELECT bids.`id`, bids.`bidder_id`, `email`, u.`nickname`, bids.`bid_amount`,
				TIMESTAMPDIFF(SECOND, bids.`bid_created`, NOW()) AS `delta`,
				TIMESTAMPDIFF(SECOND, NOW(), bids.`bid_done`) AS `future_delta`,
				DATE_FORMAT(bids.`bid_done`, '%m/%d/%Y') AS `bid_done`
				FROM `".BIDS. "` as bids
				INNER JOIN `".USERS."` as u on (u.id = bids.bidder_id)
				WHERE bids.worklist_id=".$worklist_id.
				" and bids.withdrawn = 0 ORDER BY bids.`id` DESC";
        $result_query = mysql_query($query);
        if($result_query) {
            $temp_array = array();
            while($row = mysql_fetch_assoc($result_query)) {
                $temp_array[] = $row;
            }
            return $temp_array;
        } else {
            return null;
        }
    }

    public function getFees($worklist_id)
    {
        $query = "SELECT fees.`id`, fees.`amount`, u.`nickname`, fees.`desc`, DATE_FORMAT(fees.`date`, '%m/%d/%Y') as date, fees.`paid`
			FROM `".FEES. "` as fees LEFT OUTER JOIN `".USERS."` u ON u.`id` = fees.`user_id`
			WHERE worklist_id = ".$worklist_id."
			AND fees.withdrawn = 0 ";

        $result_query = mysql_query($query);
        if($result_query){
            $temp_array = array();
            while($row = mysql_fetch_assoc($result_query)) {
                $temp_array[] = $row;
            }
            return $temp_array;
        } else {
            return null;
        }
    }

    public function placeBid($mechanic_id, $username, $itemid, $bid_amount, $done_by, $timezone,$notes)
    {
        $query =  "INSERT INTO `".BIDS."`
				(`id`, `bidder_id`, `email`,`worklist_id`,`bid_amount`,`bid_created`,`bid_done`, `notes`)
			  VALUES
				(NULL, '$mechanic_id', '$username', '$itemid', '$bid_amount', NOW(), FROM_UNIXTIME('".strtotime($done_by." ".$timezone)."'), '$notes')";

        return mysql_query($query) ? mysql_insert_id() : null;
    }

    public function getUserDetails($mechanic_id)
    {
        $query = "SELECT nickname, username FROM ".USERS." WHERE id='{$mechanic_id}'";
        $result_query = mysql_query($query);
        return  $result_query ?  mysql_fetch_assoc($result_query) : null;
    }

    public function getOwnerSummary($worklist_id)
    {
        $query = "SELECT `users`.`id` as id, `username`,`is_runner`, `summary` FROM `users`, `worklist` WHERE `worklist`.`creator_id` = `users`.`id` AND `worklist`.`id` = ".$worklist_id;
        $result_query = mysql_query($query);
        return $result_query ? mysql_fetch_assoc($result_query) : null ;
    }

    public function updateWorkItem($worklist_id, $summary, $notes, $status)
    {
        $query = 'UPDATE '.WORKLIST.' SET summary= "'.$summary.'", notes="'.$notes.'", status="' .$status.'" ';

        $query .= ' WHERE id='.$worklist_id;
        return mysql_query($query) ? 1 : 0;
    }

    public function getSumOfFee($worklist_id)
    {
        $query = "SELECT SUM(`amount`) FROM `".FEES."` WHERE worklist_id = ".$worklist_id . " and withdrawn = 0 ";
        $result_query = mysql_query($query);
        $row = $result_query ? mysql_fetch_row($result_query) : null;
        return !empty($row) ? $row[0] : 0;
    }

    /**
     * Returns work item id for given bid
     *
     * @param int $bidId
     * @return int
     */
    public function getWorkItemByBid($bidId)
    {
        $query = '
SELECT `worklist_id` FROM `' . BIDS . '`
WHERE
    `id` = ' . (int)$bidId . '
        ';
        $res   = mysql_query($query);
        if (!$res) {
            throw new Exception('Could not retrieve result.');
        }
        $row = mysql_fetch_row($res);
        if (!$row) {
            throw new Exception('Bid not found.');
        }
        return $row[0];
    }

    /**
     * Checks if a workitem has any accepted bids
     *
     * @param int $worklistId
     * @return boolean
     */
    public function hasAcceptedBids($worklistId = null)
    {
        if ($worklistId === null) {
            if (!$this->id) {
                throw new Workitem_Exception('Missing id.');
            }
            $worklistId = $this->getId();
        }
        $query = '
SELECT COUNT(*)
FROM `' . BIDS . '`
WHERE
    `worklist_id` = ' . (int)$worklistId . '
    AND
    `accepted` = 1
    AND
    `withdrawn` = 0
        ';
        $res   = mysql_query($query);
        if (!$res) {
            throw new Exception('Could not retrieve result.');
        }
        $row = mysql_fetch_row($res);
        return ($row[0] > 0);
    }

    /**
     * If a given bid is accepted, the method returns TRUE.
     *
     * @param int $bidId
     * @return boolean
     */
    public function bidAccepted($bidId)
    {
        $query = 'SELECT COUNT(*) FROM `' . BIDS . '` WHERE `id` = ' . (int)$bidId . ' AND `accepted` = 1';
        $res   = mysql_query($query);
        if (!$res) {
            throw new Exception('Could not retrieve result.');
        }
        $row = mysql_fetch_row($res);
        return ($row[0] == 1);
    }

    // Accept a bid given it's Bid id
    public function acceptBid($bid_id)
    {
        if ($this->hasAcceptedBids($this->getWorkItemByBid($bid_id))) {
            throw new Exception('Can not accept an already accepted bid.');
        }
        $res = mysql_query('SELECT * FROM `'.BIDS.'` WHERE `id`='.$bid_id);
        $bid_info = mysql_fetch_assoc($res);

        // Get bidder nickname
        $res = mysql_query("select nickname from ".USERS." where id='{$bid_info['bidder_id']}'");
        if ($res && ($row = mysql_fetch_assoc($res))) {
            $bidder_nickname = $row['nickname'];
        }
	$bid_info['nickname']=$bidder_nickname;

        //changing owner of the job
        mysql_unbuffered_query("UPDATE `worklist` SET `mechanic_id` =  '".$bid_info['bidder_id']."', `status` = 'WORKING' WHERE `worklist`.`id` = ".$bid_info['worklist_id']);
        //marking bid as "accepted"
        mysql_unbuffered_query("UPDATE `bids` SET `accepted` =  1 WHERE `id` = ".$bid_id);
        //adding bid amount to list of fees
        mysql_unbuffered_query("INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `date`, `bid_id`) VALUES (NULL, ".$bid_info['worklist_id'].", '".$bid_info['bid_amount']."', '".$bid_info['bidder_id']."', 'Accepted Bid', NOW(), '$bid_id')");
        $bid_info['summary'] = getWorkItemSummary($bid_info['worklist_id']);
        return $bid_info;
    }
}// end of the class
