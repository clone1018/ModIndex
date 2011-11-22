<?php
/* ========================
 * THE MINECRAFT MOD INDEX
 * VERSION PROCESSING LIBRARY
 * ========================
 * @package ModIndex
 * @subpackage version_
 * @author Major Rasputin <major.rasputin@simplaza.net>
 */

require_once 'common.php';
require_once 'tagger.php';

function is_version($string) {
    $matches = preg_match(REGEX_VERSION, $string);
    return pick($matches, false);
}

function is_latest($ver) {
    return (boolean)preg_match(VERSION_MINECRAFT, $ver);
}

function version($string) {
    $matches = '';
    preg_match_all(REGEX_VERSION, $string, $matches, PREG_SET_ORDER);

    return $matches;
}

function version_clean($version, $index = 0) {
    if ( !is_version($version) )
        return false;

    $version = version($version);
    $clean = $version[$index];

    if ( !isset($clean['build']) )
        $clean['build'] = '0';

    $status = (isset($clean['status']) ? ' '.$clean['status'].$clean['statusbuild'] : '' );

    return $clean['major'].D.$clean['minor'].D.pick($clean['build'],'0').$status;
}

// =========
// TEST UNIT
// =========

if ( testunit() ) {
    //php_astext();
    echo gettype(is_latest('1.0.0')).BR;
    echo is_latest('1.1.0').BR;
    echo is_latest('1.2.0').BR;
    echo is_latest('1.3.0').BR;
}
?>