<?php
/* ========================
 * THE MINECRAFT MOD INDEX
 * SCRAPING ENGINE
 * ========================
 * @package ModIndex
 * @author Major Rasputin <major.rasputin@simplaza.net>
 */


require_once 'lib/common.php';
require_once 'lib/security.php';
require_once 'lib/scraper.php';

$_ACCESSLEVEL = sec_authorize();
php_astext();

// =======
// GLOBALS
// =======
$SCRAPE_BUFFER;

// ============
// MAIN PROGRAM
// ============

$JOBS = arraydb_load(FILE_JOB);

foreach ($E_FORUMS as $type => $forum) {
    $SCRAPE_BUFFER[$type] = scraper_loadpage($forum, 7, $JOBS[$type]);
    scraper($type, $SCRAPE_BUFFER[$type]);

    arraydb_save(DIRECTORY_DATABASE.$type.TEMP_SUFFIX, $SCRAPE_BUFFER[$type]);
}

arraydb_save(FILE_JOB, $JOBS);

?>