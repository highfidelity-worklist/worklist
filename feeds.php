<?php
/*
 * Copyright (c) 2010, LoveMachine Inc.
 * All Rights Reserved. 
 * http://www.lovemachineinc.com
 * 
 * Feeds.php
 * Paramters:  name= name_of_feed (priority | completed)
 * 			   format=atom | rss
 */
require_once('config.php');
require_once('db.php');
require_once('Zend/Feed/Writer/Feed.php');

function addEntry($writer, $entryData, $entryDescription) {
	$entry = $writer->createEntry();
	$entry->setTitle($entryData['title']); 
	$entry->setLink(SERVER_URL . 'workitem.php?job_id=' . $entryData['worklist_id'] . '&action=view'); 
	// must supply a non-empty value for description
	$content = !empty($entryData['content']) ? $entryData['content'] : "N/A";
	$entry->setDescription($content);   
	//$entry->setContent($entryData['content']); 
	$entry->setDateCreated(time()); 
	$entry->setDateModified(time()); 
	$entry->addAuthor($entryData['author'], $entryData['email']); 
	$writer->addEntry($entry); // manual addition post-creation 
}

function loadFeed($writer, $query, $entryDescription) {
	$db = Zend_Registry::get('db');
	$result = $db->fetchAll($query);
	if ($result) {
		foreach($result as $row) {
			addEntry($writer, $row, $entryDescription);
		}
	}
}

$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'rss';
if (!($format == 'atom' || $format == 'rss')) {
	$format = 'rss';
} 
$name = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'completed';
// Connect to db
try {
	$db = Zend_Registry::get('db');
	$db->getConnection();
} catch (Zend_Db_Adapter_Exception $e) {
    // failed login, db not running
    
} catch (Zend_Exception $e) {
	// factory() failed load of mysqli
} 

switch ($name) {
	case 'priority' :
		$name = 'priority';
		$title = 'Worklist Top Priority Bidding Jobs';
		$description = 'dev.sendlove.us Worklist, highest priority jobs Bidding';
		$entryDescription = 'Worklist priority item';
		$query = "SELECT w.id as worklist_id, u1.nickname as author, username as email, summary as title, notes as content
					FROM worklist w
					JOIN users u1 ON u1.id = w.creator_id AND w.status = 'BIDDING'
					ORDER BY priority LIMIT 20";
		break;
	case 'completed' : 
	default :
		$name = 'completed';
		$title = 'Worklist most Recent completed jobs';
		$description = 'dev.sendlove.us Worklist, Most recent completed Jobs';
		$entryDescription = 'Worklist priority item';
		$query = "SELECT w.id as worklist_id, u1.nickname as author, username as email, summary as title, notes as content
					FROM worklist w
					JOIN users u1 ON u1.id = w.creator_id AND w.status = 'DONE'
					ORDER BY created DESC LIMIT 20";
}

$url = 'http://dev.sendlove.us/';
$link = $url . "feed.php?name=$name&format=$format";
$writer = new Zend_Feed_Writer_Feed; 
$writer->setTitle($title); 
$writer->setLink(SERVER_URL); 
$writer->setFeedLink(SERVER_URL . $link, $format);
$writer->setDescription($description);
$writer->setDateModified(time()); //Atom needs this  
loadFeed($writer, $query, $entryDescription);

echo $writer->export($format); 