<?php

/* 	============================================
	THE MINECRAFT MOD INDEX
	TWEETING SYSTEM

	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

require_once 'common.php';
require_once dirname(__FILE__).'/external/twitter.class.php';

php_includeonly(__FILE__);

$TWITTER = new Twitter(LOGIN_TWITTER_CKEY, LOGIN_TWITTER_CSECRET, LOGIN_TWITTER_TOKEN, LOGIN_TWITTER_TSECRET);
$TWEETS = 0;

function tweet($status) {
	global $TWITTER, $TWEETS;
	
	if (TESTING)
		return 'Twitter is disabled whilst on the development platform.';
	
	if ($TWEETS >= 10)
		return 'Over per-execution tweet limit of 10!';
	
	for ($i = 0; $i < 5; $i++) {
		$e = false;
		
		try {
			$tweet = $TWITTER->send($status);
		} catch(TwitterException $e) {
			$error = $e->getMessage();
		}
	
		if(!$e)
			break;
	}
	
	if ($e) {
		return $error;
	} else {
		$TWEETS++;
		return true;
	}
}

function tweet_announce($row, $id) {
	// Twitter-friendly title
	$title = preg_replace('@(\([^\(]+\)|\[[^\[]+\])@i','',$row['title']);
	$title = trim($title);
	
	// Keeps status short
	$title = (strlen($title) > 60) ? (substr($title, 0, 60) . '...') : $title;
	$status = '"'.$title.'" by '.$row['author'].' has been updated to '.$row['version'].'! '.URL_TOPIC.$id;
	//$status = 'Yarr! '.$row['author'].'\'s "'.$title.'" be updated t\''.$row['version'].'! '.URL_TOPIC.$id;
	
	return tweet($status);
}
?>