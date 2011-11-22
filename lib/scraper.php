<?php
/* ========================
 * THE MINECRAFT MOD INDEX
 * SCRAPING LIBRARY
 * ========================
 * @package ModIndex
 * @subpackage scraper_
 * @author Major Rasputin <major.rasputin@simplaza.net>
 */

require_once 'common.php';
require_once 'tagger.php';
require_once 'htmldom.php'; // The Simple HTML DOM parser from http://simplehtmldom.sourceforge.net/, you guys rock!
php_includeonly(__FILE__);

function scraper_loadpage($forum, $days, &$page) {
	print_web('Retriving page '.$page.' for forum '.$forum.N);
    $buffer;

	for ($try = 1; $try <= 10; $try++) {
		print_web(T.'Attempt ' . $try .'... ');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf(URL_FORUM, $forum, $days, $page*30) );
        curl_setopt($curl, CURLOPT_BUFFERSIZE, 250);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		$buffer = curl_exec($curl);

		// CURL error handling from http://www.php.net/manual/en/book.curl.php#99979
		if( curl_errno($curl) ) {
			print_web('Curl failure: ' . curl_error($curl) . N);

		} else if ( empty($buffer) ) {
			print_web('Curl failure: Empty buffer.' . N);

		} else if ( strpos($buffer, 'No topics were found. ') !== false ) {
			print_web('Ran out of topics, rewinding to page zero.' . N);
            $try--;
			$page = 0;

		} else {
			curl_close($curl);
			unset($curl);

			// Trim all the fat
            print_web('Success! Downloaded '.  strlen($buffer) . ' bytes of data.' . N);

			$page++;
			return $buffer;
		}
	}

	echo 'Fatal error on page '.$page.' for forum '.$forum.', skipping.' . N;
    return false;
}

function scraper($type, &$buffer) {
    global $E_FORUMS;

    print_web('Scraping rows of buffer type '.$type.'... ');

	// Finalize the recorded data
	$html = str_get_html($buffer);
	$rows = $html->find('tr[id^=trow]');
    $buffer = array();
    $stat_rows = 0;

	// No rows? Then we have a serious problem here.
	if ( empty($rows) ) {
		print_web('Failed! This buffer had no rows.'.N);
        return -1;
    }

	foreach ($rows as $row) {
		// Pinned topic, do not want...
        $prefix = $row->find('.topic_prefix',0);
		if ( $prefix && $prefix->plaintext == 'Pinned' ) {
			$prefix->clear();
            continue;
        }

		$topicID        = str_replace('trow_','',$row->id);
        $buffer_row     = array();

		$topicTitleBar          = $row->find('.__topic',0);
		$buffer_row['title']    = un_html($topicTitleBar->find('.topic_title',0)->plaintext);

        if ( $desc = $topicTitleBar->find('.desc',0) ) {
            $buffer_row['desc']     = un_html($desc->plaintext);
            $desc->clear();
        }

		$topicAuthorBar          = $topicTitleBar->next_sibling();
		$buffer_row['author']    = $topicAuthorBar->first_child()->plaintext;

		$buffer_row['author_id'] = str_replace(URL_USERFULL,'', $topicAuthorBar->first_child()->href);
		$buffer_row['author_id'] = substr($buffer_row['author_id'],0,-1);

		$buffer_row['views']     = $topicAuthorBar->next_sibling()->find('.views',0)->plaintext;
		$buffer_row['views']     = str_replace(' Views','', $buffer_row['views']);
		$buffer_row['views']     = str_replace(',','', $buffer_row['views']);

		$buffer_row['time_created']   = $topicTitleBar->find('.topic_title',0)->getAttribute('title');
		$buffer_row['time_created']   = str_replace('View topic, started  ','', $buffer_row['time_created']);
		$buffer_row['time_created']   = str_replace(' - ',' ', $buffer_row['time_created']);
		$buffer_row['time_created']   = strtotime($buffer_row['time_created']);

        $buffer_row['time_indexed']   = time();

        $buffer_row['tags']           = tagger_scan($buffer_row['title']);

        $buffer[$topicID] = $buffer_row;
        $stat_rows++;

		// Clear all the data pre-next loop as these objects take a massive amount of memory and may contain circular references
		$topicTitleBar->clear();
		$topicAuthorBar->clear();
	}

    print_web($stat_rows . ' rows scraped!'.N);

	$html->clear();
}


?>