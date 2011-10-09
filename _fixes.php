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
header('Content-type: text/plain;');
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

if ( is_file(FILE_INDEX) ) {

	// Backup by timestamp. Important!
	copy( FILE_INDEX, FILE_INDEX.time() );
	$INDEX = index_load(FILE_INDEX);
	
	foreach ($INDEX as $key => &$row) {
		// Block/destroy any [WIP] mods
		if ( preg_match('@'.REGEX_OPENBRACKETS.'WIP'.REGEX_CLOSEBRACKETS.'@i',$row['title']) ) {
			print_web('===== WIP row deleted: '.$row['title'].N);
			unset($INDEX[$key]);
			continue;
		}
		
		process_title($row, $key);
	}
	
	index_save(FILE_INDEX,$INDEX);
	index_save(FILE_METADATA,$SUBINDEXES['metadata']);
	debug("Successfully applied fixes; $UPDATED rows affected.");
} else {
	die('There\'s nothing to fix; Index is missing. Generate the index first!');
}

function process_title(&$row, $id) {
	global $SUBINDEXES;
	
	// Automatically flag SMP mods
	if ( preg_match('@'.REGEX_OPENBRACKETS.'(smp(?: vanila)?)'.REGEX_CLOSEBRACKETS.'@i',$row['title'],$tag) ) {
		$SUBINDEXES['metadata'][$id]['flag_smp'] = true;
		$row['title'] = trim( str_ireplace($tag[0],'',$row['title']) );
		print_web('===== SMP tag added: '.$row['title'].N);
	}
	
	// Automatically flag Modloader mods
	if ( preg_match('@'.REGEX_OPENBRACKETS.'(ml|modloader)'.REGEX_CLOSEBRACKETS.'@i',$row['title'],$tag) ) {
		$SUBINDEXES['metadata'][$id]['flag_modloader'] = true;
		$row['title'] = trim( str_ireplace($tag[0],'',$row['title']) );
		print_web('===== Modloader tag added: '.$row['title'].N);
	}
}

?>