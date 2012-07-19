<?php
//
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

//Joanne added the following line for the journal attachments
$current_dir = dirname(__FILE__) . '/';

require_once('Zend/Config.php');
require_once('Zend/Config/Ini.php');
require_once('Zend/Registry.php');

// Use this function to not overwrite values that were previously
// specified in server.local.php
// @TODO: Migrate all constants to use this handy function :)
function defineOnce($key, $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

if (file_exists(dirname(__FILE__).'/server.local.php')) {
    include_once(dirname(__FILE__).'/server.local.php');
} else {
    header("HTTP/1.0 404 Not Found");
    define('WORKLIST_NUMBER', substr(dirname(__FILE__), 1 + strrpos(dirname(__FILE__), '/')));
    $tmp_username =  substr(dirname(__FILE__), 0 , strrpos(dirname(__FILE__), '/public_html/'));
    define('USERNAME2', substr($tmp_username, strrpos($tmp_username, '/dev-www/') + 9));
    die('Application configuration not found.<br/>'.
        'Run the following commands to set up your sandbox:<br/>'.
        'cp ' . dirname(__FILE__). '/server.local.php.default ' . dirname(__FILE__). '/server.local.php<br/> ' .
        'sed -ie "s/ini_set(\'html_errors\',FALSE);/ini_set(\'html_errors\',TRUE);/" ' . dirname(__FILE__) . '/server.local.php <br/>' .
        'sed -ie "s/define(\'SANDBOX_NAME\', \'worklist\/\');/define(\'SANDBOX_NAME\', \'' . WORKLIST_NUMBER . '\/\');/" ' . dirname(__FILE__) . '/server.local.php <br/> ' .
        'sed -ie "s/define(\'SANDBOX_USER\', \'\');/define(\'SANDBOX_USER\', \'~'. USERNAME2 . '\');/" ' . dirname(__FILE__) . '/server.local.php <br/> ' .
        'rm ' . dirname(__FILE__) . '/server.local.phpe <br/> ' 
        );
}

defineOnce('MYSQL_DEBUG_LEVEL', 0);
defineOnce('MYSQL_DEBUG_MESSAGE_DEFAULT', 'General database error');


if (!defined('DEFAULT_SENDER')) define('DEFAULT_SENDER', 'Worklist <worklist@worklist.net>');
if (!defined('SMS_SENDER'))     define('SMS_SENDER', 'worklist@worklist.net');

# Add revision (version) information
if (!defined('APP_REVISION'))   define('APP_REVISION', '$Rev$');
if (!defined('APP_VERSION'))    define('APP_VERSION', preg_replace('/\D/', '', APP_REVISION));
 
// this is the name of the app that will be used when
// authenticating with login service.
// change it per app.
if (!defined("SERVICE_NAME"))   define("SERVICE_NAME", 'worklistmachine');

if (!defined('APP_NAME'))       define('APP_NAME','Worklist');
if (!defined('APP_LOCATION'))   define('APP_LOCATION',substr($_SERVER['SCRIPT_NAME'], 1, strrpos($_SERVER['SCRIPT_NAME'], '/')));
if (!defined('APP_BASE'))       define('APP_BASE',substr(APP_LOCATION, 0, strrpos(APP_LOCATION, '/', -2)));
if (!defined('APP_PATH'))       define('APP_PATH', realpath(dirname(__FILE__)));
if (!defined('UPLOAD_PATH'))    define('UPLOAD_PATH', realpath(APP_PATH . '/uploads'));

if (!defined('APP_ENV'))        define('APP_ENV', 'production');

//http[s]://[[SECURE_]SERVER_NAME]/[LOCATION/]index.php   #Include a TRAILING / if LOCATION is defined
if (!defined('SERVER_NAME'))    define('SERVER_NAME','dev.worklist.net');
if (!defined('SANDBOX_SERVER')) define('SANDBOX_SERVER','dev.worklist.net');
if (!defined('SERVER_URL'))     define('SERVER_URL','https://'.SERVER_NAME.'/'.APP_LOCATION); //Include [:port] for standard http traffic if not :80
if (!defined('SERVER_BASE'))    define('SERVER_BASE','https://'.SERVER_NAME.'/'.APP_BASE);
//SSL Not enabled on development
//Secure domain defaults to standard; Include [:port] for secure https traffic if not :443
if (!defined('SECURE_SERVER_URL')) define("SECURE_SERVER_URL",'https://'.SERVER_NAME.'/'.APP_LOCATION);
if (!defined('SVN_BASE_URL'))   define('SVN_BASE_URL', 'http://svn.worklist.net/listing.php?repname=');
if (!defined('FEEDBACK_EMAIL')) define('FEEDBACK_EMAIL', 'feedback@lovemachineinc.com');
if (!defined('FINANCE_EMAIL'))  define('FINANCE_EMAIL', 'finance@lovemachineinc.com');

if (!defined('DB_SERVER'))      define('DB_SERVER', 'mysql.dev.sendlove.us');
if (!defined('DB_USER'))        define('DB_USER', 'project_cupid');
if (!defined('DB_PASSWORD'))    define('DB_PASSWORD', 'test30');
if (!defined('DB_NAME'))        define('DB_NAME', 'worklist_joanne');


//worklist tables
if (!defined('BIDS'))           define('BIDS', 'bids');
if (!defined('BUDGETS'))        define('BUDGETS', 'budgets');
if (!defined('BUDGET_SOURCE'))  define('BUDGET_SOURCE', 'budget_source');
if (!defined('COMMENTS'))       define('COMMENTS', 'comments');
if (!defined('COUNTRIES'))     define('COUNTRIES', 'countries');
if (!defined('FEES'))           define('FEES', 'fees');
defineOnce('USERS_FAVORITES', 'rel_users_favorites');
if (!defined('FILES'))          define('FILES', 'files');
if (!defined('FUNDS'))          define('FUNDS', 'funds');
if (!defined('PAYPAL_ADMINS'))  define('PAYPAL_ADMINS', 'paypal_admins');
if (!defined('PAYPAL_LOG'))     define('PAYPAL_LOG', 'paypal_log');
if (!defined('PROJECTS'))       define('PROJECTS', 'projects');
if (!defined('PROJECT_USERS'))  define('PROJECT_USERS', 'project_users');
if (!defined('ROLES'))          define('ROLES', 'roles');
if (!defined('SKILLS'))         define('SKILLS', 'skills');
if (!defined('STATUS_LOG'))     define('STATUS_LOG', 'status_log');
if (!defined('TASK_FOLLOWERS')) define('TASK_FOLLOWERS', 'task_followers');
if (!defined('TOKENS'))         define('TOKENS', 'tokens');
if (!defined('WORKITEM_SKILLS')) define('WORKITEM_SKILLS', 'workitem_skills');
if (!defined('USER_STATUS'))    define('USER_STATUS', 'user_status');
if (!defined('USERS'))          define('USERS', 'users');
if (!defined('WORKLIST'))       define('WORKLIST', 'worklist');
if (!defined('WS_SESSIONS'))    define('WS_SESSIONS', 'ws_sessions');

//journal tables
if (!defined("RECENT_SPEAKERS")) define("RECENT_SPEAKERS","recent_speakers");
if (!defined("TYPING_STATUS"))   define("TYPING_STATUS","typing_status");
if (!defined("ENTRIES")) 	     define("ENTRIES","entries");
defineOnce("ENTRIES_ALL", "entries2");

if (!defined("TOKENS")) 	     define("TOKENS","tokens");
if (!defined("SURVEYS")) 	     define("SURVEYS","surveys");
if (!defined("SURVEY_CHOICES"))  define("SURVEY_CHOICES","survey_choices");
if (!defined("SURVEY_VOTERS"))   define("SURVEY_VOTERS","survey_voters");
if (!defined("PENALTIES"))	     define("PENALTIES", "penalties");
if (!defined("ENTRYJOBS"))	     define("ENTRYJOBS", "entry_jobs");
if (!defined("BOTDATA"))         define("BOTDATA", "botdata");
if (!defined("JOURNAL_FILES"))   define("JOURNAL_FILES", "journal_files");
if (!defined("LATENCY_LOG"))	 define("LATENCY_LOG", "latency_log");
if (!defined("BLOCKED_IP"))		 define("BLOCKED_IP", "blocked_ip");

if (!defined('SALT'))            define('SALT', 'WORKLIST');
if (!defined('SESSION_EXPIRE'))  define('SESSION_EXPIRE', 365*24*60*60);
if (!defined('REQUIRELOGINAFTERCONFIRM')) define('REQUIRELOGINAFTERCONFIRM', 1);

if (!defined("LATENCY_SAMPLE")) define("LATENCY_SAMPLE", 100); // 0-100: percentage of journal entries to sample

// Penalty box timeouts in seconds
if (!defined("PB_TIMEOUT_1")) 	define("PB_TIMEOUT_1", 120);
if (!defined("PB_TIMEOUT_2")) 	define("PB_TIMEOUT_2", 300);
if (!defined("PB_TIMEOUT_3")) 	define("PB_TIMEOUT_3", 600);

// user timeout in minutes
if (!defined('USER_TIMEOUT'))    define('USER_TIMEOUT', 30);
if (!defined('USER_IDLETIME'))    define('USER_IDLETIME', 15);

if (!defined("WORKLIST_URL"))   define("WORKLIST_URL", "https://dev.worklist.net/worklist/");

//<joanne>
if (!defined("SENDLOVE_URL"))   define("SENDLOVE_URL", "https://lovemachine.sendlove.us/love");
if (!defined("REWARDER_URL"))   define("REWARDER_URL", "http://lovemachine.sendlove.us/love/tofor.php?tab=1");

//<john>
if (!defined("JOURNAL_URL")) define('JOURNAL_URL', SERVER_URL . "worklist/journal.php");

if (!defined('JOURNAL_EXISTS')) define('JOURNAL_EXISTS', 1);

if (!defined('JOURNAL_QUERY_URL')) define('JOURNAL_QUERY_URL', 'https://dev.worklist.net/worklist/aj.php');

if (!defined("SURVEYS_EMAIL")) define("SURVEYS_EMAIL","all@lovemachineinc.com");
if (!defined("JOURNAL_PICTURE_EMAIL_PREFIX")) define("JOURNAL_PICTURE_EMAIL_PREFIX","devjournalimage");
if (!defined("JOURNAL_PICTURE_EMAIL_DOMAIN")) define("JOURNAL_PICTURE_EMAIL_DOMAIN","@sendlove.us");

//<kordero>
if(!defined("ADMINS_EMAILS"))
  define("ADMINS_EMAILS", 
    "ryan@lovemachineinc.com " . 
    "garth.johnson@gmail.com " . 
    "philip@lovemachineinc.com " . 
    "tj@coffeeandpower.com " . 
    "danbrown@php.net " . 
    "heiberger@earthlink.net " . 
    "fred@lovemachineinc.com " . 
    "alexi@kostibas.com"
  );

if(!defined('WIKI_URL')) define('WIKI_URL', 'http://wiki.worklist.net/wiki/');

// Special Accounts
if (!defined("USER_SENDLOVE"))      define("USER_SENDLOVE", "Send Love");
if (!defined("USER_SVN"))           define("USER_SVN", "SVN");
if (!defined('USER_SVN_ID'))	    define('USER_SVN_ID', 1339);
if (!defined("USER_SCHEMAUPDATE"))      define("USER_SCHEMAUPDATE", "Schema Update");
if (!defined('USER_SCHEMAUDPDATE_ID'))  define('USER_SCHEMAUPDATE_ID', 2267);
if (!defined("USER_AUTOTESTER"))    define("USER_AUTOTESTER", "AutoTester");
if (!defined('USER_AUTOTESTER_ID')) define('USER_AUTOTESTER_ID', 2470);
if (!defined("USER_WORKLIST"))      define("USER_WORKLIST", "Work List");
if (!defined('USER_JOURNAL'))	    define('USER_JOURNAL', 'Journal');
if (!defined('USER_JOURNAL_ID'))    define('USER_JOURNAL_ID', 1430);
if (!defined('USER_SALES'))         define('USER_SALES', 'Sales');
if (!defined('USER_SALES_ID'))      define('USER_SALES_ID', 1854);
if (!defined('USER_SITESCAN'))	    define('USER_SITESCAN','SiteScan');
if (!defined('USER_SITESCAN_ID'))   define('USER_SITESCAN_ID',462);

if (!defined('BOT_USER_ID'))    define('BOT_USER_ID', 1509);
if (!defined('GUEST_NAME'))    define('GUEST_NAME', 'Guest');

//Unspecified away text
if (!defined('NOMESSAGE'))    define('NOMESSAGE', 'Currently away');

if (!defined('BOTLIST'))        define('BOTLIST', 'him,me,love,survey,ping,');

if (!defined("SENDLOVE_API_URL")) define("SENDLOVE_API_URL", "https://dev.sendlove.us/love/api.php");
if (!defined("SENDLOVE_API_KEY")) define("SENDLOVE_API_KEY", "uierbycur4yt73467t6trtycff3rt");

if (!defined("REWARDER_API_URL")) define("REWARDER_API_URL", "https://dev.sendlove.us/review/api.php");
if (!defined("REWARDER_API_KEY")) define("REWARDER_API_KEY", "dhfsfdhgdhsfg7g5fyg73ff23545f32fwd");

if (!defined("SALES_API_URL")) define("SALES_API_URL", "https://dev.sendlove.us/sales/api.php");
if (!defined("SALES_API_KEY")) define("SALES_API_KEY", "qxuakwyjhqp4zo7wt2ie");

// key to identificate api users
if (!defined("API_KEY"))    define("API_KEY", "worklist_dev_worklist_unsecure");

// update touch file
if (!defined('JOURNAL_UPDATE_TOUCH_FILE')) define('JOURNAL_UPDATE_TOUCH_FILE', '/tmp/journal_update');

// Refresh interval for ajax updates of the history table (in seconds)
if (!defined('AJAX_REFRESH'))   define('AJAX_REFRESH', 120);

//pagination vars
if (!defined('QS_VAR'))         define('QS_VAR', 'page');

if (!defined('STR_FWD'))        define('STR_FWD', '&nbsp;&nbsp;Next');
if (!defined('STR_BWD'))        define('STR_BWD', 'Prev&nbsp;&nbsp;');
if (!defined('IMG_FWD'))        define('IMG_FWD', 'images/left.png');
if (!defined('IMG_BWD'))        define('IMG_BWD', 'images/right.png');

if (!defined("TESTFLIGHT_API_TOKEN"))    define("TESTFLIGHT_API_TOKEN", "c5ae8c56e6ac6e6d5aefceb711070261_MTA5MzE3");

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
        'name'    => 'Worklist',
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
        'twitterUsername'       => TWITTER_USER,
        'twitterPassword'       => TWITTER_PASS,
        ),
        array(
        'twitterUsername'       => TWITTER_2_USER,
        'twitterPassword'       => TWITTER_2_PASS,
        )
    ),
    'websvn' => array(
        'baseUrl'           => 'http://svn.worklist.net',
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

/** Journal Attachments **/
if (!defined("ATTACHMENT_URL")) define("ATTACHMENT_URL",SERVER_URL . "helper/get_attachment.php");
/** File uploads / S3 settings **/
/* These allow upload of profile images to S3 */
if (!defined('S3_ACCESS_KEY')) define('S3_ACCESS_KEY', 'AKIAIXRYPAC4HCOWBXMQ');
if (!defined('S3_SECRET_KEY')) define('S3_SECRET_KEY', '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');
defineOnce('S3_BUCKET', 'worklist-dev');
if (!defined('S3_URL_BASE')) define('S3_URL_BASE', 'https://'.S3_BUCKET.'.s3.amazonaws.com/');
defineOnce('APP_IMAGE_PATH', 'image/');
defineOnce('APP_ATTACHMENT_PATH', 'attachment/');
defineOnce('APP_INTERNAL_PATH', 'internal/');
defineOnce('APP_IMAGE_URL', S3_URL_BASE . APP_IMAGE_PATH);
defineOnce('APP_ATTACHMENT_URL', S3_URL_BASE . APP_ATTACHMENT_PATH);
defineOnce('APP_INTERNAL_URL', 'https://s3.amazonaws.com/' . S3_BUCKET . '/' . APP_INTERNAL_PATH);

if (! defined('VIRUS_SCAN_CMD')) define('VIRUS_SCAN_CMD', '/usr/bin/clamscan');

// max thumbnail size
if (!defined('MAX_THUMB_SIZE')) define('MAX_THUMB_SIZE', 500);

/*  budget authorized users
    Only Ryan (2), Philip (1020) & Fred (1918) can add projects! In order to work on the add projects page in your sb,
    your userid must be included below. Just remove when done!
    Adding alexi and jeskad - alexi 2011-10-26
*/
if (!defined('BUDGET_AUTHORIZED_USERS')) define('BUDGET_AUTHORIZED_USERS', ",2,1020,1918,");

// Delimiter used in linkify to separate encoded html from uncoded one.
defineOnce('DELIMITER', 'xxxXXXxxxYYYxxxXXXxxx');

defineOnce('REVIEW_NOTIFICATIONS_CRON_FILE', '/tmp/reviewNotificationsCron.dat');

require_once('sanitization.php');



