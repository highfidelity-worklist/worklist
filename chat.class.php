<?php
// Class to work with chat table
//
//  vim:ts=4:et
require_once ("config.php");

$libdir = dirname(__FILE__) . '/lib';
set_include_path(get_include_path() . PATH_SEPARATOR . $libdir);

require_once dirname(__FILE__) . '/bot.class.php';

mysql_connect(DB_SERVER, DB_USER,DB_PASSWORD) or die(mysql_error()) ;
mysql_select_db(DB_NAME) or die(mysql_error());

define('IDLE', 1);
define('STOPPED', 2);
define('TYPING', 3);

class Chat
{
    // declarying an array that will hold
    // all possible bot names
    // currently the array consists of the values
    // of $botnames array and $custom_user_classes values
    var $botNames_ = array();

    var $botnames = array('him', 'faq', 'me', 'survey', 'ping', 'Eliza');
    var $custom_user_classes = array(
      USER_SENDLOVE=>"sendlove",
      USER_SVN=>"svn",
      USER_SCHEMAUPDATE=>"schemaupdate",
      USER_AUTOTESTER=>"autotester",
      USER_WORKLIST=>"worklist",
      USER_JOURNAL=>'journal',
      USER_SALES =>"worklist",
      USER_SITESCAN => "sitescan",
    );

    
    // we need another way to define system users
    public $system_users = array(
        USER_SENDLOVE, USER_WORKLIST, USER_SVN, USER_JOURNAL,
        USER_SALES, USER_SCHEMAUPDATE, USER_SITESCAN, USER_AUTOTESTER);

    // Add new class of user: emergency message senders. <daniel.brown@parasane.net>
    public $emergency_users = array(USER_SITESCAN);

    public function __construct()
    {
        foreach($this->botnames as $b){
            $this->botNames_[] = $b;
        }
        foreach($this->custom_user_classes as $name=>$class){
            $this->botNames_[] = $name;
            $this->botNames_[] = $class;
        }
        $this->botNames_[] = "Eliza";
    }

    function formatBotResponse($rsp) {
        $time = time();

        if ($rsp['scope'] == '#private') {
            $message = $rsp['message'];
        } else {
            $message = $rsp['private'];
        }

        $html = '<div data="' . $time . '" class="entry bot private">'."\n".
            '  <h2>'."\n".
            '    <span class="entry-author">'.$rsp['bot'].'</span>'."\n".
            '    <span class="entry-date" data="'.$time.'" title="'.date("d M H:i:s", $time).'">just now</span>'."\n".
            '  </h2>'."\n".
            '  <div class="entry-text">' . linkify($message, null, true) . '</div>'."\n".
            '</div>';
        return $html;
    }
    
    /**
     * Returns all bot names as an array
     * @return array
     * 08-MAY-2010 <Yani>
     */
    function getBotNames(){
        return $this->botNames_;
    }

    // if $last_private is true, the client has last shown a private message, so always start a new group
    function formatEntries($entries, $exclude = '', $bundle = true, $last_private = null, $returnHtml = true, $all = true) {

        $html = '';
        if (empty($entries)) return $html;
        $newentries = array();
        $prev_author = null;
        $ENTRIES_TABLE = $all === true ? ENTRIES_ALL : ENTRIES;
        // no sense in doing this query if we aren't bundling
        if ($bundle && count($entries) == 1) {
            $sql = "SELECT author,ip FROM " . $ENTRIES_TABLE . " " . $exclude . " ORDER BY id DESC LIMIT 1, 1";
            $result = mysql_query($sql);
            
            if ($result) {
              $row = mysql_fetch_assoc($result);
              $prev_author = $row['author'].'@'.$row['ip'];
            } else {
              // log an error message to follow-up this event
              $fp = fopen("/tmp/journal_write.log", "a");
              fwrite($fp, date("Y-m-d H:M:s")."::Error finding entry for: \n");
              fwrite($fp, "  query: " . $sql . "\n");
              fwrite($fp, "  errno: ".mysql_errno()."\n");
              fwrite($fp, "  error: ".mysql_error()."\n");
              fclose($fp);            
            }
              
        }
        if ($last_private != null) $prev_author = $last_private;

        $nickname = (isset($_SESSION['nickname'])) ? $_SESSION['nickname'] : '';
        $now = strtotime($this->getNow());
        foreach ($entries as $entry) {
            $custom_class = '';
            $bot=0;
            foreach ($this->custom_user_classes as $user=>$class) {
                if ($entry['author'] == $user) {
                    $custom_class = $class;
                    $bot=1;
                    break;
                }
            }
            foreach ($this->botnames as $botname) {
                if ($entry['author'] == $botname) {
                    $custom_class = 'bot';
                    $bot=1;
                }
            }
            if (empty($custom_class) && $nickname != $entry['author']) {
                $custom_class = 'other';
            }
            
            if ($entry['author'].'@'.$entry['ip'] || !$bundle || $bot)  {
                $time = strtotime($entry['date']);
                $func = 'href="userinfo.php?id='.$entry['user_id'].'"';
                if ($returnHtml !== true) {
                    $prev_author = $entry['author'] . '@' . $entry['ip'];
                    $newentries[] = array_merge($entry, array(
                        'custom_class' => $custom_class,
                        'nickname' => $nickname,
                        'prev_author' => $prev_author,
                        'time' => $time,
                        'func' => $func,
                        'time_title' => date("d M H:i:s", $time),
                        'relative_time' => relativeTime($now - $time),
                        'entry_text' => linkify($entry['entry']),
                        'entry_type' => 'extra',
                    ));
                } else {
                    $html .= 
                        '<div data="' . $time . '" class="entry ' . $custom_class . '" id="entry-' . $entry['id'] . '">' . "\n" .
                        "    <h2>\n" .
                                (
                                    in_array($entry['author'], $this->botNames_) || $entry['author'] =='Guest'
                                        ? '<span class="entry-author">' . $entry['author'] . "</span>\n"
                                        : '<a ' . $func . ' target="_blank" class="entry-author">' . $entry['author'] . "</a>\n"
                                ) .
                        '        <span class="entry-date" data="' . $time . '" title="' . date("d M H:i:s", $now) . '--' . date("d M H:i:s", $time) . '">' .
                                     relativeTime($time - $now) . 
                        "        </span>\n" .
                        "    </h2>\n" .
                        '    <div class="entry-text">' . linkify($entry['entry'], $entry['author']) . "</div>\n".
                        '</div>';
                    
                }
            } else {
                $time = strtotime($entry['date']);
                if ($returnHtml !== true) {
                    $nickname = (isset($_SESSION['nickname'])) ? $_SESSION['nickname'] : '';
                    $newentries[] = array_merge($entry, array(
                        'nickname' => $nickname,
                        'entry_text' => linkify($entry['entry']),
                        'entry_type' => 'basic',
                        'time' => $time
                    )); 
                } else {
                    $html .= '<div data="' . $time . '" class="entry" id="entry-' . $entry['id'] . '">'."\n";
                    $html .= '  <div class="entry-text">' . linkify($entry['entry'], $entry['author']) . '</div>'."\n";
                    $html .= "</div>\n";
                }
            }

        }
        return $returnHtml ? $html : $newentries;
    }

    function formatPrivateResponse($author, $message) {
        $time = time();

        $html = '<div data="' . $time . '" class="entry">'."\n".
            '  <h2>'."\n".
            '    <span class="entry-author">'.$author.'</span>'."\n".
            '    <span class="entry-date" data="'.$time.'" title="'.date("d M H:i:s", $time).'">just now</span>'."\n".
            '  </h2>'."\n".
            '  <div class="entry-text">' . linkify($message) . '</div>'."\n".
            '</div>';
        return $html;
    }

    /* 
     * Get Mysql server current time
     */ 
    function getNow() {
        $sql = "select NOW() as CurrentDateTime FROM " . ENTRIES;
        $result = mysql_query($sql);
        $row = mysql_fetch_assoc($result);
        return $row['CurrentDateTime'];
    }

    function getCount() {
        $sql = "SELECT COUNT(*) AS `count` FROM ".ENTRIES;
        $result = mysql_query($sql);
        $row = mysql_fetch_assoc($result);
        return $row['count'];
    }

    function getEarliestDate($query='')
    {
        $where = $this->getWhereStatement($query);
        $sql = "SELECT date FROM ".ENTRIES_ALL." $where ORDER BY `id` ASC LIMIT 1";
        $res = mysql_query($sql);

        if ($res && $entry = mysql_fetch_assoc($res)) {
            $date = date('M j, Y H:i:s', strtotime($entry['date']));
        } else {
            $date = 'Jan 1, 2010 00:00:00';
        }
        return strtotime($date);
    }

    function getWhereStatement($query)
    {
        $where = '';
        if (!empty($query) && $query != 'Search...') {
            $array = explode(" ", rawurldecode($query));

            $i=0;
            $size = sizeof($array);
            $where = " WHERE ";

            foreach ($array as $item) {
                $where.="(author LIKE '%".mysql_escape_string($item)."%' OR entry LIKE '%".mysql_escape_string($item)."%') ";

                if(++$i != $size) {
                    $where .= " AND ";
                }
            }
        }

        return $where;
    }

    function getSystemWhere($exclude = false, $and = true){
        $exclude = ($exclude == false) ? '' : 'NOT';
        $and = ($and == true) ? 'AND' : '';
        $system = "$and `nickname` $exclude IN ('";
        $system .= implode("', '", $this->system_users);
        return $system."')";
    }

    function loadEntries($lastId, $options = null, $all = true)
    {
      // Determine which table to use 
      $ENTRIES_TABLE = $all === true ? ENTRIES_ALL : ENTRIES ;
      if(!isset($options['filter'])) $options['filter'] = 'all';
      $select = "SELECT
        `e`.`id`,
        `user_id`,
        `entry`,
        `sampled`,
        CASE WHEN `u`.`nickname` IS NULL OR `u`.`nickname` = 'Bot' THEN `author` ELSE `u`.`nickname` END AS `author`,
        `ip`,
        `date`";
      $order = "date DESC";
      $reverse = true;
      $where = (isset($options['query']) && !empty($options['query'])) 
        ? $this->getWhereStatement($options['query']) : 'WHERE 1';
      $where .= ' AND `e`.`visible` = 1';
      if(!empty($options['toTime']))
      {
        $prevNext = (isset($options['prevNext']) && !empty($options['prevNext'])) ? $options['prevNext'] : '';
        if (empty($prevNext))
        {
          // if we don't know which way to go, we'll need to do two queries, lets recurse
          $total = $options['count'];
          // half one way, half the other
          $options['count'] = $options['count'] / 2;
          // future entries first, as this is more likely to be empty
          $options['prevNext'] = 'next';
          $one = $this->loadEntries($lastId, $options, true);
          // then get the remaining entries from the past
          $options['count'] = $total - count($one['entries']);
          $options['prevNext'] = 'prev';
          $two = $this->loadEntries($lastId, $options, true);
          $lastId = max($one['lastId'], $two['lastId']);
          $firstDate = min($one['firstDate'], $two['firstDate']);
          $lastDate = max($one['lastDate'], $two['lastDate']);
          $entries = array_merge($two['entries'], $one['entries']);
          $system = array_merge($two['system_entries'], $one['system_entries']);
          return array('lastId' => $lastId, 'firstDate' => $firstDate, 'lastDate' => $lastDate,
               'entries' => $entries, 'system_entries' => $system);
        }
        $toTime = $options['toTime'];
        if ($prevNext == 'prev')
        {
          $where .= " AND date <= FROM_UNIXTIME($toTime)";
        }
        else
        {
          $where .= " AND date > FROM_UNIXTIME($toTime)";
          $order = "date ASC";
          $reverse = false;
        }
      }
      if($options['filter'] == 'system')
      {
            $from = $ENTRIES_TABLE." AS e INNER JOIN " . USERS . " AS u ON `u`.`id` = `user_id` ";
            // if the systray is open we'll do a UNION query
            $infrom = $ENTRIES_TABLE." AS e INNER JOIN " . USERS . " AS u ON `u`.`id` = `user_id` ";
            $notinfrom = $ENTRIES_TABLE." AS e LEFT JOIN " . USERS . " AS u ON `u`.`id` = `user_id` ";
            $notin = $this->getSystemWhere(1,1);
            $authornotin = str_replace('nickname', 'author', $notin);
            $notinlimit = $options['count'] ? "LIMIT {$options['count']}" : '';
            $in = $this->getSystemWhere(0,1);
            $inlimit = $options['system_count'] ? "LIMIT {$options['system_count']}" : '';
            if ($lastId) {
                  $where .= " AND e.id > $lastId";
            }
            $sql = "( $select 
                FROM $notinfrom
                $where $authornotin
                ORDER BY $order
                $notinlimit )
            UNION
            ( $select
                FROM $infrom 
                $where $in
                ORDER BY $order
                $inlimit )
            ORDER BY $order ;";
      }
      else
      {
        $limit = isset($options['count']) && $options['count'] > 0 ? "LIMIT {$options['count']}" : '';
        if ($lastId)
        {
              $where .= " AND e.id > $lastId";
        }
            $from = $ENTRIES_TABLE . " AS e LEFT JOIN " . USERS . " AS u ON `u`.`id` = `user_id` ";
            $sql = "
        $select
            FROM $from
            $where
            ORDER BY $order
        $limit
        ;";
      }  
      $entries = Array();
      $system = Array();
      if (isset($_GET['debugsql'])) die($sql);
      $result = mysql_query($sql);
      if($result)
      {
        $lastId = $lastDate = 0;
        $firstDate = 80*365*86400; /* 40 years in the future */
        $nickname = !empty($_SESSION['nickname']) ? $_SESSION['nickname'] : 'Guest';
        while ($row = mysql_fetch_assoc($result)) {
          /* Let bots handle entries. */
          $rsp = Bot::notifyOf('entry', array($nickname, $row));
          /* Bots can skip entries */
          if ($rsp['status'] == 'skip') continue;
          if (!empty($rsp['botdata'])) $row['botdata'] = $rsp['botdata'];
          
          /* Bots can add new entries */
          if ($rsp['status'] != 'ignore' && !empty($rsp['entry'])) {
              $rsp['entry']['ip'] = 0;
              $rsp['entry']['id'] = $entry['id'];
              $rsp['entry']['date'] = $entry['date'];
              $entries[] = $rsp['entry'];
          }

          $lastId = max($lastId, $row['id']);
          // Moved the next few lines to the if statement below
          // that way the data is populated only when the message
          // is defined as an entry. If the data is updated
          // when this is a system message, then there is a design flow
          // 03-MAY-2010 <Yani>
          if (isset($options['filter']) && ($options['filter'] == 'system' || $options['filter'] == 'users') && in_array($row['author'], $this->system_users))
          {
            $system[] = $row;
          }
          else
          {
            // Updates the date only when the message
            // is identified as entry
            // 03-MAY-2010 <Yani>
            $entryDate = strtotime($row['date']);
            $firstDate = min($firstDate, $entryDate);
            $lastDate = max($lastDate, $entryDate);
            $entries[] = $row;
          }

          $userId = getSessionUserId();
          if ($row['sampled'] && $userId == $row['user_id']) {
            list($usec, $sec) = explode(" ",microtime());
            $load_time = ($sec * 1000) + intval($usec * 1000);
            mysql_unbuffered_query("UPDATE ".LATENCY_LOG." SET `load_time`=$load_time WHERE `entry_id`='".$row['id']."' AND `user_id`='$userId' AND `load_time`=0");
          } 
        }
        if ($reverse) 
        {
          $entries = array_reverse($entries);
          $system = array_reverse($system);
        }
        return array('lastId' => $lastId, 'firstDate' => $firstDate, 'lastDate' => $lastDate,
             'entries' => $entries, 'system_entries' => $system);
//Garth in krumch task #11576 - return empty array if no results
      } else {
        return array();
      }
    }

    function loadTaskEntries($lastId, $options = null)
    {
      if(!isset($options['filter'])) $options['filter'] = 'all';
      $select = "SELECT
        `e`.`id`,
        `user_id`,
        `entry`,
        `sampled`,
        CASE WHEN `u`.`nickname` IS NULL OR `u`.`nickname` = 'Bot' THEN `author` ELSE `u`.`nickname` END AS `author`,
        `ip`,
        `date`";
      $order = !empty($options['order']) ? $options['order'] : "DESC";
      $reverse = !empty($options['reverse']) ? $options['reverse'] : true;
      $where = (isset($options['query']) && !empty($options['query'])) 
        ? "WHERE `j`.`job_id`=".$options['query'] : 'WHERE 1';
      $where .= ' AND `e`.`visible` = 1';
      if(!empty($options['toTime']))
      {
        $prevNext = (isset($options['prevNext']) && !empty($options['prevNext'])) ? $options['prevNext'] : '';
        if (empty($prevNext))
        {
          // if we don't know which way to go, we'll need to do two queries, lets recurse
          $total = $options['count'];
          // half one way, half the other
          $options['count'] = $options['count'] / 2;
          // future entries first, as this is more likely to be empty
          $options['prevNext'] = 'next';
          $one = $this->loadEntries($lastId, $options);
          // then get the remaining entries from the past
          $options['count'] = $total - count($one['entries']);
          $options['prevNext'] = 'prev';
          $two = $this->loadEntries($lastId, $options);
          $lastId = max($one['lastId'], $two['lastId']);
          $firstDate = min($one['firstDate'], $two['firstDate']);
          $lastDate = max($one['lastDate'], $two['lastDate']);
          $entries = array_merge($two['entries'], $one['entries']);
          $system = array_merge($two['system_entries'], $one['system_entries']);
          return array('lastId' => $lastId, 'firstDate' => $firstDate, 'lastDate' => $lastDate,
               'entries' => $entries, 'system_entries' => $system);
        }
        $toTime = $options['toTime'];
        if ($prevNext == 'prev')
        {
          $where .= " AND date <= FROM_UNIXTIME($toTime)";
        }
        else
        {
            $where .= " AND date > FROM_UNIXTIME($toTime)";
            $order = !empty($options['order']) ? $options['order'] : "ASC";
            $reverse = !empty($options['reverse']) ? $options['reverse'] : false;
        }
      }
      if($options['filter'] == 'system')
      {
            $from = ENTRIES_ALL." AS e INNER JOIN " . USERS . " AS u ON `u`.`id` = `user_id` ";
            // if the systray is open we'll do a UNION query
            $infrom = ENTRIES_ALL." AS e INNER JOIN " . USERS . " AS u ON `u`.`id` = `user_id` ";
            $notinfrom = ENTRIES_ALL." AS e LEFT JOIN " . USERS . " AS u ON `u`.`id` = `user_id` ";
            $notin = $this->getSystemWhere(1,1);
            $authornotin = str_replace('nickname', 'author', $notin);
            $notinlimit = $options['count'] ? "LIMIT {$options['count']}" : '';
            $in = $this->getSystemWhere(0,1);
            $inlimit = $options['system_count'] ? "LIMIT {$options['system_count']}" : '';
            if ($lastId) {
                  $where .= " AND e.id > $lastId";
            }
            $sql = "( $select 
                FROM $notinfrom
                $where $authornotin
                ORDER BY date $order
                $notinlimit )
            UNION
            ( $select
                FROM $infrom $in
                $where
                ORDER BY date $order
                $inlimit )
            ORDER BY date $order ;";
      }
      else
      {
        $limit = isset($options['count']) && $options['count'] > 0 ? "LIMIT {$options['count']}" : '';
        if ($lastId)
        {
              $where .= " AND e.id > $lastId";
        }
            $from = ENTRIES_ALL . " AS e LEFT JOIN " . USERS . " AS u ON `u`.`id` = `user_id` INNER JOIN ".ENTRYJOBS." AS j ON `e`.`id`=`j`.`entry_id` ";
            $sql = "
        $select
            FROM $from
            $where
            ORDER BY date $order
        $limit
        ;";
      }  
      $entries = Array();
      $system = Array();
      if (isset($_GET['debugsql'])) die($sql);
      $result = mysql_query($sql);
      if($result)
      {
        $lastId = $lastDate = 0;
        $firstDate = 80*365*86400; /* 40 years in the future */
        $nickname = !empty($_SESSION['nickname']) ? $_SESSION['nickname'] : 'Guest';
        while ($row = mysql_fetch_assoc($result)) {
          /* Let bots handle entries. */
          $rsp = Bot::notifyOf('entry', array($nickname, $row));
          /* Bots can skip entries */
          if ($rsp['status'] == 'skip') continue;
          if (!empty($rsp['botdata'])) $row['botdata'] = $rsp['botdata'];
          
          /* Bots can add new entries */
          if ($rsp['status'] != 'ignore' && !empty($rsp['entry'])) {
              $rsp['entry']['ip'] = 0;
              $rsp['entry']['id'] = $entry['id'];
              $rsp['entry']['date'] = $entry['date'];
              $entries[] = $rsp['entry'];
          }

          $lastId = max($lastId, $row['id']);
          // Moved the next few lines to the if statement below
          // that way the data is populated only when the message
          // is defined as an entry. If the data is updated
          // when this is a system message, then there is a design flow
          // 03-MAY-2010 <Yani>
          if (isset($options['filter']) && ($options['filter'] == 'system' || $options['filter'] == 'users') && in_array($row['author'], $this->system_users))
          {
            $system[] = $row;
          }
          else
          {
            // Updates the date only when the message
            // is identified as entry
            // 03-MAY-2010 <Yani>
            $entryDate = strtotime($row['date']);
            $firstDate = min($firstDate, $entryDate);
            $lastDate = max($lastDate, $entryDate);
            $entries[] = $row;
          }

          $userId = getSessionUserId();
          if ($row['sampled'] && $userId == $row['user_id']) {
            list($usec, $sec) = explode(" ",microtime());
            $load_time = ($sec * 1000) + intval($usec * 1000);
            mysql_unbuffered_query("UPDATE ".LATENCY_LOG." SET `load_time`=$load_time WHERE `entry_id`='".$row['id']."' AND `user_id`='$userId' AND `load_time`=0");
          } 
        }
      }
      if ($reverse) 
      {
        $entries = array_reverse($entries);
        $system = array_reverse($system);
      }
      return array('lastId' => $lastId, 'firstDate' => $firstDate, 'lastDate' => $lastDate,
           'entries' => $entries, 'system_entries' => $system);
    }


    function getLatestCount($lastId, $filter = 'all') {

      $system_count = 0;
      $where_exclude = '';

      if ($lastId == 0) {
        $sql = "SELECT MAX(id) as id FROM ".ENTRIES;
        $result = mysql_query($sql);
        $row = mysql_fetch_assoc($result);
        $lastId = $row['id'];
      }

      if($filter == 'system'){
        if(count($this -> system_users) > 0){
        $sql = "SELECT COUNT(*) AS `count` FROM ".ENTRIES." WHERE `id` > '$lastId' ".$this->getSystemWhere();
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        $system_count = (int)$row['count'];
        $where_exclude .= $this->getSystemWhere(true);
        }
      }

      $sql = "SELECT COUNT(*) AS `count` FROM ".ENTRIES." WHERE `id` > '$lastId' ".$where_exclude;
      $res = mysql_query($sql);
      $row = mysql_fetch_assoc($res);
      $count = $row['count'];

      return array('count' => $count, 'system_count' => $system_count, 'lastId' => $lastId);
    }

    function getGlobalTypingStatus($timeout = 30) {
        $data = array();

mysql_connect(DB_SERVER, DB_USER,DB_PASSWORD) or die(mysql_error()) ;
mysql_select_db(DB_NAME) or die(mysql_error());


        $sql = "SELECT a.`user_id` as `user_id`, a.`status` as `status`, a.*, b.* FROM ".TYPING_STATUS." AS a LEFT JOIN ".RECENT_SPEAKERS." AS b ON a.`user_id` = b.`user_id` WHERE DATE_ADD(a.`last_change`, INTERVAL $timeout SECOND) > NOW() AND a.`last_change` > b.`last_entry` ORDER BY a.`user_id` ASC";

//Garth in krumch sb #11576
        if (! $res = mysql_query($sql)) { error_log("getGlobalTypingStatus.mysql: ".mysql_error()."\n".var_export(array(debug_backtrace(),$_POST,$_SERVER),true)); return array(); }

        while ($row = mysql_fetch_row($res)){
            if ($row[1] == 'typing') {
                $data[$row[0]] = TYPING;
            } elseif ($row[1] == 'stopped') {
                $data[$row[0]] = STOPPED;
            }
        }

        return $data;
    }

    function setTypingStatus($status, $userId) {
        $map = array(IDLE => "'idle'", STOPPED => "'stopped'", TYPING => "'typing'");
        $status = array_key_exists($status, $map) ? $map[$status] : $map[IDLE];
        $sql = "INSERT INTO ".TYPING_STATUS." (`user_id`, `status`, `last_change`) VALUES ($userId, $status, NOW()) ON DUPLICATE KEY UPDATE `status` = $status, `last_change` = NOW()";
        mysql_unbuffered_query($sql);
        $this->touch();
        return array();
    }

    function sendEntry($author, $message, $data = array(), $internal = false, $encode = true) {
    
        $sampled = isset($data['sampled']) ? 1 : 0;
        
        // let's do this first, so if a bot hijacks the entry we're still 
        // going to mark the user as not typing any more
        // and we need to know the userid before we can do that
        if (isset($data['userid'])) {
            $userId = $data['userid'];
        } else if (isset($_SESSION['userid']) && $_SESSION['userid'] && ($author != USER_JOURNAL)) {
            $userId = $_SESSION['userid'];
            /* Mark user as idle. */
            $this->setTypingStatus(IDLE, $userId);
        }

        /* Give the bots a chance to respond to the new message. */
        $rsp = Bot::respondTo($author, $message);
        if (is_array($rsp)) $rsp['bot'] = 'Eliza';
        if ($rsp !== false && ($rsp['scope'] == '#private' || $rsp['scope'] == '#privpub')) {
            if ($rsp['status'] != 'ok-quiet') {
                $rsp['html']  = $this->formatPrivateResponse($author, $message);
            }
            $rsp['html'] .= $this->formatBotResponse($rsp);

            /* If there is a public bot response, don't return yet. */
            if ($rsp['scope'] != '#privpub') {
                return $rsp;
            }
        }
        if ($rsp === false || ($rsp['scope'] == '#public' || $rsp['scope'] == '#pubpriv')) {
            $messagetime = $this->recordEntry($userId, $author, $message, $sampled, $internal, $encode);
        }

        /* If the bot had a public response, record that too */
        if ($rsp !== false && ($rsp['scope'] == '#public' || $rsp['scope'] == '#privpub')) {
            $this->recordEntry(BOT_USER_ID, $rsp['bot'], $rsp['message'], false, true);
        }

        /* Now we can return the public bot response. */
        if ($rsp['scope'] == '#privpub') {
            $rsp['scope'] = '#private';
            return $rsp;
        }
        return array('status'=>'ok', 'scope'=>'#public', 'messagetime' => $messagetime);
    }

    //speaker's list functionality
    function addSpeaker($userId){
        $userId = intval($userId);
        $ip = ($userId == 0) ? $_SERVER['REMOTE_ADDR'] : 0;

        // add the given speaker to the list, or update their last_online status if they're already there
        $sql = "INSERT INTO ".RECENT_SPEAKERS." (`online`, `user_id`, `ip`, `last_entry`) VALUES ('1', $userId, '$ip', (SELECT CASE WHEN $userId = 0 THEN NULL ELSE `date` END FROM `".ENTRIES."` where `user_id` = $userId order by `date` desc limit 1)) ON DUPLICATE KEY UPDATE `last_online` = CURRENT_TIMESTAMP, `online` = '1'";
        mysql_unbuffered_query($sql);
        
        //check if this speaker is currently idle
        $sql = "SELECT `idle` FROM ".RECENT_SPEAKERS." WHERE `user_id` = $userId AND `ip` = '$ip' AND `idle` = 1 AND `idletime` < NOW()";
        $res = mysql_query($sql);
        if ($res && mysql_num_rows($res) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    function isSpeakerAway($nickname) {
        global $me_bot;
        $away = isset($me_bot) ? $me_bot->queryAway($nickname) : false;
        return array('away' => $away, 'link' => $me_bot->backlink);
    }

    //speaker's list functionality
    function isSpeakerOnline($userId) {
        $userId = intval($userId);
        $ip = ($userId == 0) ? $_SERVER['REMOTE_ADDR'] : 0;

        $res = mysql_query("SELECT `user_id` FROM ".RECENT_SPEAKERS." WHERE `user_id`=$userId AND `ip`='$ip' AND `online`!=0");
        if ($res && mysql_num_rows($res) > 0) {
            return true;
        } else {
            return false;
        }
    }

    function offlineSpeaker($userId){
        $userId = intval($userId);
        $ip = ($userId == 0) ? $_SERVER['REMOTE_ADDR'] : 0;

        $sql = "UPDATE ".RECENT_SPEAKERS." SET `online` = '0', `idle` = '0', `idletime` = NULL WHERE `user_id`=$userId AND `ip`='$ip' LIMIT 1";
        mysql_unbuffered_query($sql);
    }

    function updateOnlineSpeakers() {
        // remove speakers from the recent speaker list that are no longer heartbeating
        $sql = "UPDATE ".RECENT_SPEAKERS." SET `online` = '0' WHERE `last_online` < DATE_SUB(NOW(), INTERVAL ".USER_TIMEOUT." MINUTE);\n";
        mysql_unbuffered_query($sql);
        echo $sql, mysql_error();

        /* Delete stale guest speakers */
        $sql = "DELETE FROM `".RECENT_SPEAKERS."` WHERE `ip`!='0' AND `last_online`<DATE_SUB(NOW(), INTERVAL 4 HOUR) AND (`last_entry` IS NULL OR `last_entry` < DATE_SUB(NOW(), INTERVAL 4 HOUR));\n";
        mysql_unbuffered_query($sql);
        echo $sql, mysql_error();

        // get idlers and update current idle status
        $idlewhere = " `online` = '1' AND `idle` = '0' AND `last_entry` < DATE_SUB(NOW(), INTERVAL ".USER_IDLETIME." MINUTE) AND `idletime` < NOW()";
        $sql = "SELECT u.`nickname` FROM ".RECENT_SPEAKERS." r JOIN ".USERS." u ON r.user_id = u.id WHERE $idlewhere\n";
        $res = mysql_query($sql);
        if (mysql_num_rows($res) > 0) {
            global $him_bot;
            while ($row = mysql_fetch_row($res)){
                if($row[0]) {
                    $him_bot->recordIdleTime($row[0]);
                }
            }
        }
        echo $sql, mysql_error();
        $sql = "UPDATE ".RECENT_SPEAKERS." SET `idle` = '1', `idletime` = NOW(), `last_active` = NOW() WHERE $idlewhere\n";
        mysql_unbuffered_query($sql);
        echo $sql, mysql_error();

        // update current users idle status
        $sql = "UPDATE ".RECENT_SPEAKERS." SET `idle` = '0' WHERE `online` = '1' AND `last_entry` > DATE_SUB(NOW(), INTERVAL ".USER_IDLETIME." MINUTE);\n";
        mysql_unbuffered_query($sql);
        echo $sql, mysql_error();
    }

    function unidleSpeaker($userId){
        // speakers can mark themselves as unidle, the idle flag is not removed until
        // they actually speak however, so we can keep a track of when their idle
        // messages should begin this way we can preserve the original idle time
        // between idles
        $userId = intval($userId);
        $ip = ($userId == 0) ? $_SERVER['REMOTE_ADDR'] : 0;

        // unidle a speaker
        $sql = "UPDATE ".RECENT_SPEAKERS." SET `idle` = 0, `idletime` = DATE_ADD(NOW(), INTERVAL ".USER_IDLETIME." MINUTE) WHERE `idle` = 1 AND `user_id` = $userId AND `ip` = '$ip'";
        mysql_unbuffered_query($sql);
        
    }

    function updateSpeaker($userId){
        $userId = intval($userId);
        $ip = ($userId == 0) ? $_SERVER['REMOTE_ADDR'] : 0;

        $sql = "UPDATE ".RECENT_SPEAKERS." SET `last_entry` = CURRENT_TIMESTAMP, `idle` = 0 WHERE `user_id`=$userId AND `ip`='$ip'";
        mysql_unbuffered_query($sql);
        if(isset($_GET['debug'])) {
            echo mysql_error();
            die($sql);
        }
    }
    
    function listSpeakers(){
        /* Get guests, needs to be separate because we don't want to bump into the limit */
        $sql = "SELECT UNIX_TIMESTAMP(`last_online`) AS `last_online`, UNIX_TIMESTAMP(`last_entry`) AS `last_entry`, `ip` FROM ".RECENT_SPEAKERS." ".
               "WHERE `online`!=0 AND `ip`!='0' AND ((`last_online` BETWEEN DATE_SUB(NOW(), interval 4 HOUR) AND NOW()) OR (`last_entry` BETWEEN DATE_SUB(NOW(), interval 4 HOUR) AND NOW())) ".
               "ORDER BY `last_entry` DESC";
        $res = mysql_query($sql);

        $guests = 0;
    $penalized_guests = array();
        if ($res && ($guests = mysql_num_rows($res)) > 0) {
            $guest = mysql_fetch_array($res);
            $lastGuestEntry = intval($guest[1]);

        $penaltyData = Penalty::getUserPenalties(0, $guest['ip']);
        $suspended = ($penaltyData['sums']['status'] == 'suspended') ? 1 : 0;

        if($penaltyData['sums']['timeleft'] > 0 || $suspended == 1){
            $penalized_guests[] = array('ip' => $guest['ip'], 'timeleft' => $penaltyData['sums']['timeleft'], 'suspended' => $suspended);
        }
        }

        /* Get regular users */
        $sql = "SELECT `user_id`, `nickname`, UNIX_TIMESTAMP(`last_entry`) AS `last_entry`, UNIX_TIMESTAMP(`last_online`) AS `last_online`, ".
               "       (select status from ".USER_STATUS." WHERE id = user_id ORDER BY timeplaced DESC LIMIT 1) as `status`".
               "FROM ".RECENT_SPEAKERS." LEFT JOIN ".USERS." ON `user_id`= ".USERS.".`id` ".
               "WHERE `online`!=0 AND `ip`='0' ORDER BY `last_entry` DESC LIMIT 20";
        $res = mysql_query($sql);

        $speakers = array();
        global $me_bot;
        $away = isset($me_bot) ? $me_bot->getAwayList() : array();
        $away = explode(',',strtolower(implode(',', $away)));
        $typing_status = $this->getGlobalTypingStatus();
        $awayText = $me_bot->getAwayText();
        while ($speaker = mysql_fetch_row($res)){

            /* Inject the special guest speaker into the right location. */
            if ($guests > 0 && $lastGuestEntry > 0 && $lastGuestEntry > intval($speaker[2])) {
                $speakers[] = array(0, "Guest ($guests)", $lastGuestEntry, 0, '', $penalized_guests, 1);
                $guests = 0;
            }

            if (!empty($speaker[1]) && isset($awayText[$speaker[1]])&& $awayText[$speaker[1]]['away'] != NOMESSAGE) $speaker[4] = $awayText[$speaker[1]]['away'];
            $speaker[5] = in_array(strtolower($speaker[1]), $away) ? 1 : 0;

            // get Penalty Box data
            $speakerPenalties = Penalty::getUserPenalties($speaker[0]);
            $suspended = ($speakerPenalties['sums']['status'] == 'suspended') ? 1 : 0;
            $timeleft = $speakerPenalties['sums']['timeleft'] > 0 ? $speakerPenalties['sums']['timeleft'] : 0;
            $speaker[6] = $timeleft;
            $speaker[7] = $suspended;

            // Typing status:
            $speaker[8] = array_key_exists($speaker[0], $typing_status) ? $typing_status[$speaker[0]] : IDLE;
            
            //LocalTime
            $user = new User();
            $user->findUserById($speaker[0]);
            $speaker[9] = convertTimeZoneToLocalTime($user->getTimezone(), 0);
            if (isset($_GET['debug'])) var_dump($speaker);
            $speakers[] = $speaker;
        }
        /* If no guest has spoken more recently than the last speaker, just append the guest speaker. */
        if ($guests > 0) {
            $speakers[] = array(0, "Guest ($guests)", $lastGuestEntry, 0, '', $penalized_guests);
        }

        return $speakers;
    }

    function touch() {
        $touchtime = microtime();
        $touch = fopen(JOURNAL_UPDATE_TOUCH_FILE, 'w');
        if (flock($touch, LOCK_EX))
        {
            fputs($touch,$touchtime ) ;
            flock($touch, LOCK_UN) ;
        }
        fclose($touch);
        return($touchtime);
    }

    protected function recordEntry($userId, $author, $message, $sampled, $internal = false, $encode = true) {
        if ($encode) {
            $message = htmlentities($message, ENT_QUOTES, 'UTF-8');
        }
    
        if (!$internal) {
            $message = mysql_real_escape_string($message);
            $author = mysql_real_escape_string(strip_tags($author));
        }
        if ($userId === null) {
            $userId = 0;
        } else {
            $userId = (int)$userId;
        }

        if ($userId != BOT_USER_ID) {
            $this->updateSpeaker($userId);
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];

        // prevent users in penalty box or suspended users from adding entries
        $userStatus = $userId != 'NULL' ? Penalty::getSimpleStatus($userId) : Penalty::getSimpleStatus(0, $ip);
        if($userStatus == Penalty::NOT_PENALIZED){
            // Send to global entries table (used for searches and timetravel)
            $sql = "INSERT INTO ".ENTRIES." (`id`, `user_id`, `entry`, `author`,`ip`, `date`, `sampled`) ".
                    " (SELECT NULL, {$userId}, '{$message}', '{$author}', '{$ip}', CURRENT_TIMESTAMP, '{$sampled}' ".
                    " FROM ".USERS.
                    " WHERE (".
                        " nickname='{$author}' ". 
                        " AND `id` = {$userId} ".
                    " ) OR ( ".
                        " '{$userId}' IN ('0','".BOT_USER_ID."') ". 
                    " ) LIMIT 1 );";

            $retries = 0;
            while (!($rt = mysql_unbuffered_query($sql)) && $retries++ < 3) {
                $fp = fopen("/tmp/journal_write.log", "a");
                fwrite($fp, date("Y-m-d H:M:s")."::Error writing: $userId:$author:$message\n");
                fwrite($fp, "  retry: $retries\n");
                fwrite($fp, "  errno: ".mysql_errno()."\n");
                fwrite($fp, "  error: ".mysql_error()."\n");
                fclose($fp);

                usleep(250 * 1000 * $retries);
            }

            $id = mysql_insert_id();
            if ($id == 0 && $retries == 0 && $userId != BOT_USER_ID) {
                if (isset($_SESSION['userid']) && isset($_SESSION['nickname']) && $_SESSION['userid']) {
                    $lastUserInfo = getUserById($_SESSION['userid']);
                    if ($lastUserInfo->nickname != $author && $_SESSION['nickname'] != $lastUserInfo->nickname) {
                        $author = $lastUserInfo->nickname;
                        $_SESSION['nickname'] = $lastUserInfo->nickname;
                        $sql = "INSERT INTO ".ENTRIES." (`id`, `user_id`, `entry`, `author`,`ip`, `date`, `sampled`) ".
                                " (SELECT NULL, {$userId}, '{$message}', '{$author}', '{$ip}', CURRENT_TIMESTAMP, '{$sampled}' ".
                                " FROM ".USERS.
                                " WHERE (".
                                    " nickname='{$author}' ". 
                                    " AND `id` = {$userId} ".
                                " ) OR ( ".
                                    " '{$userId}' IN ('0','".BOT_USER_ID."') ". 
                                " ) LIMIT 1 );";
                        $rt = mysql_unbuffered_query($sql);
                        $id = mysql_insert_id();
                    }
                }
            }
            if( preg_match_all('/(\#[1-9][0-9]+)/i', $message, $matches)) {
                $distinctMatches= array_unique($matches[0]);
                foreach($distinctMatches as $match){
                    $job_id= (int) substr($match, 1);
                    $sql = "INSERT INTO ". ENTRYJOBS. " (entry_id, job_id) VALUES ({$id}, {$job_id})";
                    mysql_query($sql);
                }
            }   

            // Send to latest entries table (used for quick loading for current messages)
            $sql = "INSERT INTO ".ENTRIES_ALL." (`id`, `user_id`, `entry`, `author`,`ip`, `date`, `sampled`) ".
                    " (SELECT {$id}, {$userId}, '{$message}', '{$author}', '{$ip}', CURRENT_TIMESTAMP, '{$sampled}' ".
                    " FROM ".USERS.
                    " WHERE (".
                        " nickname='{$author}' ". 
                        " AND `id` = {$userId} ".
                    " ) OR ( ".
                        " '{$userId}' IN ('0','".BOT_USER_ID."') ". 
                    " ) LIMIT 1 );";
            $retries = 0;
            while (!($rt = mysql_unbuffered_query($sql)) && $retries++ < 3) {
                $fp = fopen("/tmp/journal_write.log", "a");
                fwrite($fp, date("Y-m-d H:M:s")."::Error writing: $userId:$author:$message\n");
                fwrite($fp, "  retry: $retries\n");
                fwrite($fp, "  errno: ".mysql_errno()."\n");
                fwrite($fp, "  error: ".mysql_error()."\n");
                fclose($fp);

                usleep(250 * 1000 * $retries);
            }

            if ($sampled && !empty($userId) && $userId != BOT_USER_ID) {
                list($usec, $sec) = explode(" ",microtime());
                $save_time = ($sec * 1000) + intval($usec * 1000);
                mysql_unbuffered_query("INSERT INTO ".LATENCY_LOG." SET `entry_id`=$id, `user_id`=$userId, `save_time`=$save_time");
            }

            return $this->touch();
        }
    }
    
    function updateAllJobIds() {
        $sql="DELETE FROM ". ENTRYJOBS ." WHERE 1";
        mysql_query($sql);
        echo "<br/>Empty Table, OK!";
        $sql="SELECT id, entry FROM ".ENTRIES. " WHERE entry LIKE '%#%'  ";
        $result=mysql_query($sql);
        while($row=mysql_fetch_assoc($result)){
            echo "<br/>";
            print_r($row);
            if( preg_match_all('/(\#[1-9][0-9]+)/i', $row['entry'], $matches)) {
                $distinctMatches= array_unique($matches[0]);
                foreach($distinctMatches as $match){
                    $job_id = (int) substr($match, 1);
                    $id = (int) $row["id"];
                    $sql = "INSERT INTO ". ENTRYJOBS. " (entry_id, job_id) VALUES ({$id}, {$job_id})";
                    echo "<br/>&nbsp;&nbsp;&nbsp;Job found: " . $job_id;
                    mysql_query($sql);
                }
            }   else {
                echo "<br/>Not found!";
            }
        }
        
        echo "<br/>TOTAL". count($rows);
    }

    function saveSample($startTime, $receiveTime) {
        $userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
        $result = mysql_query("SELECT MAX(id) AS id FROM ".ENTRIES." WHERE `user_id`='$userId'"); 
        $row = mysql_fetch_assoc($result);
        if (!empty($row)) {
            $id = $row['id'];
            mysql_unbuffered_query("UPDATE ".LATENCY_LOG." SET `start_time`='$startTime', `receive_time`='$receiveTime' WHERE `entry_id`='$id' AND `user_id`='$userId'");
        }
    }

    /**
     * Gets a user's infor given the user Id
     * @param $userId
     * @return User Row
     */
    function getUserById ($userId) {
        $query = "
            SELECT 
                usr.id,
                usr.username,
                usr.nickname
             FROM " . USERS . " as usr
             WHERE usr.id = " . $userId;
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        return $row;
    }
}

static $chat = null;
if (empty($chat)) {
    $chat = new Chat;
}

?>
