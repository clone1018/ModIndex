<?php
/* 	============================================
	THE MINECRAFT MOD INDEX
	ADMIN PANEL HANDLERS

	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

// =====================
// INDEX EDITOR HANDLING
// =====================

// ===== Core
if ( isset($_GET['core_popfixes']) ) {
	if (TESTING)
		die('Cannot delete _fixes.php in the testing environment!');
	
	if ( is_file('_fixes.php') ) {
		unlink('_fixes.php');
		die('_fixes.php has been deleted.');
	}
	
	die('_fixes.php does not exist!');
}

// ==================
// BLACKLIST HANDLING
// ==================

if ( is_numeric($_REQUEST['blacklist_add']) || preg_match(REGEX_IP, $_REQUEST['blacklist_add']) ) {
	$blacklist = $_REQUEST['blacklist_add'];
	
	if ( !$_REQUEST['blacklist_ignore'] )
		index_row_delete($blacklist);
	
	if ( !in_array($blacklist ,$SUBINDEXES['blacklist']) )
		array_push($SUBINDEXES['blacklist'], $blacklist);
		
	index_save(FILE_BLACKLIST,$SUBINDEXES['blacklist']);
}

if ( $_GET['blacklist_pop'] ) {
	$key = array_search($_GET['blacklist_pop'], $SUBINDEXES['blacklist']);
	
	if ($key !== false) {
		unset($SUBINDEXES['blacklist'][$key]);
		index_save(FILE_BLACKLIST,$SUBINDEXES['blacklist']);
	}
	
	unset($key);
}

// ===== Rowpicker and Roweditor
// Are we deleting a row?
if ( is_numeric($_GET['row_delete']) ) {
	index_row_delete($_GET['row_delete']);
}

// Did we just pick/search for a row?
if ( is_numeric($_POST['rowpicker_query']) ) {
	$PICKEDROW = $_POST['rowpicker_query'];
	
} else if ( is_numeric($_GET['row_edit']) ) {
	$PICKEDROW = $_GET['row_edit'];
	$MODE = MODE_ROWEDITOR;
	
} else if ( !empty($_POST['rowpicker_query']) ) {
	foreach ($INDEX as $key => $row) {
		if ( stripos($row['title'], $_POST['rowpicker_query']) !== false ) {
			$PICKEDROW = $key;
			break;
		}
	}	
}

// Or did we get asked for a random row?
if ( $_POST['rowpicker_random'] )
	$PICKEDROW = index_random($INDEX);

// Or are we recieving data to edit a picked row?
if ( array_key_exists($_GET['row_save'], $INDEX) && $_POST['roweditor_transmit'] ) {
	$PICKEDROW = $_GET['row_save'];
	
	$INDEX[$PICKEDROW]['title'] = $_POST['row_title'];
	$INDEX[$PICKEDROW]['desc'] = $_POST['row_desc'];
	$INDEX[$PICKEDROW]['version'] = $_POST['row_version'];
	$INDEX[$PICKEDROW]['author'] = $_POST['row_author'];
	$INDEX[$PICKEDROW]['author_id'] = $_POST['row_author_id'];
	$SUBINDEXES['metadata'][$PICKEDROW]['keywords'] = str_replace( array("\n","\r"), '', $_POST['metadata_keywords'] );
	
	// Cycle through all flags and set them
	foreach ($ENUM_INDEXFLAGS as $flag => $desc)
		$SUBINDEXES['metadata'][$PICKEDROW][$flag] 	= $_POST[$flag];
	
	index_save(FILE_INDEX,$INDEX);
	index_save(FILE_METADATA,$SUBINDEXES['metadata']);
	$INDEX_SAVED = $PICKEDROW;
	
	if ($_POST['roweditor_random'])
		$PICKEDROW = index_random($INDEX);
	
	if ($_POST['roweditor_editoronly'])
		$MODE = MODE_ROWEDITOR;
}

// Or are we reciving suggested changes via email?
if ( array_key_exists($_GET['row_changes'], $INDEX) ) {
	$PICKEDROW = $_GET['row_changes'];
	
	// Cycle through all flags and set them
	foreach ($ENUM_INDEXFLAGS as $flag => $desc)
		$SUBINDEXES['metadata'][$PICKEDROW][$flag] 	= $_GET[$flag];
		
	$SUBINDEXES['metadata'][$PICKEDROW]['keywords'] = $_GET['keywords'];
	
	index_save(FILE_METADATA,$SUBINDEXES['metadata']);
	$INDEX_SAVED = $PICKEDROW;
}

// ===== Twitter handling
if ( $_POST['twitter_post'] ) {
	$tweetpost = $_POST['twitter_post'];
	
	if ( is_numeric($tweetpost) && array_key_exists($tweetpost,$INDEX) )
		$TWEET_SUCCESS = tweet_announce($INDEX[$tweetpost], $tweetpost);
	else
		$TWEET_SUCCESS = tweet($tweetpost);
	
	unset($tweetpost);
}

// =============
// MOTW HANDLING
// =============

if ( $_POST['motw_reset'] ) {
	file_write(FILE_MOTW, null, 'Could not write MOTW database!');
	$MOTW = array();
}

if ( is_numeric($_GET['motw_pop']) ) {
	$newfile = '';
	
	foreach ($MOTW as $key => $row) {
		if ($row == $_GET['motw_pop'])
			unset($MOTW[$key]);
		else
			$newfile .= $row.N;
	}
		
	file_write(FILE_MOTW, $newfile, 'Could not write MOTW database!');
	unset($newfile);
}

if ( is_numeric($_POST['motw_id']) ) {
	file_write(FILE_MOTW,$_POST['motw_id'].N,'Could not write MOTW database!', true);
	$MOTW = file(FILE_MOTW, FILE_IGNORE_NEW_LINES);
}

// ==============
// EMAIL HANDLING
// ==============

if ( $_POST['email_oldid'] ) {
	notification_send($_POST['email_oldid'], $_POST['email_type'], EMAIL_ADMIN, $INDEX[$_POST['email_oldid']], $INDEX[$_POST['email_newid']], true);
}

if ( $_GET['email_pop'] ) {
	unset( $EMAIL_DB[2][$_GET['email_pop']] );
	emaildb_save();
}

?>