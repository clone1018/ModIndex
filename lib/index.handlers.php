<?php

/* ============================================
  THE MINECRAFT MOD INDEX
  USER INTERFACE HANDLERS
  Version: v1.3

  by The Major / Crome Tysnomi / Ayman Habayeb
  http://gnu32.deviantart.com
  ============================================
 */

php_includeonly(__FILE__);

// ============
// RANDOM ENTRY
// ============

// Have we been asked for a random mod?
if ( isset($_GET['random']) ) {
    while (true) {
        if (empty($INDEX))
            die('Could not pick a random mod! There are no up-to-date mods in the index.');

        $rand = array_rand($INDEX);

        // Unversioned? Skip.
        if (!$INDEX[$rand]['version']) {
            unset($INDEX[$rand]);
            continue;
        }

        if ( preg_match(VERSION_MINECRAFT, $INDEX[$rand]['version']) ) {
            header('Location: ' . URL_TOPIC . $rand);
            exit;
        } else {
            unset($INDEX[$rand]);
        }
    }
}

// =================
// SETTINGS HANDLERS
// =================

// ===== User Settings Handling
// Have we been asked to export user settings?
if ( isset($_GET['export']) && !empty($_COOKIE) ) {
    header('Content-Type: application/octet-stream; charset=utf-8');
    header('Content-Disposition:attachment;filename="modindex.txt"');

    echo json_encode($_COOKIE);
    exit;
}

if (isset($_GET['importsuccess']))
    $DIALOG = 'Your settings and favorites were successfully imported.';

// ============
// RAW HANDLERS
// ============

// Have we been asked for raw CSV data?
if ( isset($_GET['raw']) ) {
	$data;
	
	if ( is_numeric($_GET['id']) ) {
		$data[$_GET['id']] = $INDEX[$_GET['id']];
	} else {
		
		if ( !empty($USER['SEARCHQUERY']) )
			search_engine();
			
		if ( isset($_GET['favorites']) && $USER['FAVORITES'] ) {
			foreach ($USER['FAVORITES'] as $fav) {
				if ( array_key_exists($fav, $INDEX) )
					$SUBINDEXES['fav'][$fav] = $INDEX[$fav];
			}
			
			$data = $SUBINDEXES['fav'];
		} else {
			$data = $INDEX;
		}
		
	}
		
	if ( $_GET['raw'] == 'json' ) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data);
		
	} else {
		header('Content-Type: text/plain; charset=utf-8');
		echo 'id,' . implode(',',$ENUM_INDEXFIELDS) . N;

		foreach ($data as $key => $row) {
			echo $key;
			
			foreach ($ENUM_INDEXFIELDS as $field)
				echo ',' . (is_numeric($row[$field]) ? $row[$field] : '"' . addcslashes( un_html($row[$field]), '"') . '"');
				
			echo N;
		}
	}

    exit;
}

// ==============
// EMAIL HANDLERS
// ==============

// Any email handling requires the email library
if ( isset($_GET['email']) || $_POST['email'] )
    require_once('lib/system.email.php');

// Are we handling email data via GET? (External links; verifying, deverifying and denotifying)
if ( isset($_GET['email']) ) {

    // Make sure all emails are lowercase
    lower($_GET['email']);

    if ($_GET['verify'] && $_GET['address'])
        $DIALOG = verify($_GET['address'], $_GET['verify']) ? 'Email has been verified! You will now recieve notifications you\'ve signed up for.' : 'Failure! Could not verify email.';
    else if ($_GET['deverify'] && $_GET['address'])
        $DIALOG = deverify($_GET['address'], $_GET['deverify']) ? 'Email has been deverified! You will no longer recieve notifications from the index at all.' : 'Failure! Could not deverify email.';
    else if ($_GET['remove'] && $_GET['address'] && is_numeric($_GET['type']))
        $DIALOG = notification_delete($_GET['remove'], $_GET['type'], $_GET['address']) ? 'You will no longer recieve this specific notification.' : 'Failure! Could not remove notification.';

    emaildb_save();
}

// Are we handling email data via POST? (Internal forms; signing up for notifications)
if ($_POST['email']) {

    // Prevents external form spamming (to some degree).
    if (!preg_match(REGEX_REFERERCHECK, $_SERVER['HTTP_REFERER']))
        die('I don\'t think so, sweetheart.');

    // Make sure all emails are lowercase
    lower($_POST['email']);

    // Make sure we've also been given the ID and type. Fail silently because we won't cater to form tampering n00bs.
    if (is_numeric($_POST['id']) && is_numeric($_POST['type'])) {
        // Add the notification, regardless of verified email or not.
        $notification = notification_add($_POST['id'], $_POST['type'], $_POST['email']);

        switch (check_verify($_POST['email'])) {
            case 2:
                $DIALOG = 'Your email is still awaiting verification! You won\'t recieve notifications until you verify it.';
                break;
            case 1:
                send_verification($_POST['email']);
                $DIALOG = 'You\'ve just been sent a verification email from <em>notifications@mods.simplaza.net</em>. Please click the link in it to ensure the email address belongs to you.';
                break;
            default:
                $DIALOG = $notification ? 'You will now recieve email notifications for mod:<br /><i>' . $INDEX[$_POST['id']]['title'] . '</i>' : 'You\'ve already signed up for this notification!';
                break;
        }

        emaildb_save();
    }
}

// ===============
// EDITOR HANDLERS
// ===============

if ( is_numeric($_POST['form_editor_id']) ) {
	$id = $_POST['form_editor_id'];
	
	if ( array_key_exists($id, $INDEX) ) {
		$row = $INDEX[$id];
		$changes = '';
		$url = 'http://'.(TESTING ? 'localhost/mods' : 'mods.simplaza.net').'/_admin.php?admin='.AUTH_ADMIN.'&row_changes='.$id;
		$url_blacklist = 'http://'.(TESTING ? 'localhost/mods' : 'mods.simplaza.net').'/_admin.php?admin='.AUTH_ADMIN.'&blacklist_add='.$_SERVER['REMOTE_ADDR'];
		
		foreach ($ENUM_INDEXFLAGS as $flag => $desc) {
			$checked = $_POST['checkbox_editor_'.$flag];
			
			$changes .= "<li><b>$desc:</b> $checked</li>";
			$changes .= "<li><b>Previous $desc:</b> $row[$flag]</li><hr />";
			$url .= $checked ? "&$flag=$checked" : '';
		}
		
		$changes .= '<li><b>Keywords:</b> '.$_POST['textbox_editor_keywords'].'</li>';
		$changes .= '<li><b>Previous keywords:</b> '.$row['keywords'].'</li>';
		$url .= '&keywords='.str_replace( array("\n","\r"), '', $_POST['textbox_editor_keywords'] );
		
		$body = '
		<p>
			<h1><a target="_blank" href="'.URL_TOPIC.$id.'">['.$row['version'].'] '.html($row['title']).'</a></h1>
			<h4>'.html($row['desc']).'</h4>
			<ul>
			<li>Author: <a target="_blank" href="'.URL_USER.$row['author_id'].'">'.$row['author'].'</a></li>
			<li>Views: '.$row['views'].'</li>
			</ul>
		</p>';
		
		$body .= '
		<p>
			<h1>Suggested changes:</h1>
			<ul>'.$changes.'</ul>
		</p>';
		
		$body .= '<h3><a href="' . $url . '">&#187; Accept</a></h3>';
		$body .= '<h3><a href="' . $url_blacklist . '">&#187; Blacklist IP</a></h3>';
		
		
		email_alert($_SERVER['REMOTE_ADDR'], 'Custom data: ' . $INDEX[$id]['title'], $body);
		
		unset($body, $changes, $url, $url_blacklist, $row, $checked);
		$DIALOG = 'Thank you! Your suggested edits have been sent to the administrator.';
	} else {
		$DIALOG = 'Failure editing '.$id.': That row doesn\'t exist in the index!';
	}

	unset($id);
}
?>