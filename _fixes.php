<?php
/* 	============================================
	MODLISTER FOR MINECRAFTFORUM.NET
	DATA PATCHER/SANDBOX/MIGRATIONS
	Version: v0.9
	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

require_once('lib/common.php');
auth_access($argv);

$INDEX;
$SUBINDEXES;
$UPDATED = 0;

// ===== Subindexes
$SUBINDEXES = array(
	'metadata' => index_load(FILE_METADATA),
	'blacklist' => index_load(FILE_BLACKLIST)
);

// ============
// MAIN PROGRAM
// ============

echo '<pre>';
if (is_file(FILE_INDEX)) {

	// Backup by timestamp. Important!
	copy( FILE_INDEX, FILE_INDEX.time() );
	$INDEX = index_load(FILE_INDEX);
	
	foreach ($INDEX as $key => $row) {
		$INDEX[$key]['author_id'] = str_replace(URL_USERFULL,'',$row['author_id']);
		$UPDATED++;
	}
	
	index_save(FILE_INDEX,$INDEX);
	//index_save(FILE_METADATA,$SUBINDEXES['metadata']);
	debug("Successfully applied fixes; $UPDATED rows affected.");
} else {
	die('There\'s nothing to fix; Index is missing. Generate the index first!');
}
echo '</pre>';

?>