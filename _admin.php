<?php
/* 	============================================
	THE MINECRAFT MOD INDEX
	ADMIN PANEL
	Version: v1.3
	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

require_once('lib/_admin.func.php');
?>

<!DOCTYPE html>
<html>
<head>
	<meta name="author" content="The Major / Crome Tysnomi / Ayman Habayeb" />
	<meta name="description" content="A periodically updated index of mods available on the official Minecraft forums." />
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	
	<link href="style/index.css" rel="stylesheet" type="text/css">
	<link href="style/skin.monolith.css" rel="stylesheet" type="text/css">
	<style type="text/css">
	
	
	.heading {
		margin-bottom: 5px;
		
	}
	
	input[type=checkbox] {
		width: 20px;
		height: 10px;
		border: solid 1px #f00;
	}
	
	li {
		font-size: 14px;
	}
	
	#frame_roweditor {
		position: fixed;
		width: 100%;
		left: 0px;
		top: 350px;
		height: 100%;
		border: none;
	}
	</style>
		
	<title>Mod Index Admin Panel</title>
</head>

<body>
<div class="<?php echo ($MODE == MODE_ROWEDITOR) ? '' : 'root' ?> ">
	<?php
	if ($MODE != MODE_ROWEDITOR) {
	?>
	<header>
		<h1>Admin Panel</h1>
		<h6>The toolbox that maintain the gears of <a href="./">Minecraft Mod Index</a></h6>
	</header>
	
	<div class="toolbar" id="toolbar_sort">
	Go to:
	</div>
	
	<div class="heading">Core</div>
	
	<iframe name="frame_core" class="right" style="background-color: #fff; width: 75%; margin-bottom: 5px;"></iframe>
	
	<a href="./_scraper.php?admin=<?php echo $_GET['admin']?>" target="frame_core"><h2>1. Scrape</h2></a>
	<a href="./_indexer.php?admin=<?php echo $_GET['admin']?>" target="frame_core"><h2>2. Index</h2></a>
	<a href="./_janitor.php?admin=<?php echo $_GET['admin']?>" target="frame_core"><h2>3. Janitor</h2></a>
	
	<?php
	// _fixes.php is supposed to be a temporary file.
	if ( is_file('_fixes.php') ) {
	?>
		<a href="./_fixes.php?admin=<?php echo $_GET['admin']?>" target="frame_core"><h2 style='color: #f00'>4. Fixes</h2></a>
		<a href="<?php echo URL_ADMIN.'&core_popfixes' ?>" target="frame_core">(delete _fixes.php)</a>
		
	<?php
	}
	?>
	
	<!--
		==================
		STATISTICS SECTION
		==================
	-->
	
	<div class="heading clear">Statistics</div>
	<ul>
		<li><h6>Last update:		<?php echo date('jS F Y h:i:s A', filemtime(FILE_INDEX) ).' ('.time_ago().')';	?></h6></li>
		<li><h6>Current page:		<?php echo file_get_contents(FILE_JOB);	?></h6></li>
	</ul>
	
	<?php
	}
	?>

	<!--
		=============
		INDEX SECTION
		=============
	-->
	<div class="heading clear">Index editor</div>
	
	<form id="form_rowpicker" method="POST" action="<?php echo URL_ADMIN ?>" class="center <?php echo ($MODE == MODE_ROWEDITOR) ? 'hidden' : '' ?>" accept-charset="UTF-8">
		<input name="rowpicker_query" type="text" /><br />
		<input type="submit" value="Go"  />
		<input name="rowpicker_random" type="submit" value="Random" />
	</form>
	
	<form id="form_roweditor" method="POST" action="<?php echo URL_ADMIN ?>&row_save=<?php echo $PICKEDROW ?>" accept-charset="UTF-8">
		<input type="hidden" name="roweditor_transmit" value="true" />
		<input type="hidden" name="roweditor_editoronly" value="<?php echo ($MODE == MODE_ROWEDITOR) ? 'true' : '' ?>" />
		<?php 
		if ($INDEX_SAVED)
			echo '<h3 class="center" style="color: #0a0">Row ID '.$INDEX_SAVED.' has been saved @ '.time().'.</h3>';
		
		listing_index();
		?>
		
		<div class="center">
			<input type="text" style="width: 100%" name="row_title" value="<?php echo $INDEX[$PICKEDROW]['title']; ?>" /><br/>
			<input type="text" style="width: 100%" name="row_desc" value="<?php echo $INDEX[$PICKEDROW]['desc']; ?>" /><br/>
			<b>Version:</b> <input type="text" name="row_version" value="<?php echo $INDEX[$PICKEDROW]['version']; ?>" />
			<b>Author:</b> <input type="text" name="row_author" value="<?php echo $INDEX[$PICKEDROW]['author']; ?>" />
			<b>Author URL:</b> <input type="text" name="row_author_id" value="<?php echo $INDEX[$PICKEDROW]['author_id']; ?>" />
		</div>
		
		<hr />
		
		<div class="half right">
		<h3>Keywords:</h3>
		<textarea style="width: 100%; height: 70px; font-size: 12px; font-weight: bold;" name="metadata_keywords"><?php echo $SUBINDEXES['metadata'][$PICKEDROW]['keywords'] ?></textarea>
		</div>
		
		<ul>
			<li><input type="checkbox" name="flag_collection" <?php checked('flag_collection'); ?> /> <b>Collection</b> - Topics containing more than one mod</li>
			<li><input type="checkbox" name="flag_adfly" <?php checked('flag_adfly'); ?> /> <b>Adf.ly</b> - Topics that ONLY contain Adf.ly download links</li>
			<li><input type="checkbox" name="flag_modloader" <?php checked('flag_modloader'); ?> /> <b>Modloader</b> - Mods that require Modloader</li>
			<li><input type="checkbox" name="flag_depends" <?php checked('flag_depends'); ?> /> <b>Dependency</b> - Mods that are a dependency for others (Modloader, libraries, etc)</li>
			<li><input type="checkbox" name="flag_smp" <?php checked('flag_smp'); ?> /> <b>SMP</b> - Mods that support Survival Multi-Player</li>
		</ul>
		
		<input type="submit" class="right" value="Save Changes"  />
		<span class='right' style='padding-top: 4px;'>
		<b>Random after save</b> <input type="checkbox" name="roweditor_random" <?php echo $_POST['roweditor_random'] ? 'checked' : ''; ?> />
		</span>
		
		<?php
		if ($MODE == MODE_ROWEDITOR)
			echo '<a href="'.URL_TOPIC.$PICKEDROW.'"><b>Close editor</b></a>';
		?>
	</form>
	
	<?php 
		if ($MODE == MODE_ROWEDITOR) {
			echo '<iframe id="frame_roweditor" src="'.URL_TOPIC.$PICKEDROW.'" />';
		} else {	
	?>
	
	<!--
		============
		BLACKLIST SECTION
		============
	-->
	
	<div class="heading clear">Moderation</div>
	
	<div class="center">
		<form id="form_blacklist" method="POST" action="<?php echo URL_ADMIN ?>" accept-charset="UTF-8">
			<input name="blacklist_add" /><br />
			<b>Don't delete, just ignore</b> <input type="checkbox" name="blacklist_ignore" />
			<input type="submit" value="Add to Blacklist / Delete from Index"/>
		</form>
	</div>

	<?php listing_blacklist(); ?>
	
	<!--
		===============
		TWITTER SECTION
		===============
	-->
	
	<div class="heading clear">Twitter</div>
	
	<div class="center">
		<form id="form_motw" method="POST" action="<?php echo URL_ADMIN ?>" accept-charset="UTF-8">
			<input name="twitter_post" style="width: 100%" /><br />
			<input type="submit" value="Tweet"/>
		</form>
	</div>
	
	<?php
	if ($TWEET_SUCCESS === true)
		echo '<h1 class="center" style="color:red">Success! Tweet was posted.</h1>';
	else if ( is_string($TWEET_SUCCESS) )
		echo '<h1 class="center" style="color:red">Failure! '.$TWEET_SUCCESS.'</h1>';
	?>
		
	<!--
		============
		MOTW SECTION
		============
	-->
	
	<div class="heading clear">MOTW</div>
	
	<div class="center">
		<form id="form_motw" method="POST" action="<?php echo URL_ADMIN ?>" accept-charset="UTF-8">
			<input name="motw_id" /><br />
			<input type="submit" value="Add to Queue"/>
			<input name="motw_reset" type="submit" value="Flush" />
		</form>
	</div>

	<?php listing_motw(); ?>

	<!--
		=============
		EMAIL SECTION
		=============
	-->
	
	<div class="heading clear">Email</div>
	
	<div style="background-color: #500; text-shadow: 1px 1px 0px #000; font-weight: bold; font-size: 14px; padding: 20px;" class="warning center">
		<em>As administrator, you are responsible for keeping the email data below safe from misuse, whether accidental or malicious.<br />
		Never indulge yourself in power; it will always come back to bite you in the ass.</em><br />
		<br />
		Misuse of this data, including leaking/sharing it, is illegal in the UK under the <a href="http://www.legislation.gov.uk/ukpga/1998/29/contents" target="_blank">Data Protection Act of 1998</a>
	</div>

	<div class="center">
		<form id="form_emailsend" method="POST" action="<?php echo URL_ADMIN ?>" accept-charset="UTF-8">
			<input name="email_oldid" /> <input name="email_newid" /> <br />
			<b>Type:</b> <input type="checkbox" name="email_type" />
			<input type="submit" value="Send test notification"/>
		</form>
	</div>
	
	<?php listing_email(); ?>

	<div style="width: 100%; height: 256px; overflow: auto; color: #0f0;">
		<pre><?php 	print_r($EMAIL_DB); ?></pre>
	</div>
	
	<?php	
	}
	?>
</div>
</body>