<?php

/*	============================================
	THE MINECRAFT MOD INDEX
	COMMON LIBRARY AND SETTINGS


	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
 */

// The SimPlaza Common PHP Library
require_once __DIR__.'/../../common/c.php';

// Settings
require_once 'common.config.php';
php_includeonly(__FILE__);

// =========
// CONSTANTS
// =========

define('VERSION',   2.0);
define('TESTING',   ($_SERVER['HTTP_HOST'] == HOSTNAME_TESTING));

define('VERSION_MINECRAFT',          '@^(' . VERSION_MINECRAFT_CURRENT . ')$@i');

// ===== Regexes
define('REGEX_OPENBRACKETS', '(\[|\(|\{)');
define('REGEX_CLOSEBRACKETS', '(\]|\)|\})');
define('REGEX_TAGS', '@((\[)|(\()|(\{))(.*?)(?(2)\]|(?(3)\)|(?(4)\})))@i');

define('REGEX_VERSION_BASICNUMERICAL', '(?<major>1)\.(?<minor>\d)((\.|\_)(?<build>\d{1,2}))?((_|-|\s)?(?<status>pre(-?release)?|rc)(\s?(?<statusbuild>[0-9]))?)?');
define('REGEX_VERSION', '@' . REGEX_VERSION_BASICNUMERICAL . '@i');

define('REGEX_SPAM_TERMS', '\s?(?:req(uest)?|del(ete)?|help|coming\ssoon|closed|discontinued|w(ork\s?)?i(n\s?)?p(rogress)?|tut(orial)?)?\s?');
define('REGEX_SPAM_BEGGING', 'help|question|req(u|w)est|how\sdo\si|\?|idea|problem|how to|seeking|please|need|abandoned|looking|issues|closed');
define('REGEX_SPAM_MALICIOUS', 'minecr?aft code|codes|hentai|giveaway|prem\\s?account|give \d{1,2} minecraft|free|poker|dress|cialis|viagra|sony|adf\.?ly');
define('REGEX_SPAM_MALICIOUS2', 'karen|hold\'em|order|sale|discount|research|phone|contract|cheap');
define('REGEX_SPAM', '@(' . REGEX_OPENBRACKETS . REGEX_SPAM_TERMS . REGEX_CLOSEBRACKETS . '|' . REGEX_SPAM_BEGGING . '|' . REGEX_SPAM_MALICIOUS . '|' . REGEX_SPAM_MALICIOUS2 . ')@i');

// ===== URLs
define('URL_INDEX',    'http://'.(TESTING ? URL_INDEX_TESTING : URL_INDEX_LIVEFIRE) );
define('URL_FORUM',    'http://minecraftforum.net/forum/%d-/page__prune_day__%d__sort_by__A-Z__sort_key__title__topicfilter__open__st__%d');
define('URL_TOPIC',    'http://minecraftforum.net/index.php?showtopic=');
define('URL_USERFULL', 'http://www.minecraftforum.net/user/');
define('URL_USER',     'http://minecraftforum.net/user/');

// ===== Files
define('DIRECTORY_DATABASE',   'data/');
define('DIRECTORY_INDEXES',    'indexes/');
define('DIRECTORY_ARCHIVES',   'archives/');
define('DIRECTORY_LANGUAGES',  'lang/');

define('INDEX_MODS',      'mods');
define('INDEX_WIP',       'wip');
define('INDEX_TEXTURES',  'tex');
define('INDEX_TOOLS',     'tools');

define('INDEX_SUFFIX',    '.idx');
define('TEMP_SUFFIX',     '.tmp');

define('FILENAME_INDEX_MODS',      INDEX_MODS.INDEX_SUFFIX);
define('FILENAME_INDEX_WIP',       INDEX_WIP.INDEX_SUFFIX);
define('FILENAME_INDEX_TEXTURES',  INDEX_TEXTURES.INDEX_SUFFIX);
define('FILENAME_INDEX_TOOLS',     INDEX_TOOLS.INDEX_SUFFIX);
define('FILENAME_METADATA',        'metadata.db');

define('FILE_INDEX_MODS',       DIRECTORY_INDEXES . FILENAME_INDEX_MODS);
define('FILE_INDEX_WIP',        DIRECTORY_INDEXES . FILENAME_INDEX_WIP);
define('FILE_INDEX_TEXTURES',   DIRECTORY_INDEXES . FILENAME_INDEX_TEXTURES);
define('FILE_INDEX_TOOLS',      DIRECTORY_INDEXES . FILENAME_INDEX_TOOLS);
define('FILE_METADATA',         DIRECTORY_INDEXES . FILENAME_METADATA);

define('FILE_BLACKLIST',  DIRECTORY_DATABASE . 'blacklist.db');
define('FILE_JOB',        DIRECTORY_DATABASE . 'job.db');
define('FILE_EMAIL',      DIRECTORY_DATABASE . 'email.db');
define('FILE_MOTW',       DIRECTORY_DATABASE . 'motw.db');

define('NOTIFICATION_VERSION', 0);
define('NOTIFICATION_TITLE',   1);

// ===== Enums
$E_FORUMS = array(
	'mods' => 51,
	'wip' => 141,
	'tools' => 42,
	'textures' => 41
);

$E_INDEXFIELDS = array(
    'title',
    'version',
    'desc',
    'author',
    'author_id',
    'views',
	'time_created',
    'time_indexed'
);

$E_METADATAFIELDS = array(
    'keywords',
    'flag_collection',
    'flag_adfly',
    'flag_depends',
    'flag_smp'
);

$E_INDEXFLAGS = array(
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
function arraydb_load($file) {
    if ( !is_file($file) )
        return array();
    else
        return unserialize( file_get_contents($file) );
}

// Plain and simple Index writer
function arraydb_save($file, $data) {
    file_write($file, serialize($data), 'Unable to write index: ' . $file);
}

function get_timestamp($name) {
    preg_match('@[a-z]+\.db([0-9]+)@i', $name, $matches);
    return $matches[1];
}

// ==================
// SECURITY FUNCTIONS
// ==================

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

// ===============
// ERROR REPORTING
// ===============
function testunit() {
    if ( isset($_GET['test']) )
        return true;
}
error_reporting(TESTING ? E_ALL : 0);

?>