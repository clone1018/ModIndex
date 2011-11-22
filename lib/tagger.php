<?php
/* ========================
 * THE MINECRAFT MOD INDEX
 * TAG PROCESSING LIBRARY
 * ========================
 * @package ModIndex
 * @subpackage tagger_
 * @author Major Rasputin <major.rasputin@simplaza.net>
 */

require_once 'common.php';
php_includeonly(__FILE__);

// Scans a string for tags between common tag character conventions
function tagger_scan($title) {
    $tags = '';
    $matches = preg_match_all(REGEX_TAGS, $title, $tags);

    if ($matches === false)
        return false;

    return $tags[0];
}

function tagger_content($tag) {
    return substr($tag,1, strlen($tag) - 1);
}

function tagger_remove($tag, $string) {
    $str = str_replace($tag, '', $string);
    return trim($str);
}
?>