<?php
/* 	============================================
	THE MINECRAFT MOD INDEX
	USER INTERFACE
	Version: v1.3
	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

header("Content-Type: text/html; charset=utf-8");
require_once('lib/index.func.php');
?>
<!DOCTYPE html>
<html>
<head>
	<meta name="author" content="The Major / Crome Tysnomi / Ayman Habayeb" />
	<meta name="description" content="A periodically updated index of mods available on the official Minecraft forums." />
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	<?php echo $_GET ? '<meta name="robots" content="noindex, follow" />'.N : ''; ?>
	
	<link rel="shortcut icon" href="favicon.png" />
	<link rel="canonical" href="http://mods.simplaza.net" />
	<link href="style/index.css" rel="stylesheet" type="text/css">
	<link href="style/skin.<?php echo pick($SETTINGS['style']['skin'], 'monolith');?>.css" rel="stylesheet" type="text/css">
	
	<script type="text/javascript" src="<?php echo 'http://'.(TESTING ? 'localhost' : 'simplaza.net').'/common/'; ?>js"></script>
	<script type="text/javascript" src="lib/index.js"></script>
	<script type="text/javascript" src="lib/index.keyboard.js"></script>
	
	<title>Minecraft Mod Index v<?php echo VERSION; ?></title>
</head>

<body onload="init();">	
<div id='root' class="root <?php echo ($SETTINGS['style']['hide_desc'] && $SETTINGS['style']['hide_flags']) ? 'listing_mini' : false; ?>">
	<?php ui_dialog(); ?>
	
	<div class="windowroot">
		<div class="window hidden" id="window_settings">
			<div class="toolbar toolbar_window">
				<a id="button_save" onclick="settingsSave(); return false;" class="button hidden">Save</a>
				<a onclick="settingsHide(); return false;" class="button">Cancel</a>
				<a onclick="settingsConfirm(this,settingsDefaults); return false;" class="button">Defaults</a>
			</div> <h2>Settings</h2>
			
			<h3>Style</h3>
			<div class="canvas">
				<div class="option ctrl_dropdown">
					<select id="settings.style.skin" class="right">
					</select>
					<h4>Skin</h4>
				</div>
				<div class="option ctrl_checkbox"><input id="settings.style.hide_desc" class="right" type="checkbox"/><h4>Hide topic description</h4></div>
				<div class="option ctrl_checkbox"><input id="settings.style.hide_time_created" class="right" type="checkbox"/><h4>Hide topic creation time</h4></div>
				<div class="option ctrl_checkbox"><input id="settings.style.hide_views" class="right" type="checkbox"/><h4>Hide topic views</h4></div>
				<div class="option ctrl_checkbox"><input id="settings.style.hide_author" class="right" type="checkbox"/><h4>Hide topic author</h4></div>
				<div class="option ctrl_checkbox"><input id="settings.style.hide_flags" class="right" type="checkbox"/><h4>Hide topic flags</h4></div>
				<div class="option ctrl_checkbox"><input id="settings.style.disable_motw" class="right" type="checkbox"/><h4>Disable Mod of the Week</h4></div>
			</div>
			
			<h3>Cookies</h3>
			<div class="canvas">
				<div class="option ctrl_button"><h4><a onclick="delClass(windowImport, 'hidden'); return false;">Import</a></h4>
					<p>Imports your Mod Index settings, including favorites</p>
				</div>
				
				<div class="option ctrl_button"><h4><a href="?export" rel="nofollow" target="_blank">Export</a></h4>
					<p>Exports your Mod Index settings and favorites into a downloadable file</p>
				</div>
				
				<div class="option ctrl_button"><h4><a onclick="settingsConfirm(this,cookieNukeAll); return false;">Erase</a></h4>
					<p>Completely erases your settings and favorites</p>
				</div>
				
				<div class="option ctrl_button"><h4><?php echo $STATS['COOKIE_COUNT'].($STATS['COOKIE_COUNT'] == 1 ? ' cookie' : ' cookies' ); ?> stored</h4>
					<p>Approx. <?php echo $STATS['COOKIE_LENGTH']; ?> bytes used</p>
				</div>
			</div>
			
			<h3>Statistics</h3>
			<div class="canvas">
				<div class="metadata right half">
					<h4>System:
						<span class="toolbar">
							<a href="lib/credits.html" target="_blank" class="button" title="Credits to the creators and contributors of the Mod Index">Credits</a>
							<a href="http://twitter.com/Gnu32" target="_blank" class="button" title="Official Twitter feed for Mod Index software updates">Updates</a>
						</span>
					</h4>
					<h6>Peak Memory:</h6> <p><?php echo $STATS['SYSTEM_PEAKMEM']; ?> KB</p>
					<h6>Environment:</h6> <p><?php echo $STATS['SYSTEM_ENV']; ?></p>
					<h6>Host:</h6> <p><?php echo $_SERVER['HTTP_HOST']; ?></p>
				</div>
				
				<div class="metadata">
					<h4>Index:
						<span class="toolbar">
							<a href="?random" target="_blank" class="button" title="Picks a random, up-to-date mod">Random</a>
							<a href="?raw" class="button" title="Hot-linkable raw comma seperated format of the index data">Raw CSV</a>
							<a href="<?php echo FILE_INDEX?>" class="button" title="Raw PHP serialized array file of index data">File</a>
						</span>
					</h4>
					<h6>Entries:</h6> <p><?php echo $STATS['INDEX_ENTRIES']; ?></p>
					<h6>Size:</h6> <p><?php echo $STATS['INDEX_SIZE']; ?> KB</p>
					<h6>Last Update:</h6> <p><?php echo $STATS['INDEX_LASTUPDATE']?></p>
					<h6>Current Page:</h6> <p><?php echo $STATS['INDEX_PAGE']?></p>

				</div>
			</div>
			
			<footer>
				<p>
				The Minecraft Mod Index<br />
				Product of the United Kingdom
				</p>
			</footer>
		</div>
	</div>
	
	<div class="windowroot">
		<div class="window hidden" id="window_import">
		
			<div class="toolbar toolbar_window">
				<a onclick="addClass(windowImport, 'hidden'); return false;" class="button">Cancel</a>
			</div> <h2>Import Settings</h2>
			
			<div class="canvas clear center" style="padding: 128px 0;">
				<h4>Paste the content of your exported 'modindex.txt' into the line below.<br />
				Invalid content may reset your settings to the default.</h4>
				
				<form id="form_import" onsubmit="settingsImport(this); return false;">
					<input name="importedsettings" type="text" style="width: 100%;" placeholder="Paste your settings here" /><br />
					<input type="submit" value="Import" />
				</form>
			</div>
			
		</div>
	</div>
	
	<div class="windowroot">
		<div class="window hidden" id="window_editor">
		
			<div class="toolbar toolbar_window">
				<a id="button_editor_submit" onclick="editorSubmit(); return false;" class="button hidden">Submit</a>
				<a onclick="addClass(windowEditor, 'hidden'); return false;" class="button">Cancel</a>
			</div> <h2>Row Editor</h2>
			
			<form id="form_editor" method="post">
			<input name="form_editor_id" type="hidden"/>
			
			<div class="canvas">
				<p>Welcome to the mod editor! This allows you to submit custom metadata to mods, whether they're your own or somebody elses. Please read each option carefully and fill in the data as best you can. When you're done, the submitted data will be sent to an Indexmaster for approval, which may take up to 24 hours.</p>
			</div>
			
			<h3>Mod Details</h3>
			<div class="canvas">
				<div class="mod mU">
					<div class="meta"> 
						<b><a id="mod_editor_author" href="#" target="_blank"></a></b>, <b id="mod_editor_views"></b> views
					</div>
					
					<a id="mod_editor_link" href="#" target="_blank" class="link_mod">
					</a>
				</div>
			</div>
			
			<h3>Flags</h3>
			<div class="canvas canvas_flags">
				<p>These flags help users filter down results and find the types of mods they're looking for. Examine each flag carefully and check the correct ones that apply to the mod being edited.</p>
				
				<div class="option ctrl_checkbox">
					<input name="checkbox_editor_flag_collection" class="right" type="checkbox"/>
					<h4>Collection</h4>
					<p>This topic is actually a collection of various different mods with different versions. Examples include "[Name]'s mods". Note that mods that combine many functions in one (e.g. Zombe's mods) does not actually count as a collection.</p>
				</div>
				
				<div class="option ctrl_checkbox">
					<input name="checkbox_editor_flag_adfly" class="right" type="checkbox"/>
					<h4>Adf.ly</h4>
					<p>This topic <b>forces</b> users to click adf.ly links to download the mod(s), without any obviously indicated direct download links. <b>If a mod uses adf.ly links but offers clearly indicated direct download links, do not apply this flag.</b></p>
				</div>
				
				<div class="option ctrl_checkbox">
					<input name="checkbox_editor_flag_modloader" class="right" type="checkbox"/>
					<h4>Modloader</h4>
					<p>This topic contains one or more mods that <b>requires</b> Risugami's Modloader in order to function.</p>
				</div>
				
				<div class="option ctrl_checkbox">
					<input name="checkbox_editor_flag_depends" class="right" type="checkbox"/>
					<h4>Dependency</h4>
					<p>This topic contains a mod that other mods <b>depend on</b>. Examples include APIs and libraries, such as GUIAPI and Modloader.</p>
				</div>
				
				<div class="option ctrl_checkbox">
					<input name="checkbox_editor_flag_smp" class="right" type="checkbox"/>
					<h4>Multiplayer</h4>
					<p>This topic contains one or more mods that support multiplayer. Only apply this flag if you are <b>absolutely confident</b> this mod is <b>purely client-side</b> (e.g. graphical enhancements, minimaps) or it <b>clearly indicates it has multiplayer support.</b></p>
				</div>
			</div>
			
			<h3>Keywords</h3>
			<div class="canvas">
				<p>These keywords are extremely helpful for users searching for mods, especially if a topic contains more than one mod or uses a title and description that doesn't really describe the mod. <b>Keywords must be listed with spaces in between, not with commas or any other seperators.</b></p>
				<p>For the most effective keywords, use plural versions of words (e.g. <b>cows</b> instead of <b>cow</b>) and don't repeat words from the title or description.</p>
				
				<div class="option">
					<textarea class="full" name="textbox_editor_keywords">???</textarea>
				</div>
			</div>
			
			</form>
		</div>
	</div>
	
	<div class="windowroot">
		<div class="window hidden" id="window_share">
		
			<div class="toolbar toolbar_window">
				<a onclick="addClass(windowShare, 'hidden'); return false;" class="button">Cancel</a>
			</div> <h2>Share</h2>
			
			<div class="canvas">
				<h3>HTML</h3>
				
				<textarea id="codebox_share_html" class="codebox">Please wait...</textarea>
			</div>
			
			<div class="canvas">
				<h3>BBCode</h3>
				
				<textarea id="codebox_share_bbcode" class="codebox">Please wait...</textarea>
			</div>
			
			<div class="canvas">
				<h3>Reddit</h3>
				
				<textarea id="codebox_share_reddit" class="codebox">Please wait...</textarea>
			</div>
			
		</div>
	</div>
	
	<header>
		<span class="toolbar right"><a onclick="settingsShow(); return false;">Settings</a></span>
		<h1><a href="./">Minecraft Mod Index</a></h1>
		<h6>A periodically updated index of mods avaliable on the <a href="http://www.minecraftforum.net/forum/51-released-mods/">Minecraft forums</a></h6>
	</header>
	
	<form id="searchform" accept-charset="UTF-8">
		<input id="field_search" name="mmiquery" placeholder="Search Index" value="<?php echo $USER['SEARCHQUERY']; ?>" />
		<?php
			ui_fieldpassthrough('sort', $_GET['sort']);
			ui_fieldpassthrough('reverse', isset($_GET['reverse']));
			ui_fieldpassthrough('limit', $_GET['limit']);
		?>
		<input type="submit" value="GO" />
	</form>
	
	<div class="tooltip hidden" id="tooltip_field_search">
		<h4>How to use filters:</h4>
		<p>Simply use them alongside your search terms. Examples:</p>
		<div class='center'><em><a href="?q=airship+!updated">airship !updated</a></em></div>
		<div class='center'><em><a href="?q=!favorites+planes">!favorites planes</a></em></div>
		
		<hr />
		<h4>Filters avaliable:</h4>
		<ul>
			<li><b>~adfly</b> - Hide Adf.ly-forced mods</li>
			<li><b>!updated</b> - Show only updated mods</li>
			<li><b>!favorites</b> - Show only favorited mods</li>
			<li><b>.regex</b> - Parse search query as <a href="http://docs.google.com/viewer?url=http%3A%2F%2Fcdn.cloudfiles.mosso.com%2Fc8031%2FPHP_PCRE_Cheat_Sheet.pdf">PCRE Regex</a></li>
		</ul>
		<hr />
		<h4>Accented and Unicode characters may break search.</h4>
	</div>
	
	<div class="toolbar" id="toolbar_sort">
	Sort by: <?php ui_list_sorts(); ?>
	</div>
	
<?php
	// Flush the buffer, which should have a listing
	buffer_flush();
?>
	
</div>
	<!-- darioissocoollike -->
</body>
</html>