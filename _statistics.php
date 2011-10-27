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
$SUBINDEXES;

$STAT_REMOVED;
$TIMESTAMP = time();
$WEEKSTAMP = date('WY');
$ROW;

// ============
// MAIN PROGRAM
// ============

// Only do statistical stuff if an index actually exists.
if ( !is_file(FILE_INDEX) )
	die('There is no index!');

// Archive a copy of the index and metadata
copy( FILE_INDEX, DIRECTORY_ARCHIVES.'index.db'.$TIMESTAMP );
copy( FILE_METADATA, DIRECTORY_ARCHIVES.'metadata.db'.$TIMESTAMP );

// Flushes statistics and reprocesses the archives
if ( isset($_GET['full']) ) {
    
    $files = scandir(DIRECTORY_ARCHIVES);
    
    foreach ($files as $file) {
        if ( strpos($file,'index.db') === false )
            continue;
        
        $TIMESTAMP = get_timestamp($file);
        $WEEKSTAMP = date('WY',$TIMESTAMP);
        
        if ($prevweek != $WEEKSTAMP) {
            if ($SUBINDEXES['statistics']) {
                index_save(DIRECTORY_STATISTICS.$prevweek.FILENAME_STATISTICS, $SUBINDEXES['statistics']);
                print_web('Successfully saved statistics for weekyear '.$prevweek.N);
            }
            
            $SUBINDEXES['statistics'] = index_load(DIRECTORY_STATISTICS.$WEEKSTAMP.FILENAME_STATISTICS);
            $prevweek = $WEEKSTAMP;
            print_web('Loading statistics for weekyear '.$WEEKSTAMP.N);
        }
        
        $INDEX = index_load(DIRECTORY_ARCHIVES.$file);
        $SUBINDEXES['metadata'] = index_load(DIRECTORY_ARCHIVES.'metadata.db'.$TIMESTAMP);
        $ROW = &$SUBINDEXES['statistics'][$TIMESTAMP];
        
        foreach ($INDEX as $key => $row) {
            // Process statistics only if the row has a valid version
            if ($row['version'])
                gather_statistics($row, $key, $ROW, $SUBINDEXES['metadata'][$key]);
        }
        
    }
    
} else {
    $INDEX = index_load(FILE_INDEX);
    $SUBINDEXES = array(
        'statistics' => index_load(DIRECTORY_STATISTICS.$WEEKSTAMP.FILENAME_STATISTICS),
        'metadata' => index_load(FILE_METADATA)
    );
    
    $STAT_REMOVED = 0;
    $ROW = &$SUBINDEXES['statistics'][$TIMESTAMP];
    $ROW = array(
        'rows'             => count($INDEX),
        'totalviews'       => 0,
        'totalmodloader'   => 0,
        'totalmultiplayer' => 0,
        'versions'         => array(),
        'authors'          => array()
    );
    
    foreach ($INDEX as $key => $row) {
        // Process statistics only if the row has a valid version
        if ($row['version'])
            gather_statistics($row, $key, $ROW, $SUBINDEXES['metadata'][$key]);
        
        if ( (time() - $row['time_indexed']) > (60 * 60 * 24 * 30) ) {
            print_web('Expired entry removed: '.$row['title'].N);
            unset( $INDEX[$key] );
            $STAT_REMOVED++;
            
            continue;
        }
    }
    
    index_save(FILE_INDEX, $INDEX);
    print_web("Successfully cleaned the index. $STAT_REMOVED rows removed.".N);
}

index_save(DIRECTORY_STATISTICS.$WEEKSTAMP.FILENAME_STATISTICS, $SUBINDEXES['statistics']);
print_web('Successfully saved statistics.');

// ===============
// INDEX PROCESSOR
// ===============
function gather_statistics(&$row, $key, &$stat_row, $metadata) {
    $stat_row['totalviews'] += $row['views'];
    
    if ($metadata['flag_modloader'])
        $stat_row['totalmodloader']++;
        
    if ($metadata['flag_smp'])
        $stat_row['totalmultiplayer']++;
    
    $ver = $row['version'];
    $stat_row['versions'][$ver]++;
    
    $auth = $row['author'];
    $stat_row['authors'][$auth]++;
    
    
}
?>