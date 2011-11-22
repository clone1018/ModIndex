<?php
/* ========================
 * THE MINECRAFT MOD INDEX
 * ERROR GEN LIBRARY
 * ========================
 * @package ModIndex
 * @subpackage err_
 * @author Major Rasputin <major.rasputin@simplaza.net>
 */

require_once 'common.php';
php_includeonly(__FILE__);

define('ERROR_FATAL', '#550000');
define('ERROR_WARNING', '#000055');

$ERROR_NOINDEX = array(
	'The index is missing. The server may be in the middle of generating it or there may be a more serious issue. Please try again in a few seconds or email the <a href="mailto:'.EMAIL_ADMIN.'?subject=\'Help help! Modlist issue!\'">administrator</a>.',
	'L\'index est manquant. Le serveur peut être au milieu de celui-ci de produire ou il peut y avoir un problème plus grave. S\'il vous plaît essayez de nouveau en quelques secondes ou par courriel à <a href="mailto:'.EMAIL_ADMIN.'?subject=\'Help help! Modlist issue!\'">l\'administrateur</a>.',
	'Indexet saknas. Servern kan vara i mitten för att generera det eller det kan finnas en mer allvarlig fråga. Försök igen om några sekunder eller e-post <a href="mailto:'.EMAIL_ADMIN.'?subject=\'Help help! Modlist issue!\'">administratören</a>.',
	'O índice está faltando. O servidor pode estar no meio de gerá-la ou pode haver um problema mais grave. Por favor, tente novamente em alguns segundos ou e-mail do <a href="mailto:'.EMAIL_ADMIN.'?subject=\'Help help! Modlist issue!\'">administrador</a>.',
	'индекс отсутствует. Сервер может быть в середине ее получения или может быть более серьезной проблемой. Повторите попытку через несколько секунд или по электронной почте <a href="mailto:'.EMAIL_ADMIN.'?subject=\'Help help! Modlist issue!\'">администратору</a>.',
	'该指数已丢失。服务器可能在它的中间产生或有可能是一个更严重的问题。请在几秒钟或电子邮件<a href="mailto:'.EMAIL_ADMIN.'?subject=\'Help help! Modlist issue!\'">管理员了</a>。',
	'색인이 누락되었습니다. 서버가 그것을 생성하는 중간에있을 수있는 더 심각한 문제가있을 수 있습니다. 몇 초 또는 이메일 <a href="mailto:'.EMAIL_ADMIN.'?subject=\'Help help! Modlist issue!\'">관리자에</a> 다시 시도하십시오.'
);

$ERROR_XYZZY = array(
	'Nothing happens.',
	'Rien ne se passe.',
	'Ingenting händer.',
	'Nada acontece.',
	'Ничего не происходит.',
	'什么也没有发生。',
	'아무것도 나타나지 않습니다.'
);

function err_panic($level, $errors, $code = 500) {
	if (PHP_SAPI == 'cli') die('PANIC: '.$errors[0]);
	
	header('HTTP/1.0 '.$code);
	
	?>
	
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	<link rel="shortcut icon" href="favicon.ico" />
	
	<style type="text/css">
	body {
		background-color: <?php echo $level; ?>;
		color: #ddd;
		text-shadow: 0px 2px #000;
		
		font-family: sans-serif;
		font-size: large;
	}
	
	div {
		padding: 5% 20%;
	}
	
	a {
		color: #fff;
		text-decoration: none;
		text-shadow: 0px 0px 2px;
	}
	</style>
	
	<title>PANIC</title>
</head>

<body>
	<div>
	<?php foreach ($errors as $error) echo '<p>'.$error.'</p>'.N; ?>	
	</div>
</body>

</html>

    <?php
	die();
} 
?>