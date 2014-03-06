<?php

use \Michelf\Markdown;

class EntryModel extends Model {
    protected $table = 'entries';

    public function latest($minutes_ago = 0, $max_limit = 0, $offset = 0) {
        $cond = $minutes_ago ? 'DATESUB(NOW(), ' + $minutes_ago + ' minutes) < date' : '';
        return $this->loadMany($cond, 'date DESC', $max_limit, $offset);
    }

    public function latestFromTask($job_id, $order = 'date DESC', $max_limit = 0, $offset = 0) {
        $cond = "entry REGEXP '#" . $job_id . "[^0-9]?'";
        return $this->loadMany($cond, $order, $max_limit, $offset);
    }

    public function notify($message) {
        $username = mysql_real_escape_string(JOURNAL_API_USER);
        $password = mysql_real_escape_string(sha1(JOURNAL_API_PWD));
        $sql = "select id, nickname from ".USERS." where username='$username' and password='$password' and confirm='1'";
        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_assoc($res);
            $this->id = null;
            $this->user_id = $row['id'];
            $this->author = $row['nickname'];
            $this->ip = $_SERVER['REMOTE_ADDR'];
            $this->sampled = 0;
            $this->entry = $message;
            $this->date = 'CURRENT_TIMESTAMP';
            return $this->insert();
        }
        return false;
    }

    public function markdownEntry() {
        return Markdown::defaultTransform($this->entry);
    }

    public function oldFormatEntry() {
        return strtotime($this->date) < strtotime('2014-03-07 00:00:00');
    }
}