<?php
/* 	============================================
	THE MINECRAFT MOD INDEX
	INDEXING ENGINE
	Version: v1.3
	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

require_once('lib/common.php');
require_once('lib/system.email.php');
header('Content-type: text/plain;');
auth_access($argv);

$NEWINDEX;
$INDEX;
$SUBINDEXES;
$TWITTER;

$SUBINDEXES = array(
	'blacklist' => index_load(FILE_BLACKLIST)
);

// ============
// MAIN PROGRAM
// ============

// Stage 1. Get the fresh, new temp index and convert it into a proper array
if (is_file(FILE_TEMPINDEX)) {
	// Get the data from the generated temporary index
	$file = fopen(FILE_TEMPINDEX, 'r') or die('There was a problem reading the temp index. Please make sure the script\'s folder has the permissions \'0777\' set on it.');

	// Run through the data and generate the proper array (as we are heavily conserving memory on the scraper engine)
	while( $line = fgets($file) ) {		
		$row = unserialize($line);
		$NEWINDEX[$row['id']] = array_slice($row,1);
	}
	
	fclose($file);
	
} else {
	die('There\'s nothing to index; temp.db is missing. Run the scraper first!');
}

// Stage 2. Create or otherwise merge to the live-fire index
if (is_file(FILE_INDEX)) {

	// Statistics
	$STAT_UPDATES = 0;
	$STAT_ADDITIONS = 0;
	$STAT_EMAILS = 0;
	$STAT_SPAM = 0;
	$STAT_BLACKLIST = 0;
	$INDEX = unserialize( file_get_contents(FILE_INDEX) );
	
	// Walk through each row of the new index and merge it in with the old index
	foreach ($NEWINDEX as $key => $row) {
		// Block/ignore blacklisted entries
		if ( in_array($key, $SUBINDEXES['blacklist']) ) {
			print_web('===== Blacklisted row discarded: '.$row['title'].N);
			$STAT_BLACKLIST++;
			continue;
		}
			
		if ($INDEX[$key]) {			
			print_web('===== Row updated: '.$row['title'].N);
			
			// Process updates, for things like email notifcations
			process_updates($INDEX[$key],$row,$key,$STAT_EMAILS);
			$STAT_UPDATES++;
		} else {
			
			// Block spam before adding it to the index
			if ( ( empty($row['version']) && preg_match(REGEX_SPAM,$row['title']) ) || empty($row['title']) ) {
				print_web('===== Spam row discarded: '.$row['title'].N);
				$STAT_SPAM++;
				continue;
			}
			
			print_web('===== New row added: '.$row['title'].N);
			
			$STAT_ADDITIONS++;
		}
		
		$INDEX[$key] = $row;
		unset($NEWINDEX[$key],$key,$row);
	}
	
	// We're done here.
	unset($NEWINDEX);
	write_index($INDEX);
	
	echo "Successfully updated the index: $STAT_UPDATES commited, $STAT_EMAILS emails sent, $STAT_ADDITIONS additions made,
	$STAT_SPAM spam entries blocked, $STAT_BLACKLIST blacklisted entries blocked.";
} else {
	write_index($NEWINDEX);
	echo 'Successfully created the index.';
}

// Finally, dispose of the temporary index.
unlink(FILE_TEMPINDEX);

// ===============
// UPDATE HANDLING
// ===============
function process_updates($oldrow, $newrow, $id, &$STAT_EMAILS) {
	global $EMAIL_DB, $TWITTER;
	$changed_version 	= ($oldrow['version'] != $newrow['version']);
	$changed_title 		= ($oldrow['title'] != $newrow['title']);
	
	// If we got a difference in either version or title, initialize email code
	if ( $changed_version || $changed_title ) {
		
		// Handle a version difference
		if ($changed_version) {
			// Are notifications registered for this row?
			if ( is_array($EMAIL_DB[NOTIFICATION_VERSION][$id]) ) {
				foreach ($EMAIL_DB[NOTIFICATION_VERSION][$id] as $email) {
					$STAT_EMAILS += notification_send($id,NOTIFICATION_VERSION,$email,$oldrow,$newrow);
					print_web('Email sent for version update: '.$id.N);
				}
			}
			
			// *** ******** ***
			// *** TWEETING ***
			if ( preg_match('@^'. VERSION_MINECRAFT_CURRENT .'$@i',$newrow['version']) && $newrow['views'] > 9000 ) {
				//TEMP: Tweets for 1.8 mods!
				if (!$TWITTER)
					require_once('lib/system.twitter.php');
				
				$tweet = tweet_announce($newrow, $id);
				
				if ($tweet === true)
					print_web('Tweet sent: '.$newrow['title'].N);
				else
					print_web('Tweet failure for '.$id.': '.$tweet.N);
				
			}
			// *** TWEETING ***
			// *** ******** ***
		}
		
		// Handle a title difference
		if ($changed_title) {
			// Are notifications registered for this row?
			if ( is_array($EMAIL_DB[NOTIFICATION_TITLE][$id]) ) {
				foreach ($EMAIL_DB[NOTIFICATION_TITLE][$id] as $email) {
					$STAT_EMAILS += notification_send($id,NOTIFICATION_TITLE,$email,$oldrow,$newrow);
					print_web('Email sent for title update: '.$id.N);
				}
			}
		}
	}

}

// =============
// FILE HANDLING
// =============

function write_index($data) {
	$file = fopen(FILE_INDEX, 'w') or die('There was a problem writing the index. Please make sure the script\'s folder has the permissions \'0777\' set on it.');
	fwrite($file, serialize($data));
		
	fclose($file);
}

?>