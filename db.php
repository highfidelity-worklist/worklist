<?php 
/*
 * Setup reusable Zend_db objects and stores them to the Zend_Registry.
 * 
 * This assumes that config.php has already been included previously.
 * 
 */
require_once('Zend/Db.php');
$config = Zend_Registry::get('config');
// This does not make a db connection.  Must be done later using $db->getConnection();
Zend_Registry::set('db', Zend_Db::factory($config->database));





