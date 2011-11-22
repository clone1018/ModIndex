<?php
/* ========================
 * THE MINECRAFT MOD INDEX
 * ACCESS CONTROL LIBRARY
 * ========================
 * @package ModIndex
 * @subpackage sec_
 * @author Major Rasputin <major.rasputin@simplaza.net>
 */

require_once 'common.php';
require_once 'error.php';

define('ACCESS_USER', 0);
define('ACCESS_INDEXMASTER', 1);
define('ACCESS_ADMIN', 2);
define('ACCESS_EXEC', 3);

$ERROR_UNAUTHORIZED = array(
	'This page requires an authentication code.',
	'Cette page nécessite un code d\'authentification.',
	'Denna sida kräver en verifiering kod.',
	'Esta página requer um código de autenticação.',
	'Эта страница требует аутентификации кода.',
	'这页需要一个认证码。',
	'이 페이지는 인증 코드를 필요로.'
);

$_ACCESSLEVEL = ACCESS_USER;

function sec_authorize($min = 1) {
	global  $ERROR_UNAUTHORIZED, $argv;

    if ( isset($_GET['auth']) ) {
        if ($_GET['auth'] == AUTH_INDEXMASTER && $min <= ACCESS_INDEXMASTER) return ACCESS_INDEXMASTER;
        if ($_GET['auth'] == AUTH_ADMIN && $min <= ACCESS_ADMIN) return ACCESS_ADMIN;
    }
    
    if ($argv[1] == AUTH_EXECUTION) return ACCESS_EXEC;

	err_panic(ERROR_FATAL, $ERROR_UNAUTHORIZED, 401);
}

?>