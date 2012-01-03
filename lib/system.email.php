<?php

/* 	============================================
	THE MINECRAFT MOD INDEX
	EMAILING SYSTEM

	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

require_once('common.php');
php_includeonly(__FILE__);

$EMAIL_DB;

// =========
// CONSTANTS
// =========

define(URL_EMAIL, URL_INDEX.'/?email');
define(URL_EMAIL_ADDRESS,'&address=');
define(URL_EMAIL_VERIFY,'&verify=');
define(URL_EMAIL_DENOTIFY,'&remove=');
define(URL_EMAIL_DENOTIFYTYPE,'&type=');
define(URL_EMAIL_DEVERIFY,'&deverify=');

// ============
// MAIN PROGRAM
// ============

if ( !is_file(FILE_EMAIL) ) {
	emaildb_init();
	emaildb_save();
} else {
	emaildb_load();
}


// =============
// NOTIFICATIONS
// =============

// Adds notifications to the database
function notification_add($id, $type, $email) {
	global $EMAIL_DB;
		
	// Blesses the id with it's own array if it doesn't have one
	if ( !is_array($EMAIL_DB[$type][$id]) )
		$EMAIL_DB[$type][$id] = array();
	
	if ( !has_notification($id, $type, $email) ) {
		array_push($EMAIL_DB[$type][$id],$email);
		return true;
	}
	
	return false;
		
}

// Removes notifications from the database
function notification_delete($id, $type, $email) {
	global $EMAIL_DB;
	
	if ( has_notification($id, $type, $email) ) {
		array_splice($EMAIL_DB[$type][$id], array_search($email,$EMAIL_DB[$type][$id]) , 1);
		
		// If we removed the last notification from a topic id + type, delete the array
		if ( empty($EMAIL_DB[$type][$id]) )
			unset($EMAIL_DB[$type][$id]);
			
		return true;
	}
		
	return false;
}

// Check if an email with ID + type has notifications registered to it
function has_notification($id, $type, $email) {
	global $EMAIL_DB;
	
	// If array doesn't exist, that mod ID simply does not have any notifications.
	// This is seperate as in_array will generate a warning.
	if ( !is_array($EMAIL_DB[$type][$id]) )
		return false;
	
	if ( in_array($email, $EMAIL_DB[$type][$id]) )
		return true;
	
	return false;
}

// Fires off a notification; should be called outside _email.php
function notification_send($id, $type, $email, $oldrow, $newrow, $force = false) {
	
	// ONLY send notifications if target email is verified. No exceptions.
	if ( !is_verified($email) && !$force )
		return false;
	
	// Generate our subject based on notification type
	switch ($type) {
		case NOTIFICATION_VERSION:
		$subject = $oldrow['title'].' has been updated from version '.$oldrow['version'].' to version '.$newrow['version'];
		break;
		case NOTIFICATION_TITLE:
		$subject = $oldrow['title'].' has been renamed to '.$newrow['title'];
		break;
	}

	// Generate the body purely with fields. Consistent layout and style across types.
	$body = '
		<p>
			<h1><a target="_blank" href="'.URL_TOPIC.$id.'">['.$newrow['version'].'] '.html($newrow['title']).'</a></h1>
			<h4>'.html($newrow['desc']).'</h4>
			<ul>
			<li>Author: <a target="_blank" href="'.URL_USER.$newrow['author_id'].'">'.$newrow['author'].'</a></li>
			<li>Views: '.$newrow['views'].'</li>
			</ul>
		</p>';
	
	email_send($email, $subject, $body, TRUE, $type, $id);
	return true;
}

function notification_count($email) {
	global $EMAIL_DB;
	$count = 0;
	
	for ($i = 0; $i < 2; $i++) {
		foreach ($EMAIL_DB[$i] as $notification) {
			if ( in_array($email, $notification) )
				$count++;
		}
	}
	
	return $count;
}
// =============
// VERIFICATIONS
// =============

// Generates and sends verification to email
function send_verification($email) {
	global $EMAIL_DB;
	$code = mt_rand(10000,99999);
	
	// Apply generated verification code to email in verification list
	$EMAIL_DB[2][$email] = $code;
	email_send(
		$email,
		'Please verify your email to recieve notifications from the Minecraft Mod Index',
		"<p>Your email was used to sign up for a notification on the <a href='" . URL_INDEX . "'>Minecraft Mod Index</a>.
		 If this was you, please <a href='"
		 .URL_EMAIL
		 .URL_EMAIL_ADDRESS.$email
		 .URL_EMAIL_VERIFY.$code
		 .'\'>click here to verify</a>. Otherwise, please ignore this email.</p>'
	);
}

// Checks status of verification
function check_verify($email) {
	global $EMAIL_DB;

	// If email has md5 hash of itself as verification code, it's verified.
	if ($EMAIL_DB[2][$email] == md5($email))
		return 3;	// Verified!
	else if ( !empty($EMAIL_DB[2][$email]) )
		return 2;	// Awaiting verification!
	else
		return 1;	// Not verified
}

// Shortcut of check_verify, which DOES NOT CHECK if email is being verified.
function is_verified($email) {
	return (check_verify($email) == 3) ? TRUE : FALSE;
}

// Adds email to verification
function verify($email, $code) {
	global $EMAIL_DB;
	
	// Email hasn't been sent a verification!
	if (!array_key_exists($email, $EMAIL_DB[2]))
		return false;
	
	// Email is already verified!
	if (is_verified($email))
		return false;
	
	// Set the email's verification code to md5 hash, as a deverification code
	if ($code == $EMAIL_DB[2][$email]) {
		$EMAIL_DB[2][$email] = md5($email);
		return true;
	}
	
	// Failure, wrong code?
	return false;
}

// Removes email from verification, preventing any emails
// v0.53 - Made it so it deverifies emails awaiting verification too
function deverify($email, $code) {
	global $EMAIL_DB;
	
	// Email isn't verified!
	if ( !array_key_exists($email, $EMAIL_DB[2]) )
		return false;
	
	// If the given code matches verification code, deverify!
	if ($code == $EMAIL_DB[2][$email]) {
		unset($EMAIL_DB[2][$email]);
		return true;
	}
	
	return false;
}

// =================
// DATABASE HANDLING
// =================

// Database structure:
// [0] - Notifications for versions
// [1] - Notifications for titles
// [2] - Verifications

function emaildb_init() {
	global $EMAIL_DB;
	
	$EMAIL_DB = array(
		array(),
		array(),
		array()
	);
}

// Main email database loader
function emaildb_load() {
	global $EMAIL_DB;
	
	// Empty the database in memory
	$EMAIL_DB = array();
	
	// Read the raw file data, decrypt it and explode it into lines
	$data = file_read(FILE_EMAIL, 'Could not load the email database!');
	cryptare($data, AUTH_EMAILDBKEY, 'tripledes', FALSE);
	$data = unserialize($data);
	
	// Decrypt + unserialize each line into the database in memory
	foreach ($data as $line)
		array_push($EMAIL_DB, emaildb_array_read($line));
}

// Finalizes, encrypts and saves the email database
function emaildb_save() {
	global $EMAIL_DB;
	$data = array();
	
	// Serialize + encrypt each line into memory
	foreach ($EMAIL_DB as $line)
		array_push($data, emaildb_array_write($line));

	// Serialize final array of encrypted arrays and dump to file.
	$data = serialize($data);
	cryptare($data, AUTH_EMAILDBKEY, 'tripledes');
	file_write(FILE_EMAIL, $data, 'Could not write the email database!');
}

// =================
// RAW DATA HANDLING
// =================

// Decrypts and unserializes an array
function emaildb_array_read($data) {
	cryptare($data, AUTH_EMAILKEY, 'twofish', FALSE);
	
	$array = unserialize($data);
	return $array;
}

// Serializes and encrypts an array
function emaildb_array_write($array) {
	$data = serialize($array);
	cryptare($data, AUTH_EMAILKEY, 'twofish');
	
	return $data;
}

// =============
// MAIL HANDLING
// =============

function email_send($destination,$subject,$data,$deverify = FALSE, $type = NOTIFICATION_TITLE, $id = 0) {
	$message_deverify = '<p>To stop recieving this specific notification, <a href="'
	.URL_EMAIL
	.URL_EMAIL_ADDRESS.$destination
	.URL_EMAIL_DENOTIFY.$id
	.URL_EMAIL_DENOTIFYTYPE.$type
	.'">click here</a>. To deverify your email and recieve no notifications at all, <a href="'
	.URL_EMAIL
	.URL_EMAIL_ADDRESS.$destination
	.URL_EMAIL_DEVERIFY.md5($destination)
	.'">click here</a></p>';
	
	$message_footer = N.'<p style="color: #aaa">This is an automated message from a bot-only mailbox; replies to this address will not be recieved.</p>';

	$message = '<body>'.
	$data.($deverify ? $message_deverify : '').$message_footer.'
	</body>';
	
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$headers .= 'From: Minecraft Mod Index <' . EMAIL_NOTIFICATIONS . '>' . "\r\n";

	// Mail it
	mail($destination, $subject, $message, $headers);
}

// ==========
// ENCRYPTION
// ==========

// Cryptare! Oh god. (thanks http://www.php.net/manual/en/function.mdecrypt-generic.php#88812)
function cryptare(&$data, $key, $alg, $crypt = TRUE) {

    switch($alg) {
        case '3des':
            $td = mcrypt_module_open('tripledes', '', 'ecb', '');
            break;     
        case 'twofish':
            $td = mcrypt_module_open('twofish', '', 'ecb', '');
            break;   
        case 'rijndael-256':
            $td = mcrypt_module_open('rijndael-256', '', 'ecb', '');
            break;  
        default:
            $td = mcrypt_module_open('blowfish', '', 'ecb', '');
            break;                                           
    }
   
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    $key = substr($key, 0, mcrypt_enc_get_key_size($td));
    mcrypt_generic_init($td, $key, $iv);
   
	$data = $crypt ? mcrypt_generic($td, $data) : mdecrypt_generic($td, $data);
   
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
} 

?>