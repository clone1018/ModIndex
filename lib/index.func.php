<?php

/* ============================================
  THE MINECRAFT MOD INDEX
  USER INTERFACE FUNCTIONALITY
  Version: v1.3

  by The Major / Crome Tysnomi / Ayman Habayeb
  http://gnu32.deviantart.com
  ============================================
 */

require_once('common.php');

php_includeonly(__FILE__);

if (!file_exists(FILE_INDEX)) {
    include_once('lib/err_index.html');
    die();
}

// =========
// CONSTANTS
// =========

// ===== Filter states
define(FILTER_ONLY, 1);
define(FILTER_EXCLUDE, -1);

// ===== Sorting tweaks
define(SORT_MAGIC_VIEWSTHRESHOLD, 200);
define(SORT_MAGIC_VIEWSPROBABILITY, 200);

// ===== Index states
define(STATE_NORMAL, 1);  // Normal listing mode
define(STATE_SEARCHED, 2); // Search results
define(STATE_FILTERED, 4); // Filtered results
define(STATE_SORTED, 8);  // Manually sorted results
define(STATE_LIMITED, 16); // Manually limited results

// ===== Code generation types
define(CODE_HTML, 0);
define(CODE_BBCODE, 1);
define(CODE_REDDIT, 2);

// =======
// GLOBALS
// =======

$INDEX;
$SUBINDEXES;
$BUFFER;
$STATE;
$DIALOG;
$NORMALIZED_CHARSET;

$USER;
$SORTING;
$STATS;

// ===== Subindexes
$SUBINDEXES = array(
    'metadata' => index_load(FILE_METADATA),
	'blacklist' => index_load(FILE_BLACKLIST)
);

// ===== State Global
$STATE = STATE_NORMAL;

// ===== User Data Globals
$USER = array(
    'FAVORITES' => ($_COOKIE['favorites'] ? explode(',', $_COOKIE['favorites']) : NULL),
    'FILTERS' => array(),
    'SEARCHQUERY' => init_searchquery(),
    'SEARCHTERMS' => '',
    'JS_SETTINGS' => json_decode(stripslashes($_COOKIE['settings']), true)
);

// ===== Sorting Globals
// (Don't combine these two; methods need to be defined first for init_sortmethod to work)
$SORTING = array(
    'METHODS' => array(
        'magic' => 'Magic',
        'az' => 'Alphabetical',
        'views' => 'Views',
        'author' => 'Author',
        'created' => 'Created'
    )
);

$SORTING += array(
    'CHOSEN' => init_sortmethod(),
    'REVERSE' => isset($_GET['reverse'])
);

// ===== Statistics Globals
$STATS = array(
    'INDEX_ENTRIES' => '',
    'INDEX_SIZE' => (int) (filesize(FILE_INDEX) / 1024),
    'INDEX_LASTUPDATE' => date('jS F Y h:i:s A', filemtime(FILE_INDEX)) . ' (' . time_ago() . ')',
    'INDEX_PAGE' => file_get_contents(FILE_JOB),
    'SYSTEM_PEAKMEM' => '',
    'SYSTEM_ENV' => (TESTING ? 'Local Testing' : 'Livefire'),
    'COOKIE_COUNT' => count($_COOKIE),
    'COOKIE_LENGTH' => strlen(implode('', $_COOKIE))
);

// =============
// SANITY CHECKS
// =============

if ($USER['SEARCHQUERY'] == 'xyzzy') {
    include_once('lib/err_xyzzy.html');
    die();
}

if ( in_array($_SERVER['REMOTE_ADDR'], $SUBINDEXES['blacklist'] ) ) {
	$error = 'Sorry sweetheart, you ('.$_SERVER['REMOTE_ADDR'].') have been blacklisted!'.BR;
	$error .= 'Reasons for this include abusing the editing system or suspected attacks.'.BR;
	$error .= 'If you wish to contest this, please email the administrator at <a href="mailto:'.EMAIL_ADMIN.'">'.EMAIL_ADMIN.'</a>.'.BR;
	die($error);
}

// ============
// INIT PROGRAM
// ============

$INDEX = index_load(FILE_INDEX);
$STATS['INDEX_ENTRIES'] = count($INDEX);

// ===== Merge metadata into main index
foreach ( $SUBINDEXES['metadata'] as $key => $row ) {
	if ( array_key_exists($key, $INDEX) )
		$INDEX[$key] = array_merge($INDEX[$key], $row);
	
	unset($SUBINDEXES['metadata'][$key]);
}

unset($SUBINDEXES['metadata']);

// ===== Handlers
require_once('index.handlers.php');

// Finally, we process the index and fill the listing's output buffer, fill the statistics, etc.
init_index();

// ==============
// INIT FUNCTIONS
// ==============
// Captures user-definied sort method if any
function init_sortmethod() {
    global $USER, $SORTING;

    lower($_GET['sort']);

    // If user defined sort method exists, then we sort by it and reflect the index's state as such, else default to 'magic'
    if ( array_key_exists($_GET['sort'], $SORTING['METHODS']) ) {
        state_add(STATE_SORTED);
        return $_GET['sort'];
    } else {
        return 'magic';
    }
}

// This captures the search query from all possible inputs and reformats it into an internally clean query
function init_searchquery() {
    global $USER, $NORMALIZED_CHARSET;

    // The index supports searching from both the 'q' parameter and 'mmiquery' parameter
    if (!empty($_GET['q']))
        $query = $_GET['q'];
    else if (!empty($_GET['mmiquery']))
        $query = $_GET['mmiquery'];
    else
        return false;

    // We got a search term, so the index is in search state
    state_add(STATE_SEARCHED);

    // Assigning this here for memory's sake; this is used to make searches easier
    // Thanks to http://www.php.net/manual/en/function.strtr.php#98669
    $NORMALIZED_CHARSET = array(
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
        'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i',
        'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
        'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u',
        'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
        'ÿ' => 'y', 'ƒ' => 'f'
    );

    lower($query);

    // Cleans the query of weird HTML stuff, then converts accented characters into normal ones
    $clean = trim($query);
    $clean = strtr($clean, $NORMALIZED_CHARSET);
    return $clean;
}

// The super-function that handles the main listing and index processing
function init_index() {
    global $INDEX, $SUBINDEXES, $BUFFER, $USER, $SORTING, $STATS, $STATE;

    // If we've got a search query, run the search engine
    if (!empty($USER['SEARCHQUERY']))
        search_engine();

    // Pick out favorites from the index and put them into a subindex
    if ($USER['FAVORITES']) {

        foreach ($USER['FAVORITES'] as $fav) {
            if (array_key_exists($fav, $INDEX)) {
                $SUBINDEXES['fav'][$fav] = $INDEX[$fav];
                unset($INDEX[$fav]);
            }
        }

        // Generate the listing for favorite mods, seperated by headers
        if ($SUBINDEXES['fav']) {
            $BUFFER .= list_heading('Favorites', '<span class="right"><a onclick="shareFavorites();" href="#" title="Exports your favorites to shareable formats!">Share</a></span>');

            sort_index($SUBINDEXES['fav']);
            array_walk($SUBINDEXES['fav'], 'list_item', 'mF');
        }
    }

    if ($USER['FILTERS']['favorites'] == FILTER_ONLY) {
        unset($INDEX);
    } else if (!empty($INDEX)) {
        // Sorting index BEFORE limiting it, otherwise we end up sorting the smaller random selection
        sort_index($INDEX);

        // If the index is not being searched, filtered or manually sorted, show Mod of the Week
        // FIXME: This is depressing, this isn't how bitwise operations aren't supposed to work! Can somebody clean this up?
        if (( ($STATE == STATE_NORMAL) || ($STATE == (STATE_NORMAL | STATE_LIMITED) ) ) && !$USER['JS_SETTINGS']['style']['disable_motw'])
            init_index_motw();

        // If after favorites and search filter the index has more than 150 entries, then we limit unless specified otherwise
        $results = count($INDEX);
        $limited = init_index_limit($results);

        // Finally, we generate all the listing rows to buffer.
        $BUFFER .= list_heading('Forum');
        array_walk($INDEX, 'list_item');

        if ($limited) {
            $BUFFER .=
                    '<div class="limitedlisting">
				To conserve bandwidth and make loading this page quicker,<br />
				this listing has been limited to <b>' . $limited . '</b> out of <b>' . $results . '</b> results.<br />
				<br />
				How many would you like to see?
				
				<div class="toolbar" id="toolbar_limits">' .
                    ui_list_limits($limited)
                    . '</div>
				
			</div>';
        }
    }

    if (empty($INDEX) && empty($SUBINDEXES['fav'])) {
        $BUFFER .=
                '<div class="emptylisting">
			There are no mods to show.<br />    
			Il n\'y a pas de modifications à afficher.<br />
			Det finns inga ändringar att visa.<br />
			Não há modificações para mostrar.<br />
			Нет никаких модов, чтобы показать.<br />
			表示するには変更はありません。<br />
			표시에는 변경이 없습니다.<br />
		</div>';
    }
}

// Sub-process that limits the index to a set, definied or otherwise unlimited value
function init_index_limit($results) {
    global $INDEX;

    if ($results > 150) {
        $limit = $_GET['limit'];

        // If limit's numeric and it's between zero and the number of rows in index, we're limiting it
        if (is_numeric($limit) && ($limit >= 0) && ($limit < $results)) {
            state_add(STATE_LIMITED);

            $INDEX = array_slice($INDEX, 0, $limit, TRUE);
            return $limit;
            // If limit's defined as 'all' or bigger than the results, don't bother limiting.
        } else if (($limit >= $results) || (strtolower($limit) == 'all')) {
            return false;
            // Else, by default we limit to 150
        } else {
            $INDEX = array_slice($INDEX, 0, 150, TRUE);
            return 150;
        }
    }

    return false;
}

// Sub-process that generates Mod of the Week
function init_index_motw() {
    global $INDEX, $BUFFER;

    // Fetch the first MOTW from the queue (forcing as int)
    $motw = (int) file_readline(FILE_MOTW, 'Could not read the Mod of the Week queue!');

    // If it's not in the index, just ignore it
    if (!array_key_exists($motw, $INDEX))
        return false;

    $BUFFER .= list_heading(
            'Mod of the Week', '<span class="right"><a onclick="motwHide()" href="#" title="Disables Mod of the Week; use the Settings panel to re-enable!">Disable</a></span>'
    );
    list_item($INDEX[$motw], $motw);
    unset($INDEX[$motw]);
}

// ====================
// UI DRAWING FUNCTIONS
// ====================
// ===== Direct-Write-to-Page Main UI Elements

function ui_dialog() {
    global $DIALOG;

    if ($DIALOG)
        echo '<div title="Click to close this message" onclick="this.parentNode.removeChild(this.nextElementSibling); this.parentNode.removeChild(this);" class="dialog">'.$DIALOG.'</div>
				<img src="style/dialog.bg.png"/>';
}

// Generates a hidden form field to pass through URL parameters to the next page (useful for search form to keep sorts)
function ui_fieldpassthrough($name, $data) {
    if ($data)
        echo "<input name='$name' type='hidden' value='$data' />";
}

// ===== Element Listing Functions
// Directly writes buttons for each sorting method avaliable, automatically styling them and linking them for selected, reverse, previous queries, etc
function ui_list_sorts() {
    global $SORTING, $USER;
    $code;

    foreach ($SORTING['METHODS'] as $method => $name) {
        $current = ($SORTING['CHOSEN'] == $method);
        $code =
                '<a class="button ' . ($current ? ( $SORTING['REVERSE'] ? 'selected2 ' : 'selected') : '') . '" ' .
                'href="?' . ($USER['SEARCHQUERY'] ? 'q=' . urlencode($USER['SEARCHQUERY']) . '&' : '') . ($_GET['limit'] ? 'limit=' . $_GET['limit'] . '&' : '' ) . ( ($current && !$SORTING['REVERSE']) ? 'reverse&' : '') . "sort=$method\">" .
                $name . '</a>';

        echo $code;
    }
}

function ui_list_limits($limit) {
    $labels = array('Less', 'More', 'Even More');
    $scalefactors = array(0.5, 2, 4);
    $query = $_SERVER['QUERY_STRING'];
    $code;

    // FIXME: MAJOR BUG: Will keep any email-related query parameters in the URL, which can result in annoying behavior.
    // If the URL query already contains a limit parameter, kill it
    if (strpos($query, 'limit=') !== FALSE)
        $query = preg_replace('/(&)?limit\=(\d)+/i', '', $query);

    // First chunk of link code for each button
    $html = '<a class="button" href="?' . $query . '&limit=';

    // Generate buttons for the three scale factors
    for ($i = 0; $i < 3; $i++)
        $code .= $html . ( floor($limit * $scalefactors[$i]) ) . '" >' . $labels[$i] . '</a>';

    // The 'all' button
    $code .= $html . 'all" title="Warning, this is slow!">All</a>';

    return $code;
}

// ===== Mod Listing HTML Generation Functions
// Generates a heading for the list (for categories, favorites, etc)
function list_heading($title, $html = null) {
    return "<div class=\"heading\">$title $html</div>";
}

// Walking function that generates a row of data in HTML format to the global buffer.
function list_item(&$row, $key, $classes = null) {
    global $BUFFER, $USER;

    // Limits title's length and snips it with ellipses
    if (strlen($row['title']) > 80)
        $row['title'] = substr($row['title'], 0, 80) . '...';

    // If description is empty, add a whitespace to double-line
	$time_created = $USER['JS_SETTINGS']['style']['hide_time_created'] ? false : ($row['time_created'] ? date('H:i d/m/Y \- ', $row['time_created']) : '<em>??:?? ??/??/???? - </em>');
    $desc = $USER['JS_SETTINGS']['style']['hide_desc'] ? false : $time_created.( $row['desc'] ? html($row['desc']) : '&nbsp;');
    $views = $USER['JS_SETTINGS']['style']['hide_views'] ? false : "<b>$row[views]</b> views";
    $author = $USER['JS_SETTINGS']['style']['hide_author'] ? false : "<b><a target='_blank' href='" . URL_USER . "$row[author_id]'>$row[author]</a></b>" . ($views ? ',' : '');
    $flags = ( $USER['JS_SETTINGS']['style']['hide_flags'] || empty($row['version']) ) ? false : '<div class="flags">' . list_item_flags($row) . '</div>';

    $BUFFER .=
            "<div id='$key' class='mod " . list_item_vclass($row['version']) . ' ' . $classes . "'>
			<div class='button_extend'>&raquo;</div>
			
			<div class='meta'>
				 $author $views
				 $flags
			</div>
			
			<a class='link_mod' target='_blank' href='" . URL_TOPIC . "$key'><h2>" . list_item_v($row['version']) . html($row['title']) . '</h2>' .
            $desc . '</a>
		</div>';
}

	// Sub-fuction of list_item that generates the Version tag (e.g. [1.7.3])
	function list_item_v($v) {
		return '<span class="version">[' . (empty($v) ? '???' : $v) . ']</span> ';
	}

	// Sub-function of list_item that determines if a given version is the latest and returns the appropriate classname
	function list_item_vclass($v) {
		if (empty($v))
			return 'mX';
		else
			return preg_match(VERSION_MINECRAFT, $v) ? 'mU' : 'mO';
	}

	// Sub-fuction of list_item that generates flags
	function list_item_flags($row) {
		global $ENUM_INDEXFLAGS;
		$buffer;

		foreach ($ENUM_INDEXFLAGS as $flag => $desc)
			$buffer .= $row[$flag] ? '<span class="flag">' . $desc . '</span> ' : '';

		return $buffer;
	}

// Converts list items to code (e.g. for sharing to other sites)
function list_code($items, $type = CODE_HTML) {
	
	if ( !is_array($items) )
		$items = array($items);
	
	switch($type) {
		case CODE_HTML:
			echo '&lt;ul&gt;'.N;
			array_walk($items, 'list_code_item', $type);
			echo '&lt;/ul&gt;';
			break;
		case CODE_BBCODE:
			echo '[list]'.N;
			array_walk($items, 'list_code_item', $type);
			echo '[/list]';
			break;
		case CODE_REDDIT:
			array_walk($items, 'list_code_item', $type);
			break;
	}

}	

	// Converts list items to code (e.g. for sharing to other sites)
	function list_code_item(&$row, $key, $type = CODE_HTML) {
		$buffer;
		
		switch($type) {
			case CODE_HTML:
				$buffer = '<li><a href="'.URL_TOPIC.$key.'">'.$row['title'].'</a> by <a href="'.URL_USER.$row['author_id'].'">'.$row['author'].'</a></li>'.N;
				break;
			case CODE_BBCODE:
				$buffer = '[*][url="'.URL_TOPIC.$key.'"]'.$row['title'].'[/url] by [url="'.URL_USER.$row['author_id'].'"]'.$row['author'].'[/url]'.N;
				break;
			case CODE_REDDIT:
				$buffer = '* ['.$row['title'].']('.URL_TOPIC.$key.') by ['.$row['author'].']('.URL_USER.$row['author_id'].')'.N;
				break;
		}
		
		echo html($buffer);
	}

// ===== Buffer Functions
// Compresses the global $BUFFER by removing whitespace and flushes it to output.
function buffer_flush() {
    global $BUFFER;

    echo preg_replace('/(?:\t|\n|\r)/i', '', $BUFFER);
    unset($BUFFER);
}

// ==========
// STATISTICS
// ==========
// =================
// SORTING FUNCTIONS
// =================
// Applies sorting functions to an index
function sort_index(&$index) {
    global $SORTING;

    switch ($SORTING['CHOSEN']) {
        case 'az':
            uasort($index, 'sortby_az');
            break;

        case 'views':
            uasort($index, 'sortby_views');
            break;

        case 'author':
            uasort($index, 'sortby_author');
            break;
			
		case 'created':
            uasort($index, 'sortby_created');
            break;

        case 'magic':
        default:
            uasort($index, 'sortby_magic');
    }

    if ($SORTING['REVERSE'])
        $index = array_reverse($index, true);
}

// SORT: By magic; Unversioned mods to the bottom, then outdated mods, then latest mods sorted by views
function sortby_magic($a, $b) {

    // Keep unversioned mods to the bottom...
    if ( empty($a['version']) )
        return 1;
    else if ( empty($b['version']) )
        return -1;

    // Then updated mods after that...
    $au2d = preg_match(VERSION_MINECRAFT, $a['version']);
    $bu2d = preg_match(VERSION_MINECRAFT, $b['version']);

    if (!$au2d && $bu2d)
        return 1;
    else if ($au2d && !$bu2d)
        return -1;
		
    // Then we sort by views.
    return ($a['views'] > $b['views']) ? -1 : 1;
}

function sortby_az($a, $b) {
    return strcasecmp($a['title'], $b['title']);
}

function sortby_views($a, $b) {
    return ($a['views'] > $b['views']) ? -1 : 1;
}

function sortby_author($a, $b) {
    return strcasecmp($a['author'], $b['author']);
}

function sortby_created($a, $b) {
    return ($a['time_created'] > $b['time_created']) ? -1 : 1;
}

// =============
// SEARCH ENGINE
// =============
// Main search-handling code; picks out filter keywords and executes filter
function search_engine() {
    global $INDEX, $USER;
    $USER['SEARCHTERMS'] = explode(' ', $USER['SEARCHQUERY']);

    // Look for Favorites filter, remove from terms if found
    if (is_numeric($k = array_search('!favorites', $USER['SEARCHTERMS']))) {
        $USER['FILTERS']['favorites'] = FILTER_ONLY;
        unset($USER['SEARCHTERMS'][$k]);
    }

    // Look for Updated filter, remove from terms if found
    if (is_numeric($k = array_search('!updated', $USER['SEARCHTERMS']))) {
        $USER['FILTERS']['updated'] == FILTER_ONLY;
        array_walk($INDEX, 'filterby_latest');
        unset($USER['SEARCHTERMS'][$k]);
    }

    // Adfly filter
    if (is_numeric($k = array_search('~adfly', $USER['SEARCHTERMS']))) {
        $USER['FILTERS']['adfly'] == FILTER_EXCLUDE;
        array_walk($INDEX, 'filterby_adfly');
        unset($USER['SEARCHTERMS'][$k]);
    }

    // If we have filters, change state as appropriate
    if (!empty($USER['FILTERS']))
        state_add(STATE_FILTERED);

    // Put terms back together again, minus filters.
    $USER['SEARCHTERMS'] = implode(' ', $USER['SEARCHTERMS']);

    // If we still have a query after filters, execute search filter!
    if (!empty($USER['SEARCHTERMS']))
        array_walk($INDEX, 'filterby_search');
}

function filterby_latest($row, $key) {
    global $INDEX;

    if (!preg_match(VERSION_MINECRAFT, $row['version']))
        unset($INDEX[$key]);
}

function filterby_adfly($row, $key) {
    global $INDEX;

    if ($INDEX[$key]['flag_adfly'])
        unset($INDEX[$key]);
}

function filterby_search($row, $key) {
    global $INDEX, $USER, $NORMALIZED_CHARSET;

    // Generate the "search definition" for the row
    $rowcontent = strtr($row['title'], $NORMALIZED_CHARSET) . '[' . (empty($row['version']) ? '???' : $row['version']) . ']' . $row['author'] . $row['desc'];
    $rowcontent .= $INDEX[$key]['keywords'];

    lower($rowcontent);

    if (stripos($rowcontent, $USER['SEARCHTERMS']) === false)
        unset($INDEX[$key]);
}

// ==============
// MISC FUNCTIONS
// ==============

function state_add($state) {
    global $STATE;

    $STATE = $STATE | $state;
}

// =====================
// PRE-HTML FINALIZATION
// =====================
$STATS['SYSTEM_PEAKMEM'] = (int) (memory_get_peak_usage() / 1024);
?>