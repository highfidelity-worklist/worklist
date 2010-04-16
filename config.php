<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//
require_once('Zend/Config.php');
require_once('Zend/Config/Ini.php');
require_once('Zend/Registry.php');

if (file_exists('server.local.php')) {
    include_once('server.local.php');
}

if (!defined('APP_NAME'))       define('APP_NAME','Worklist');
if (!defined('APP_LOCATION'))   define('APP_LOCATION',substr($_SERVER['SCRIPT_NAME'], 1, strrpos($_SERVER['SCRIPT_NAME'], '/')));
if (!defined('APP_BASE'))       define('APP_BASE',substr(APP_LOCATION, 0, strrpos(APP_LOCATION, '/', -2)));
if (!defined('APP_PATH'))	define('APP_PATH', realpath(dirname(__FILE__)));
if (!defined('UPLOAD_PATH'))	define('UPLOAD_PATH', realpath(APP_PATH . '/uploads'));

if (!defined('APP_ENV'))	define('APP_ENV', 'production');

//http[s]://[[SECURE_]SERVER_NAME]/[LOCATION/]index.php   #Include a TRAILING / if LOCATION is defined
if (!defined('SERVER_NAME'))    define('SERVER_NAME','dev.sendlove.us');
if (!defined('SERVER_URL'))     define('SERVER_URL','http://'.SERVER_NAME.'/'.APP_LOCATION); //Include [:port] for standard http traffic if not :80
if (!defined('SERVER_BASE'))    define('SERVER_BASE','http://'.SERVER_NAME.'/'.APP_BASE);
//SSL Not enabled on development
//define("SECURE_SERVER_URL",'https://'.SERVER_NAME.'/'.APP_LOCATION); //Secure domain defaults to standard; Include [:port] for secure https traffic if not :443
//So clone the standard URL
if (!defined('SECURE_SERVER_URL')) define('SECURE_SERVER_URL',SERVER_URL); //Secure domain defaults to standard; Include [:port] for secure https traffic if not :443

if (!defined('FEEDBACK_EMAIL')) define('FEEDBACK_EMAIL','feedback@lovemachineinc.com');

if (!defined('DB_SERVER'))      define('DB_SERVER', 'localhost');
if (!defined('DB_USER'))        define('DB_USER', 'project_tofor');
if (!defined('DB_PASSWORD'))    define('DB_PASSWORD', 'test30');
if (!defined('DB_NAME'))        define('DB_NAME', 'worklist_dev');

if (!defined('WORKLIST'))       define('WORKLIST', 'worklist');
if (!defined('USERS'))          define('USERS', 'users');
if (!defined('BIDS'))		define('BIDS', 'bids');
if (!defined('FEES'))		define('FEES', 'fees');
if (!defined('FILES'))		define('FILES', 'files');
if (!defined('COMMENTS'))	define('COMMENTS', 'comments');
if (!defined('REWARDER'))	define('REWARDER', 'rewarder_distribution');
if (!defined('BUDGET_LOG'))	define('BUDGET_LOG', 'budget_log');
if (!defined('REWARDER_LOG'))	define('REWARDER_LOG', 'rewarder_log');
if (!defined('STATUS_LOG'))	define('STATUS_LOG', 'status_log');

if (!defined('SALT'))           define('SALT', 'WORKLIST');
if (!defined('SESSION_EXPIRE')) define('SESSION_EXPIRE', 365*24*60*60);
if (!defined('REQUIRELOGINAFTERCONFIRM')) define('REQUIRELOGINAFTERCONFIRM', 1);

if (!defined('JOURNAL_EXISTS'))     define('JOURNAL_EXISTS', 1);

if (!defined('JOURNAL_QUERY_URL'))  define('JOURNAL_QUERY_URL', 'http://dev.sendlove.us/journal/aj.php');

if (!defined('JOURNAL_API_URL'))    define('JOURNAL_API_URL', 'https://dev.sendlove.us/journal/add.php');
if (!defined('JOURNAL_API_USER'))   define('JOURNAL_API_USER', 'api_username');
if (!defined('JOURNAL_API_PWD'))    define('JOURNAL_API_PWD', 'api_password');

if (!defined("SENDLOVE_API_URL")) define("SENDLOVE_API_URL", "https://dev.sendlove.us/sendlove/api.php");
if (!defined("SENDLOVE_API_KEY"))    define("SENDLOVE_API_KEY", "uierbycur4yt73467t6trtycff3rt");

// Refresh interval for ajax updates of the history table (in seconds)
if (!defined('AJAX_REFRESH'))   define('AJAX_REFRESH', 30);

//pagination vars
if (!defined('QS_VAR'))         define('QS_VAR', 'page');

if (!defined('STR_FWD'))        define('STR_FWD', '&nbsp;&nbsp;Next');
if (!defined('STR_BWD'))        define('STR_BWD', 'Prev&nbsp;&nbsp;');
if (!defined('IMG_FWD'))        define('IMG_FWD', 'images/left.png');
if (!defined('IMG_BWD'))        define('IMG_BWD', 'images/right.png');

/**
 * Clickatell sms gateway settings
 */
if (defined('SMS_INI_FILE') && file_exists(SMS_INI_FILE) && is_readable(SMS_INI_FILE)) {
    $smsIni = new Zend_Config_Ini(SMS_INI_FILE);
    $clickatell = $smsIni->clickatell;
    if ($clickatell !== null) {
        define('CLICKATELL_WSDL',     $clickatell->wsdl);
        define('CLICKATELL_LOCATION', $clickatell->location);
        define('CLICKATELL_API_ID',   $clickatell->api_id);
        define('CLICKATELL_USERNAME', $clickatell->username);
        define('CLICKATELL_PASSWORD', $clickatell->password);
    }
}
if (!defined('CLICKATELL_WSDL'))     define('CLICKATELL_WSDL', null);
if (!defined('CLICKATELL_LOCATION')) define('CLICKATELL_LOCATION', null);
if (!defined('CLICKATELL_API_ID'))   define('CLICKATELL_API_ID', null);
if (!defined('CLICKATELL_USERNAME')) define('CLICKATELL_USERNAME', null);
if (!defined('CLICKATELL_PASSWORD')) define('CLICKATELL_PASSWORD', null);
/*
 * Non-configuration values (CONSTANTS)
 */

// User features: bits in the users.features column
define('FEATURE_SUPER_ADMIN',	0x0001);
define('FEATURE_USER_MASK',	    0x0001);

if (empty($mail_user) || !is_array($mail_user)) {
$mail_user = array (
  'authuser' => array (
    'from' => 'SendLove <love@sendlove.us>',
    'replyto' => 'SendLove <love@sendlove.us>',
    ),
  'smsuser' => array (
    'from' =>  'SendLove SMSReply <sms@sendlove.us>',
    'replyto' => 'SendLove SMSReply <sms@sendlove.us>'
    )
  );
}

// Configuration Array
// New keys can be added to this array, for configuration of features
// and retrieved where needed using Zend_Registry::get('config');
$config = array(
    'database' => array(
    	'name' 	  => 'Worklist',
        'adapter' => 'mysqli',
        'params'  => array(
            'host'     => DB_SERVER,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'dbname'   => DB_NAME,
        )
    ),
    'sms' => array(
        'mailFrom'              => $mail_user['smsuser']['from'],
        'mailReplyTo'           => $mail_user['smsuser']['replyto'],
        'clickatellApiWSDL'     => CLICKATELL_WSDL,
        'clickatellApiLocation' => CLICKATELL_LOCATION,
        'clickatellApiId'       => CLICKATELL_API_ID,
        'clickatellUsername'    => CLICKATELL_USERNAME,
        'clickatellPassword'    => CLICKATELL_PASSWORD
    ),
    'twitter' => array(
    	array(
    	'twitterUsername'		=> 'lovemachineinc',
    	'twitterPassword'		=> 'martini10'
    	),
    	array(
    	'twitterUsername'		=> 'cash4code',
    	'twitterPassword'		=> 'martini10'
    	)
    )
);
// New config object, allows additional merging
Zend_Registry::set('config', new Zend_Config($config, true));
