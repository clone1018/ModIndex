<?php
/* 	============================================
	THE MINECRAFT MOD INDEX
	STATISTICAN ENGINE
	
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
$TIMESTAMP = time();
$STAT_REMOVED = 0;

// ============
// MAIN PROGRAM
// ============

// Archive a copy of the index and metadata
copy( FILE_INDEX, DIRECTORY_ARCHIVES.'index.db'.$TIMESTAMP );
copy( FILE_METADATA, DIRECTORY_ARCHIVES.'metadata.db'.$TIMESTAMP );
$INDEX = index_require(FILE_INDEX);

foreach ($INDEX as $key => $row) {
    // Process statistics only if the row has a valid version
    //if ($row['version'])
        //gather_statistics($row, $key, $ROW, $SUBINDEXES['metadata'][$key]);
    
    if ( (time() - $row['time_indexed']) > (60 * 60 * 24 * 30) ) {
        print_web('Expired entry removed: '.$row['title'].N);
        unset( $INDEX[$key] );
        $STAT_REMOVED++;
        
        continue;
    }
}

index_save(FILE_INDEX, $INDEX);
print_web("Successfully cleaned the index. $STAT_REMOVED rows removed.");

?>