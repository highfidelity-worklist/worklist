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
} else {
    header("HTTP/1.0 404 Not Found");
    die("Application configuration not found.");
}

if (!defined('DEFAULT_SENDER')) define('DEFAULT_SENDER', 'Worklist <worklist@sendlove.us>');
if (!defined('SMS_SENDER'))     define('SMS_SENDER', 'worklist@lovemachineinc.com');

# Add revision (version) information
if (!defined('APP_REVISION'))   define('APP_REVISION', '$Rev$');
if (!defined('APP_VERSION'))    define('APP_VERSION', preg_replace('/\D/', '', APP_REVISION));
 
// this is the name of the app that will be used when
// authenticating with login service.
// change it per app.
if (!defined("SERVICE_NAME"))   define("SERVICE_NAME", 'worklistmachine');
if (!defined("APP_LOGIN"))      define("APP_LOGIN", '/logon/index.php/');

if (!defined('APP_NAME'))       define('APP_NAME','Worklist');
if (!defined('APP_LOCATION'))   define('APP_LOCATION',substr($_SERVER['SCRIPT_NAME'], 1, strrpos($_SERVER['SCRIPT_NAME'], '/')));
if (!defined('APP_BASE'))       define('APP_BASE',substr(APP_LOCATION, 0, strrpos(APP_LOCATION, '/', -2)));
if (!defined('APP_PATH'))	    define('APP_PATH', realpath(dirname(__FILE__)));
if (!defined('UPLOAD_PATH'))	define('UPLOAD_PATH', realpath(APP_PATH . '/uploads'));

if (!defined('APP_ENV'))	    define('APP_ENV', 'production');

//http[s]://[[SECURE_]SERVER_NAME]/[LOCATION/]index.php   #Include a TRAILING / if LOCATION is defined
if (!defined('SERVER_NAME'))    define('SERVER_NAME','dev.sendlove.us');
if (!defined('SERVER_URL'))     define('SERVER_URL','http://'.SERVER_NAME.'/'.APP_LOCATION); //Include [:port] for standard http traffic if not :80
if (!defined('SERVER_BASE'))    define('SERVER_BASE','http://'.SERVER_NAME.'/'.APP_BASE);
//SSL Not enabled on development
//define("SECURE_SERVER_URL",'https://'.SERVER_NAME.'/'.APP_LOCATION); //Secure domain defaults to standard; Include [:port] for secure https traffic if not :443
//So clone the standard URL
if (!defined('SECURE_SERVER_URL')) define('SECURE_SERVER_URL',SERVER_URL); //Secure domain defaults to standard; Include [:port] for secure https traffic if not :443
if (!defined("LOGIN_APP_URL"))  define("LOGIN_APP_URL",'https://'.SERVER_NAME.APP_LOGIN);
if (!defined('SVN_BASE_URL'))   define('SVN_BASE_URL', 'http://svn.sendlove.us/listing.php?repname=');
if (!defined('FEEDBACK_EMAIL')) define('FEEDBACK_EMAIL', 'feedback@lovemachineinc.com');
if (!defined('FINANCE_EMAIL'))  define('FINANCE_EMAIL', 'finance@lovemachineinc.com');

if (!defined('DB_SERVER'))      define('DB_SERVER', 'mysql.dev.sendlove.us');
if (!defined('DB_USER'))        define('DB_USER', 'project_cupid');
if (!defined('DB_PASSWORD'))    define('DB_PASSWORD', 'test30');
if (!defined('DB_NAME'))        define('DB_NAME', 'worklist_joanne');

if (!defined('WS_SESSIONS'))    define('WS_SESSIONS', 'ws_sessions');
if (!defined('TOKENS'))         define('TOKENS', 'tokens');

if (!defined('WORKLIST'))       define('WORKLIST', 'worklist');
if (!defined('USERS'))          define('USERS', 'users');
if (!defined('BIDS'))		    define('BIDS', 'bids');
if (!defined('FEES'))		    define('FEES', 'fees');
if (!defined('FILES'))		    define('FILES', 'files');
if (!defined('COMMENTS'))	    define('COMMENTS', 'comments');
if (!defined('USER_STATUS'))	define('USER_STATUS', 'user_status');
if (!defined('BUDGET_LOG'))	    define('BUDGET_LOG', 'budget_log');
if (!defined('SKILLS'))         define('SKILLS', 'skills');
if (!defined('WORKITEM_SKILLS')) define('WORKITEM_SKILLS', 'workitem_skills');
if (!defined('STATUS_LOG'))	    define('STATUS_LOG', 'status_log');
if (!defined('PAYPAL_LOG'))     define('PAYPAL_LOG', 'paypal_log');
if (!defined('PAYPAL_ADMINS'))  define('PAYPAL_ADMINS', 'paypal_admins');
if (!defined('PROJECTS'))       define('PROJECTS', 'projects');
if (!defined('PROJECT_USERS'))  define('PROJECT_USERS', 'project_users');

if (!defined('SALT'))           define('SALT', 'WORKLIST');
if (!defined('SESSION_EXPIRE')) define('SESSION_EXPIRE', 365*24*60*60);
if (!defined('REQUIRELOGINAFTERCONFIRM')) define('REQUIRELOGINAFTERCONFIRM', 1);

if (!defined("WORKLIST_URL"))   define("WORKLIST_URL", "http://dev.sendlove.us/worklist");

//<joanne>
if (!defined("SENDLOVE_URL"))   define("SENDLOVE_URL", "http://lovemachine.sendlove.us/love");


if (!defined('JOURNAL_EXISTS')) define('JOURNAL_EXISTS', 1);

if (!defined('JOURNAL_QUERY_URL')) define('JOURNAL_QUERY_URL', 'http://dev.sendlove.us/journal/aj.php');

if (!defined('JOURNAL_API_URL')) define('JOURNAL_API_URL', 'https://dev.sendlove.us/journal/add.php');
if (!defined('JOURNAL_API_USER')) define('JOURNAL_API_USER', 'api_username');
if (!defined('JOURNAL_API_PWD')) define('JOURNAL_API_PWD', 'api_password');
if (!defined('JOURNAL_API_KEY')) define('JOURNAL_API_KEY', 'api_key');

if (!defined("SENDLOVE_API_URL")) define("SENDLOVE_API_URL", "https://dev.sendlove.us/love/api.php");
if (!defined("SENDLOVE_API_KEY")) define("SENDLOVE_API_KEY", "uierbycur4yt73467t6trtycff3rt");

if (!defined("REWARDER_API_URL")) define("REWARDER_API_URL", "https://dev.sendlove.us/review/api.php");
if (!defined("REWARDER_API_KEY")) define("REWARDER_API_KEY", "dhfsfdhgdhsfg7g5fyg73ff23545f32fwd");

if (!defined("SALES_API_URL")) define("SALES_API_URL", "https://dev.sendlove.us/sales/api.php");
if (!defined("SALES_API_KEY")) define("SALES_API_KEY", "qxuakwyjhqp4zo7wt2ie");

// key to identificate api users
if (!defined("API_KEY"))    define("API_KEY", "08aaa337ac04179120f8dc88c55dd1ad");

// Refresh interval for ajax updates of the history table (in seconds)
if (!defined('AJAX_REFRESH'))   define('AJAX_REFRESH', 120);

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
define('FEATURE_SUPER_ADMIN',       0x0001);
define('FEATURE_USER_MASK',         0x0001);

define('SMS_FLAG_JOURNAL_ALERTS',   0x0001);
define('SMS_FLAG_BID_ALERTS',       0x0002);

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
    'email' => array(
        'mailFrom'              => DEFAULT_SENDER,
        'mailReplyTo'           => defined('DEFAULT_REPLYTO')?DEFAULT_REPLYTO:DEFAULT_SENDER,
        'host'                  => GOOGLE_HOST,
        'port'                  => GOOGLE_PORT,
        'username'              => GOOGLE_USER,
        'password'              => GOOGLE_PWD,
        'auth'                  => GOOGLE_AUTH

    ),
    'sms' => array(
        'mailFrom'              => SMS_SENDER,
        'mailReplyTo'           => defined('SMS_REPLYTO')?SMS_REPLYTO:SMS_SENDER,
        'clickatellApiWSDL'     => CLICKATELL_WSDL,
        'clickatellApiLocation' => CLICKATELL_LOCATION,
        'clickatellApiId'       => CLICKATELL_API_ID,
        'clickatellUsername'    => CLICKATELL_USERNAME,
        'clickatellPassword'    => CLICKATELL_PASSWORD
    ),
    'twitter' => array(
    	array(
    	'twitterUsername'		=> TWITTER_USER,
    	'twitterPassword'		=> TWITTER_PASS,
    	),
    	array(
    	'twitterUsername'		=> TWITTER_2_USER,
    	'twitterPassword'		=> TWITTER_2_PASS,
    	)
    ),
    'websvn' => array(
        'baseUrl'           => 'http://svn.sendlove.us',
        'repLinkFragment'   => 'listing.php?repname='
    )
);
// New config object, allows additional merging
Zend_Registry::set('config', new Zend_Config($config, true));
// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);

// time constants
if (! defined('BID_EXPIRE_WARNING')) define('BID_EXPIRE_WARNING', 7200);
