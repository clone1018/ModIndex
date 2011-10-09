<?php
/* 	============================================
	THE MINECRAFT MOD INDEX
	INDEX CLEANUP ENGINE

	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

require_once('lib/common.php');
header('Content-type: text/plain;');
auth_access($argv);

// =======
// GLOBALS
// =======

$INDEX;
$STAT_REMOVED = 0;

// ============
// MAIN PROGRAM
// ============

// Only do janitorial stuff if an index actually exists. We don't clean up anything else
if (is_file(FILE_INDEX)) {

	// Backup by timestamp. Important!
	if (PHP_SAPI == 'cli')
		copy( FILE_INDEX, FILE_INDEX.time() );
	
	$INDEX = unserialize(file_get_contents(FILE_INDEX));
	
	foreach ($INDEX as $key => $row) {		
	
		if ( (time() - $row['time_indexed']) > (60 * 60 * 24 * 14) ) {
			print_web('Expired entry removed: '.$row['title'].N);
			unset($INDEX[$key]);
			$STAT_REMOVED++;
			
			continue;
		}
		
	}
	
	write_index($INDEX);
	echo "Successfully cleaned the index. $STAT_REMOVED rows removed.".N;
} else {
	die('There"s nothing to clean; Index is missing. Generate the index first!\n');
}

// =============
// FILE HANDLING
// =============

function write_index($data) {
	$file = fopen(FILE_INDEX, 'w') or die('There was a problem writing the index. Please make sure the script\'s folder has the permissions \'0777\' set on it.');
	fwrite($file, serialize($data));
		
	fclose($file);
	unset($file);
}

?>