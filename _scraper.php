<?php

/* 	============================================
	THE MINECRAFT MOD INDEX
	SCREEN SCRAPER AND DATA RETRIVAL ENGINE

	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

require_once('lib/common.php');
header('Content-type: text/plain;');
auth_access($argv);

// The Simple HTML DOM parser from http://simplehtmldom.sourceforge.net/, you guys rock!
require_once('lib/system.htmldom.php');

// =======
// GLOBALS
// =======

$BUFFER_ARRAY = array();
$BUFFER;
$RECORDING;
$PAGE;
$DEBUG = false;

$STAT_ROWS = 0;

// ============
// MAIN PROGRAM
// ============

// If we don't have a job file, initialize it and begin from page 1, else find out what page we're doing
$PAGE = is_file(FILE_JOB) && !isset($_GET['rewind']) ? (int)file_get_contents(FILE_JOB) : job_set(0);

// Retrieve the page
for ($i = 0; $i < 3; $i++)
	page_get( URL_FORUM.($PAGE*30) );

$BUFFER = implode(' ',$BUFFER_ARRAY);
unset($BUFFER_ARRAY);

scrape();

exit('Index has been generated for '.$STAT_ROWS.' rows.'.N);

// =============
// FILE HANDLING
// =============

// Flushes data to the temporary index. Called on a page-by-page basis.
function flush_to_db($data) {
	$file = fopen(FILE_TEMPINDEX, 'a') or die('There was a problem writing to the database. Please make sure the script\'s folder has the permissions \'0777\' set on it.');
	$serialized = serialize($data).N;
	print_web($data);
	
	fwrite($file, $serialized);
	fclose($file);
	
	unset($file, $serialized);
}

function job_set($job) {
    file_write(FILE_JOB, $job, 'There was a problem creating the job. Please make sure the script\'s folder has the permissions \'0777\' set on it.');
    return $job;
}

// ====================
// PAGE RETRIVAL ENGINE
// ====================

/* gets the data from a URL, thanks http://davidwalsh.name/download-urls-content-php-curl */
function page_get($url) {
	global $RECORDING, $PAGE, $BUFFER_ARRAY;
	print_web('Retriving page: '.$PAGE.N);
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'page_get_chunk');
	curl_setopt($ch, CURLOPT_BUFFERSIZE, 250);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
	
	for ($try = 1; $try <= 10; $try++) {
		// Stop recording and clean buffer
		$RECORDING = FALSE;
		unset($BUFFER_ARRAY[$PAGE]);
		
		print_web('Attempt ' . $try .'... ');
		curl_exec($ch);
		
		// CURL error handling from http://www.php.net/manual/en/book.curl.php#99979
		if( curl_errno($ch) ) {
			print_web('Curl error: ' . curl_error($ch) . N);
			
		} else if ( empty($BUFFER_ARRAY[$PAGE]) ) {
			print_web('Unexpected data! Did not get any topics.' . N);
			
		} else if ( strpos($BUFFER_ARRAY[$PAGE], 'No topics were found. ') !== false ) {
			print_web('Ran out of topics, rewinding to page zero.' . N);
			unset($BUFFER_ARRAY[$PAGE]);
			$PAGE = job_set(0);
			return false;
			
		} else {
			print_web('Success!' . N);
			curl_close($ch);
			unset($ch);
			
			// Trim all the fat
			$BUFFER_ARRAY[$PAGE] = substr($BUFFER_ARRAY[$PAGE], strpos($BUFFER_ARRAY[$PAGE],'<tr class="row1"'));
			$BUFFER_ARRAY[$PAGE] = substr($BUFFER_ARRAY[$PAGE], 0, strpos($BUFFER_ARRAY[$PAGE],'</table>'));
			
			$PAGE = job_set($PAGE + 1);
			return true;
		}
	}
	
	print_web('Fatal error on page '.$PAGE.', skipping.' . N);
	$PAGE = job_set($PAGE + 1);
	return false;
}

	// Partial download handler, only gets the main listing of topics, thanks http://stackoverflow.com/questions/2032924/how-to-partially-download-a-remote-file-with-curl
	function page_get_chunk($ch, $chunk) { 
		global $BUFFER_ARRAY, $RECORDING, $PAGE;
			
		// Begin recording the HTML data when we get to the topics
		if ( strpos($chunk, '<!-- BEGIN TOPICS -->') !== false )
			$RECORDING = true;
		
		// Streams data into recorded buffer
		$BUFFER_ARRAY[$PAGE] .= $RECORDING ? $chunk : NULL;
		
		// End downloading when we get near the end of the topics
		if ( strpos($chunk, '<div id="forum_filter" class="filter_bar rounded">') !== false ) {
			$RECORDING = false;
			return -1;
		}
		
		// Needed for CURL writefunction
		return strlen($chunk);
	};


// ===================
// DOM SCRAPING ENGINE
// ===================

// This scrapes the downloaded HTML code for data via DOM traversal and translates it into array form
function scrape() {
	global $BUFFER, $DEBUG, $STAT_ROWS;
	
	// Finalize the recorded data
	$BUFFER = '<table>'.$BUFFER.'</table>';
	$html = str_get_html($BUFFER);
	
	$rows = $html->find('tr[id^=trow]');
	
	// No rows? Then we have a serious problem here.
	if ( empty($rows) )
		die( 'No rows could be scraped from the buffer! Buffer dump: '.$BUFFER);
	
	// Clear the buffer to conserve space for this next intensive part...
	unset($BUFFER);
	print_web('Scraping rows:'.N);
	
	if ($DEBUG)
		array_splice($rows,1);
	
	foreach ($rows as $row) {
		// Pinned topic, do not want...
		if ( $row->find('.topic_prefix',0)->plaintext == 'Pinned' )
			continue;
		
		$topicID = $DEBUG ? 696969 : str_replace('trow_','',$row->id);
		$topicTitleBar = $row->find('.__topic',0);
		$topicTitle = $topicTitleBar->find('.topic_title',0)->plaintext;
		//$topicTitle = '[1.8prea] lol';
		$topicDesc = $topicTitleBar->find('.desc',0)->plaintext;
		$topicVersion;
		
		$topicAuthorBar = $topicTitleBar->next_sibling();
		$topicAuthor = $topicAuthorBar->first_child();
		
		$topicAuthID = str_replace(URL_USERFULL,'',$topicAuthor->href);
		$topicAuthID = substr($topicAuthID,0,-1);
		
		$topicViews = $topicAuthorBar->next_sibling()->find('.views',0)->plaintext;
		$topicViews = str_replace(' Views','',$topicViews);
		$topicViews = str_replace(',','',$topicViews);
		
		$topicCreated = $topicTitleBar->find('.topic_title',0)->getAttribute('title');
		$topicCreated = str_replace('View topic, started  ','',$topicCreated);
		$topicCreated = str_replace(' - ',' ',$topicCreated);
		$topicCreated = strtotime($topicCreated);
		
		// *** ********** ***
		// *** VERSIONING ***
		preg_match(REGEX_VERSION,$topicTitle,$topicVersion);
		
		// If we couldn't get a version using the strict regex, we'll have to try the flimsier catch-all
		if ( empty($topicVersion[0]) ) {
			preg_match(REGEX_VERSIONBASIC,$topicTitle,$topicVersion);
			
			// If we couldn't get a version at all, it might be a wildcard one (ALL/ANY)
			if ( empty($topicVersion[0]) ) {
				preg_match(REGEX_VERSIONWILDCARD,$topicTitle,$topicVersion);
				
				// If it's a wildcard, then we set it as "ALL" for convention sake
				if (!empty($topicVersion[0]))
					$topicVersion[1] = 'ALL';
				
			}
		
		}
		
		// Reformat the version string to the latest standard
		if ( preg_match('@^(\d\.\d)_0?(\d)$@i',$topicVersion[1],$ver) ) {
			$topicVersion[1] = $ver[1].($ver[2] > 0 ? '.'.$ver[2] : '');
			
			unset($ver);
		}
		
		// Reformat the version string for -pre parts
		$topicVersion[1] = preg_replace('@(_|\s|-)?pre?\s?([0-9])?$@i', '-pre$2', $topicVersion[1]);
		
		$topicCleanTitle = trim( str_replace($topicVersion[0],'',$topicTitle) );
		// *** END VERSIONING ***
		// *** ************** ***
		
		
		// Finally, flush all the captured data to database.
		flush_to_db(array(		'id' => $topicID,
								'title' => un_html($topicCleanTitle),
								'version' => $topicVersion[1],
								'desc' => un_html($topicDesc),
								'author' => $topicAuthor->plaintext,
								'author_id' => $topicAuthID,
								'views' => $topicViews,
								'time_created' => $topicCreated,
								'time_indexed' => time()
								));

		$STAT_ROWS++;
		
		// Clear all the data pre-next loop as these objects take a massive amount of memory and may contain circular references
		$topicTitleBar->clear();
		$topicAuthorBar->clear();
		$topicAuthor->clear();
		unset($topicID,$topicTitleBar,$topicTitle,$topicDesc,$topicVersion,$topicAuthorBar,$topicAuthor,$topicCleanTitle,$topicViews,$topicAuthID,$topicCreated);
	}
	
	$html->clear();
	unset($html,$rows);
}

?>