<?php
/* ========================
 * THE MINECRAFT MOD INDEX
 * INDEXES LIBRARY
 * ========================
 * @package ModIndex
 * @subpackage index_
 * @author Major Rasputin <major.rasputin@simplaza.net>
 */

require_once 'common.php';
require_once 'error.php';
php_includeonly(__FILE__);

$_INDEX = array();

// Plain and simple Index (PHP serialized array format) loader
function index_load($index) {
	global $_INDEX;

	$file = DIRECTORY_DATABASE.$index.INDEX_SUFFIX;
    if ( is_file($file) ) {
        $_INDEX[$index] = arraydb_load($file);
		return true;
    } else {
        $_INDEX[$index] = array();
		return false;
	}
}

function index_require($file) {
    if ( !is_file($file) )
        die('Call to index_require failed: Index ' . $file . ' does not exist!');

    return unserialize( file_get_contents($file) );
}

// Plain and simple Index writer
function index_save($index) {
	global $_INDEX;

	$file = DIRECTORY_DATABASE.$index.INDEX_SUFFIX;
    arraydb_save($file, $_INDEX[$index]);
}

function index_saveall() {
	global $_INDEX;

	$keys = array_keys($_INDEX);
	foreach ( $keys as $index )
		index_save($index);
}

function index_unload($index) {
    global $_INDEX;

    index_save($index);
    unset($_INDEX[$index]);
}

// Picks an up-to-date index row at random (ONLY SUITABLE FOR THE MAIN INDEX)
function index_random($index, $filter) {
    while (true) {
        if ( empty($index) )
            return false;

        $rand = array_rand($index);

        // Unversioned? Skip.
        if (!$index[$rand]['version']) {
            unset($index[$rand]);
            continue;
        }

        if ( preg_match($filter, $index[$rand]['version']) )
            return $rand;
        else
            unset($index[$rand]);

    }
}

?>