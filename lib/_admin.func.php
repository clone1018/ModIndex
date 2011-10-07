<?php
/* 	============================================
	THE MINECRAFT MOD INDEX
	ADMIN PANEL FUNCTIONALITY
	Version: v1.3
	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/
require_once('lib/common.php');
require_once('lib/system.email.php');
require_once('lib/system.twitter.php');
auth_access($argv);

if ( !file_exists(FILE_INDEX) ) {
	include_once('lib/err_index.html');
	die();
}
		
// =======
// GLOBALS
// =======

// ===== Constants
define(MODE_ROWEDITOR, 1);

define(URL_ADMIN, '_admin.php?admin='.$_GET['admin']);

// ===== Databases
$INDEX = index_load(FILE_INDEX);
$SUBINDEXES;
$MOTW = file(FILE_MOTW, FILE_IGNORE_NEW_LINES);

// ===== Global variables
$INDEX_SAVED;
$TWEET_SUCCESS;
$PICKEDROW;
$MODE;

$SUBINDEXES = array(
	'metadata' => index_load(FILE_METADATA),
	'blacklist' => index_load(FILE_BLACKLIST)
);

// ========
// HANDLERS
// ========

require_once('_admin.handlers.php');

// ===============
// INDEX FUNCTIONS
// ===============

function index_row_delete($id) {
	global $INDEX;
	
	if ( array_key_exists($id, $INDEX) ) {
		unset($INDEX[$id]);
		index_save(FILE_INDEX, $INDEX);
	}
}
	
// ============
// UI FUNCTIONS
// ============

function checked($id) {
	global $SUBINDEXES, $PICKEDROW;
	
	if (!$PICKEDROW)
		return false;
		
	echo $SUBINDEXES['metadata'][$PICKEDROW][$id] ? 'checked' : '';
}

function listing_index() {
	global $INDEX, $PICKEDROW;
	
	// No listing if row isn't picked
	if ( !$PICKEDROW )
		return false;
	
	// Check if row doesn't exist in index, otherwise retrieve row data into $row
	if ( !($row = $INDEX[$PICKEDROW]) ) {
		echo '<h1 class="center" style="color:red">Row ID '.$PICKEDROW.' does not exist!</h1>';
		$PICKEDROW = null;
		return false;
	}	
	
	echo "<div class='mod mU'>
		<div class='tools toolbar'><a class='button' href='".URL_ADMIN."&blacklist_add=$PICKEDROW' title='Blacklist this mod!'>&#10008;</a></div>
		<div class='button_extend'></div>
		
		<div class='meta'>
			 $row[author], $row[views] views
		</div>
		
		<a class='link_mod' target='_blank' href='".URL_TOPIC."$PICKEDROW'><h2>[".$row['version'].'] '.html($row['title']).'</h2>
		'.html($row['desc']).'</a>
	</div>';
}

function listing_blacklist() {
	global $INDEX, $SUBINDEXES;
	
	if ( empty($SUBINDEXES['blacklist']) ) {
		echo '<h1 class="center" style="color:red">The blacklist is empty!</h1>';
		return false;
	}
	
	foreach ($SUBINDEXES['blacklist'] as $blacklist) {
		$exists = array_key_exists($blacklist, $INDEX) ? '(still in index: '.html($INDEX[$blacklist]['title']).')' : '';
		
		echo "<div class='mod mF'>
			<div class='tools toolbar'><a href='".URL_ADMIN."&blacklist_pop=$blacklist' title='Delete this entry!'>&#10008;</a></div>
			<div class='button_extend' style='color: #500'></div>
			
			<h2>$blacklist $exists</h2></a>
		</div>";
	}
}

function listing_motw() {
	global $INDEX, $MOTW;
	
	if ( empty($MOTW) ) {
		echo '<h1 class="center" style="color:red">No MOTW\'s are queued!</h1>';
		return false;
	}
	
	foreach ($MOTW as $motw) {
		$row = $INDEX[$motw];
		
		echo "<div class='mod mU'>
			<div class='tools toolbar'><a href='".URL_ADMIN."&motw_pop=$motw' title='Delete this MOTW!'>&#10008;</a></div>
			<div class='button_extend' style='color: #500'></div>
			
			<div class='meta'>
				 $row[author], $row[views] views
			</div>
			
			<a class='link_mod' target='_blank' href='".URL_TOPIC."$motw'><h2>".$row['version'].html($row['title']).'</h2></a>
		</div>';
	}
}

function listing_email() {
	global $INDEX, $EMAIL_DB;
	
	if ( empty($EMAIL_DB[2]) ) {
		echo '<h1 class="center" style="color:red">No email\'s registered!</h1>';
		return false;
	}
	
	foreach ($EMAIL_DB[2] as $email => $verify) {
		$count = notification_count($email);
		
		echo "<div class='mod mU'>
			<div class='tools toolbar'><a href='".URL_ADMIN."&email_pop=$email' title='Deverify this email!'>&#10008;</a></div>
			<div class='button_extend' style='color: #500'></div>
			
			<div class='meta'>
				$count notifications registered
			</div>
			
			<span class='link_mod'><h2>$email</h2></span>
		</div>";
	}
}

?>