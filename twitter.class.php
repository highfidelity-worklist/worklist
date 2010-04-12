<?php
//  vim:ts=4:et

/**
 * Twitter
 *
 * @package Twitter
 * @version $Id$
 */

class Twitter
{
    protected $username;
    protected $password;

    public function __construct()
    {
        if (!mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if (!mysql_select_db(DB_NAME)) {
            throw new Exception('Error: ' . mysql_error());
        }
    }

    public function setStatus($status, $config) {
    	if(is_array($config)) {
    		$this->username = $config['twitterUsername'];
    		$this->password	= $config['twitterPassword'];
    	}

    	$data = array();
    	$data['status'] = $status;

    	$ch = curl_init('http://twitter.com/statuses/update.json');
    	curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
    	$twitter_result_json = curl_exec($ch);
    	curl_close($ch);
    	$twitter_result = json_decode($twitter_result_json);
    }
}
?>
