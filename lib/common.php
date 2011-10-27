<?php

/*	============================================
	THE MINECRAFT MOD INDEX
	COMMON LIBRARY AND SETTINGS


	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
 */

// The SimPlaza Common PHP Library
require_once(__DIR__.'/../../common/c.php');

//
require_once('common.config.php');

php_includeonly(__FILE__);

// Unfucks form data
if ( get_magic_quotes_gpc() ) {
	foreach ($_GET as $key => &$val) $val = stripslashes($val);
	foreach ($_POST as $key => &$val) $val = stripslashes($val);
	foreach ($_COOKIE as $key => &$val) $val = stripslashes($val);
}

// =========
// CONSTANTS
// =========

define(VERSION,   1.3);
define(TESTING,   ($_SERVER['HTTP_HOST'] == HOSTNAME_TESTING));

define(VERSION_MINECRAFT_WILDCARD, 'any|all(\sversions)?');
define(VERSION_MINECRAFT,          '@^('. VERSION_MINECRAFT_CURRENT .'|' . VERSION_MINECRAFT_WILDCARD . ')$@i');

// ===== Regexes
define(REGEX_OPENBRACKETS, '(?:\[|\(|\{)');
define(REGEX_CLOSEBRACKETS, '(?:\]|\)|\})');

define(REGEX_VERSION_PREFIXES, '(?:b|beta|v\.?|ver|version|for version|minecraft\s?v?|modloader|mc)?');
define(REGEX_VERSION_BASICNUMERICAL, '\s?(1\.\d(?:(?:\.|\_)\d{1,2})?(?:(?:_|-|\s)?pre?(\s?[0-9])?)?)\s?');
define(REGEX_VERSION_BASICWILDCARD, '\s?(' . VERSION_MINECRAFT_WILDCARD . ')\s?');
define(REGEX_VERSION_NUMERICAL, REGEX_VERSION_PREFIXES . REGEX_VERSION_BASICNUMERICAL . REGEX_VERSION_PREFIXES);
define(REGEX_VERSION, '@' . REGEX_OPENBRACKETS . '\s?' . REGEX_VERSION_NUMERICAL . '\s?(?:(?:/|-|,)\s?' . REGEX_VERSION_NUMERICAL . '\s?)*(?:b|beta)?\s?' . REGEX_CLOSEBRACKETS . '@i');
define(REGEX_VERSIONWILDCARD, '@' . REGEX_OPENBRACKETS . REGEX_VERSION_BASICWILDCARD . REGEX_CLOSEBRACKETS . '@i');
define(REGEX_VERSIONBASIC, '@' . REGEX_VERSION_BASICNUMERICAL . '@i');

define(REGEX_SPAM_TERMS, '\s?(?:req(uest)?|del(ete)?|help|coming\ssoon|closed|discontinued|w(ork\s?)?i(n\s?)?p(rogress)?|tut(orial)?)?\s?');
define(REGEX_SPAM_BEGGING, 'help|question|req(u|w)est|how\sdo\si|\?|idea|problem|how to|seeking|please|need|abandoned|looking|issues|closed');
define(REGEX_SPAM_MALICIOUS, 'minecr?aft code|codes|hentai|giveaway|prem\\s?account|give \d{1,2} minecraft|free|poker|dress|cialis|viagra|sony|adf\.?ly');
define(REGEX_SPAM_MALICIOUS2, 'karen|hold\'em|order|sale|discount|research|phone|contract|cheap');
define(REGEX_SPAM, '@(' . REGEX_OPENBRACKETS . REGEX_SPAM_TERMS . REGEX_CLOSEBRACKETS . '|' . REGEX_SPAM_BEGGING . '|' . REGEX_SPAM_MALICIOUS . '|' . REGEX_SPAM_MALICIOUS2 . ')@i');

// ===== URLs
define(URL_INDEX,    'http://'.(TESTING ? URL_INDEX_TESTING : URL_INDEX_LIVEFIRE) );
define(URL_FORUM,    'http://minecraftforum.net/forum/51-released-mods/page__prune_day__15__sort_by__A-Z__sort_key__title__topicfilter__open__st__');
define(URL_TOPIC,    'http://minecraftforum.net/index.php?showtopic=');
define(URL_USERFULL, 'http://www.minecraftforum.net/user/');
define(URL_USER,     'http://minecraftforum.net/user/');

// ===== Files
define(DIRECTORY_DATABASE,    'data/');
define(DIRECTORY_ARCHIVES,    'archives/');
define(DIRECTORY_STATISTICS,  DIRECTORY_DATABASE . 'stats/');

define(FILENAME_STATISTICS, 'statistics.db');
define(FILENAME_INDEX,      'index.db');
define(FILENAME_METADATA,   'metadata.db');

define(FILE_INDEX,      DIRECTORY_DATABASE . FILENAME_INDEX);
define(FILE_METADATA,   DIRECTORY_DATABASE . FILENAME_METADATA);
define(FILE_TEMPINDEX,  DIRECTORY_DATABASE . 'tempindex.db');
define(FILE_BLACKLIST,  DIRECTORY_DATABASE . 'blacklist.db');
define(FILE_JOB,        DIRECTORY_DATABASE . 'job.db');
define(FILE_EMAIL,      DIRECTORY_DATABASE . 'email.db');
define(FILE_MOTW,       DIRECTORY_DATABASE . 'motw.db');

define(NOTIFICATION_VERSION, 0);
define(NOTIFICATION_TITLE, 1);

$ENUM_INDEXFIELDS = array(
    'title',
    'version',
    'desc',
    'author',
    'author_id',
    'views',
	'time_created',
    'time_indexed'
);

$ENUM_METADATAFIELDS = array(
    'keywords',
    'flag_collection',
    'flag_adfly',
    'flag_depends',
    'flag_smp'
);

$ENUM_INDEXFLAGS = array(
    'flag_collection' => 'Collection',
    'flag_adfly' => 'Adf.ly',
    'flag_modloader' => 'Modloader',
    'flag_depends' => 'Dependency',
    'flag_smp' => 'Multiplayer'
);

// ==============
// FILE FUNCTIONS
// ==============
// Reads a file as string
function file_read($filename, $error) {
    $file = file_get_contents($filename) or die($error);

    return $file;
}

function file_readline($filename, $error) {
    $file = fopen($filename, 'r') or die($error);
    $string = fgets($file);
    fclose($file);

    return $string;
}

// Basic file/database writing function
function file_write($filename, $data, $error, $append = FALSE) {
    $file = fopen($filename, ($append ? 'a' : 'w')) or die($error);
    fwrite($file, $data);
    fclose($file);

    unset($file);
}

// Plain and simple Index (PHP serialized array format) loader
function index_load($file) {
    if ( !is_file($file) )
        return array();
    else
        return unserialize( file_get_contents($file) );
}

function index_require($file) {
    if ( !is_file($file) )
        die('Call to index_require failed: Index ' . $file . ' does not exist!');

    return unserialize(file_get_contents($file));
}

// Plain and simple Index writer
function index_save($file, $data) {
    file_write($file, serialize($data), 'Unable to write index: ' . $file);
}

// Picks an up-to-date index row at random (ONLY SUITABLE FOR THE MAIN INDEX)
function index_random($index) {
    while (true) {
        if (empty($index))
            die('Call to index_random failed: No up-to-date rows avaliable!');

        $rand = array_rand($index);

        // Unversioned? Skip.
        if (!$index[$rand]['version']) {
            unset($index[$rand]);
            continue;
        }

        if (preg_match(VERSION_MINECRAFT, $index[$rand]['version']))
            return $rand;
        else
            unset($index[$rand]);
        
    }
}

function get_timestamp($name) {
    preg_match('@[a-z]+\.db([0-9]+)@i', $name, $matches);
    return $matches[1];
}

// ==================
// SECURITY FUNCTIONS
// ==================

function auth_access(&$args) {
    if ($_GET['admin'] == AUTH_ADMIN || $_GET['admin'] == AUTH_ADMIN_LOWER)
        return true;
    else if ($args[1] == AUTH_EXECUTION)
        return true;
    else
        header("HTTP/1.0 403 Forbidden"); die('Authentication failure.');
}

function email_alert($from, $subject, $data) {
	$message = '<body>'.$data.'</body>';
	
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$headers .= 'From: MMI Alert <'.$from.'@' . $_SERVER['HTTP_HOST'] . '>' . "\r\n";
	
	// Mail it
	mail(EMAIL_ADMIN, $subject, $message, $headers);
}

// ====================
// FORMATTING FUNCTIONS
// ====================
// Generates the "last updated" string in human readable format.
function time_ago() {
    $time = time() - filemtime(FILE_INDEX);

    if ($time < 60) {
        return 'a few seconds ago';
    } else if ($time < 300) {
        return 'a few minutes ago';
    } else if ($time < 7200) {
        return (int) ($time / 60) . ' minutes ago';
    } else {
        return (int) ($time / 60 / 60) . ' hours ago';
    }
}

?>