/* 	============================================
	THE MINECRAFT MOD INDEX
	USER INTERFACE LOCAL FUNCTIONALITY

	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

// ================
// GLOBAL VARIABLES
// ================

// ===== Settings
var settings;
var settingsElements;

// ===== Favorites
var favorites;

// ===== Email Handling
var timerEmail;
var emailId;
var emailType;
var emailAddress;
var emailPrevious;

// ===== Elements
var root;
var windowSettings;
var windowImport;
var windowShare;
var windowEditor;

var formEditor;
var buttonSave;
var buttonEditorSubmit;
var fieldSearch;

var codeboxShareHTML;
var codeboxShareBBCode;
var codeboxShareReddit;

var textboxEditorKeywords;
var checkboxesEditorFlags;

var modEditorAuthor;	
var modEditorViews;	
var modEditorLink;

// ===== Constants
var REGEX_EMAIL = new RegExp("^[A-Z0-9._%+-]+@[A-Z0-9-.]+\.[A-Z]{2,5}$","i");

var URL_REPORT = 'http://www.minecraftforum.net/index.php?app=core&module=reports&rcom=post&tid=';
var URL_TOPIC = 'http://minecraftforum.net/index.php?showtopic=';
var URL_USER = 'http://minecraftforum.net/user/';

var CODE_HTML = 0;
var CODE_BBCODE = 1;
var CODE_REDDIT = 2;

var ENUM_FLAGS = {
	'collection': 'Collection',
	'adfly' : 'Adf.ly',
	'modloader' : 'Modloader',
	'depends' : 'Depends',
	'smp' : 'Multiplayer'};
	
var ENUM_SKINS = {
	'monolith' : 'Monolith',
	'creamer' : '"Decorator Neutral"',
	'print' : 'Print',
	'forums' : 'Minecraft Forums'
};

// =====================
// INIT & MAIN FUNCTIONS
// =====================

function init() {
	favorites = getFavorites();

	elementInit();
	settingsInit();
	uiInit();
	keyboardInit();
	
	//editorShow();
	//settingsShow();
	
	// The reason I'm doing is this is because it'd be laggier to append a click event to each button element.
	window.onclick = handleToolbar;
	fieldSearch.onfocus = uiCreateTooltip;
	fieldSearch.onblur = function() { setTimeout("addClass(fieldSearch.tooltip, 'hidden');", 200); };
}

	// Registers elements to javascript globals
	function elementInit() {
		root 					= $('root');
		windowSettings 			= $('window_settings');
		windowImport 			= $('window_import');
		windowShare             = $('window_share');
		windowEditor            = $('window_editor');
		
		formEditor 				= $('form_editor');
		buttonSave 				= $('button_save');
		buttonEditorSubmit		= $('button_editor_submit');
		fieldSearch				= $('field_search');
		fieldSearch.tooltip		= $('tooltip_field_search');
		
		codeboxShareHTML        = $('codebox_share_html');
		codeboxShareBBCode      = $('codebox_share_bbcode');
		codeboxShareReddit      = $('codebox_share_reddit');
	}

function indexReload() {
	document.title = "Reloading...";
	addClass(document.body.firstElementChild, 'reloading');
	
	if (window.location.search)
		window.location.search = '';
	else
		window.location.reload();	
}

// ================
// TOOLBAR HANDLERS
// ================

// This checks if the click has gone to a toolbar extender
function handleToolbar(e) {

	// Was an extender clicked?
	if (e.target.className == "button_extend") {
		
		row = e.target.parentNode;
		
		// Is the row already extended?
		if ( row.extended ) {
			// Change button's symbol back to right arrows
			e.target.innerHTML = "&raquo;";
			row.extended = false;
			
			// Destroy the toolbar and return row back to normal
			delClass(row, "extended");
			destroyToolbar(row);
		} else {
			// Change button's symbol to left arrows
			e.target.innerHTML = "&laquo;";
			row.extended = true;
			
			// Create toolbar and linkify all the buttons
			row.toolbar = createToolbar(row);
			row.toolbar.rowid = row.id;
			row.toolbar.children[0].onmousedown = handleEmailClick;
			row.toolbar.children[1].onmousedown = handleEmailClick;
			row.toolbar.children[2].onclick = setFavorite;
			row.toolbar.children[3].onclick = editorShow;
			row.toolbar.children[3].href    = '#';
			row.toolbar.children[4].href    = '#';
			row.toolbar.children[5].href = URL_REPORT + row.id;
		}
		
	}
}

function createToolbar(row) {
	var favTitle = (hasClass(row, 'mF') ? 'Remove from ' : 'Add to ') + 'my favorites!';
	row.innerHTML =
	"<div class='tools toolbar'>\
		<a class='button' data-type='version' title='Mail me on version update!'>&#9993;</a>\
		<a class='button' data-type='title' title='Mail me on title update!'>&#9993;+</a>\
		<a class='button button_fav' title='"+favTitle+"'>&hearts;</a>\
		<a class='button' title='Edit this mod!'>&#9998;</a>\
		<a class='button' title='Share this with the world!' onclick='shareRow("+row.id+");'>&#9788;</a>\
		<a class='button' title='Report this as spam!' target='_blank'>&#10008;</a>\
	</div>" + row.innerHTML;
	
	return row.firstChild;
}

function destroyToolbar(row) {
	row.removeChild(row.toolbar);
	row.toolbar.children[2].onClick = null;
	
	delete row.toolbar;
}

// =================
// SETTINGS HANDLING
// =================

function settingsInit() {
	var settingsCookie = cookieRead('settings');
	
	// If the cookie exists, read it into settings, otherwise load the defaults
	try {
		settings = JSON.parse( decodeURIComponent(settingsCookie) );
		
		if ( settingsUpdate() )
			settingsSave();
			
	} catch (err) {
		alert('Your saved settings are corrupt, so the defaults have been loaded instead.\nIf you have saved your settings, try to import them.\nError: '+err);
		cookieDelete('settings');
		indexReload();
	}
	
	settingsElements = {};		
}

// Updates settings from older versions
function settingsUpdate() {
	// 0.8 >> 0.9
	if ( typeof(settings.motw) != 'undefined' ) {
		settings.style.disable_motw = settings.motw;
		delete settings.motw;
		return true;
	}
	
	// 1.1 >> 1.2
	if ( typeof(settings.style.hide_time_created) == 'undefined' ) {
		settings.style.hide_time_created = true;
		return true;
	}
	
	return false;
}

function settingsShow() {
	delClass(windowSettings, 'hidden');
}

function settingsHide() {
	addClass(windowSettings, 'hidden');
}

function settingsConfirm(button, func) {
	if (button.confirmHide) {
		delClass(button, 'button_confirm');
		button.innerHTML = button.prevLabel;
		button.confirmHide = false;
		func();
	} else {
		addClass(button, 'button_confirm');
		button.prevLabel = button.innerHTML;
		button.innerHTML = 'Are you sure?';
		button.confirmHide = true;
	}
}

function settingsSave() {
	cookieWrite( 'settings', encodeURIComponent( JSON.stringify(settings) ) );
	indexReload();
}

function settingsDefaults() {
	cookieDelete('settings');
	indexReload();
}

function settingsImport(form) {
	var importData = form.firstElementChild.value;
	var importButton = form.lastElementChild;
	
	importButton.disabled = true;
	
	try {
		importData = JSON.parse(importData);
	} catch (err) {
		alert('Invalid settings data! Make sure you are pasting the whole line.\n' + err);
		importButton.disabled = false;
		return false;
	}
	
	if (importData.favorites)
		cookieWrite('favorites',importData.favorites);
	
	if (importData.email)
		cookieWrite('email',importData.email);
	
	if (importData.settings)
		cookieWrite('settings',importData.settings);
		
	window.location.search = '?importsuccess';
}

// ===========
// UI HANDLERS
// ===========

function uiInit() {
	uiRegisterSetting('settings.style.hide_author');
	uiRegisterSetting('settings.style.hide_desc');
	uiRegisterSetting('settings.style.hide_time_created');
	uiRegisterSetting('settings.style.hide_views');
	uiRegisterSetting('settings.style.hide_flags');
	uiRegisterSetting('settings.style.disable_motw');
	uiRegisterSetting('settings.style.skin');
	
	uiPopulateDropdown(settingsElements['settings.style.skin'], ENUM_SKINS, settings.style.skin);
	
	uiRegisterCodeboxes();
	uiRegisterCheckboxes();
}

function uiRegisterSetting(id) {
	settingsElements[id] = $(id);
	
	if (settingsElements[id].type == 'checkbox')
		settingsElements[id].checked = eval(id);
	
	settingsElements[id].onchange = uiChangedSetting;
}

function uiPopulateDropdown(ele, options, selected) {
	var count = 0;
	
	options.walk( function(theme, key, args) {
		var oele = document.createElement('option');
		oele.text = theme;
		oele.value = key;
		ele.add(oele, null);
		
		if (selected == key)
			ele.selectedIndex = count;
			
		count++;
	} );
	
}

function uiRegisterCodeboxes() {
	var codeboxes = document.getElementsByClassName('codebox');
	
	codeboxes.walk( function (codebox, key, args) {
		codebox.readOnly = true;
		codebox.onmouseover = function() {
			this.focus();
			this.select();
		}
		
		codebox.onmouseout = function() {
			this.blur();
		}
	} );
	
}

function uiRegisterCheckboxes() {
	var checkboxes = document.getElementsByClassName('ctrl_checkbox');
	
	checkboxes.walk (function (checkbox, key, args) {
		checkbox.checkbox = checkbox.firstElementChild;
		checkbox.onclick = function(e) {
			if (e.target != this.checkbox) {
				uiToggleCheckbox(this.checkbox);
				
				if ( this.checkbox.form )
					this.checkbox.form.onchange();
				
				if ( typeof(this.checkbox.onchange) == 'function' ) {
					var e = {'target' : this.checkbox};
					this.checkbox.onchange(e);
				}
			}
		}
	} );
}

function uiToggleCheckbox(box) {
	if (box.checked)
		box.checked = false;
	else
		box.checked = true;
}

// ===== Settings

function uiChangedSetting(e) {
	var ele = e.target;
	
	if (ele.type == 'checkbox')
		eval(ele.id + ' = ' + ele.checked);
	else if (ele.nodeName == 'SELECT')
		eval(ele.id + ' = "' + ele.options[ele.selectedIndex].value + '"');
	
	delClass(buttonSave, 'hidden');
	addClass(buttonSave, 'button_save');
}

// ===== Tooltips

function uiCreateTooltip(e) {
	delClass(this.tooltip, 'hidden');
	this.tooltip.style.left = (root.offsetLeft + root.offsetWidth - this.tooltip.offsetWidth) + 'px';
	this.tooltip.style.top = (this.offsetTop + this.offsetHeight) + 'px';
	//this.tooltip.style.width = (this.offsetWidth - 20) + 'px';
}

// ==============
// EMAIL HANDLING
// ==============

function handleEmailClick(e) {
	emailAddress = cookieRead('email');
	emailId = this.parentNode.rowid;
	emailType = this.getAttribute('data-type');

	if (emailAddress) {
		// If we have an email that's been used before, handle a long click to reuse it.
		timerEmail = setTimeout(handleEmail, 1000);
		window.onmouseup = cancelEmailclick;
	} else {
		// Ask for email address
		promptEmail();
	}
}

function cancelEmailclick(e) {
	window.onmouseup = null;
	clearTimeout(timerEmail);
	promptEmail();
}

function promptEmail(fail) {
	var promptLabel =
		(fail ? '"' + emailAddress + '" isn\'t a valid email address!' + "\n" : '')
		+ 'What is your email address?' + "\n"
		+ (emailAddress && !fail ? 'To use your previous email address "' + emailAddress + '", you can simply hold down the notification button for a full second.' : '');
	emailAddress = prompt(promptLabel);
	
	if (emailAddress) {
		if (emailAddress.match(REGEX_EMAIL))
			handleEmail();
		else
			promptEmail(true);
	}
}

// Modified from http://stackoverflow.com/questions/133925/javascript-post-request-like-a-form-submit/133997#133997, thank you!
function handleEmail() {
	window.onmouseup = null;
	var form = document.createElement("form");
	var params;
	
	switch(emailType) {
		case 'version':
			emailType = '0';
			break;
		case 'title':
			emailType = '1';
			break;			
	}
	
	params = {'email' : emailAddress, 'id' : emailId, 'type' : emailType };
    form.setAttribute("method", "post");

    for(var key in params) {
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", key);
        hiddenField.setAttribute("value", params[key]);

        form.appendChild(hiddenField);
    }

	// Add email to cookies for easier notifications
	cookieWrite('email', emailAddress);
    document.body.appendChild(form);
    form.submit();
}

// ==================
// FAVORITES HANDLING
// ==================

function getFavorites() {
	var favcookie = cookieRead('favorites');
	
	return (favcookie != null) ? favcookie.split(',') : new Array();
}

function setFavorite(e) {
	var id = this.parentNode.rowid;
	var row = this.parentNode.parentNode;
	
	if (favorites.indexOf(id) != -1) {
		delClass(row,'mF');
		this.title = 'Add to my favorites!'
		favorites.pull(id);
		
	} else {
		addClass(row,'mF');
		this.title = 'Remove from my favorites!'
		favorites.push(id);
	}
	
	cookieWrite('favorites',favorites);	
}

// ===============
// EDITOR HANDLING
// ===============
function editorShow(e) {
	var id = e.target.parentNode.rowid;
	
	if ( typeof(checkboxesEditorFlags) == 'undefined' )
		editorInit();
	
	checkboxesEditorFlags.walk( function (e) {e.checked = false;} );
	
	editorUpdateMod(0,'Loading...','Matte kudasai...','???','???','#','???');	
	textboxEditorKeywords.innerHTML = ' ';
	
	ajax('?raw=json&id=' + id, editorCallback, id);
	
	addClass(buttonEditorSubmit, 'hidden');
	delClass(windowEditor, 'hidden');
}

function editorInit() {
	formEditor.onchange = function() {
		delClass(buttonEditorSubmit, 'hidden');
	}
	
	formEditor.rowID	    = document.getElementsByName('form_editor_id')[0];
	modEditorAuthor			= $('mod_editor_author');
	modEditorViews			= $('mod_editor_views');
	modEditorLink			= $('mod_editor_link');
	textboxEditorKeywords	= document.getElementsByName('textbox_editor_keywords')[0];
		
	checkboxesEditorFlags   = [];
	
	ENUM_FLAGS.walk( function (e,key) { checkboxesEditorFlags[key] = document.getElementsByName('checkbox_editor_flag_' + key)[0]; } );
		
}

function editorCallback(data, id) {
	try {
		var jsonResponse = JSON.parse(data);
	} catch(e) {
		editorUpdateMod(0,'AJAX error!','Please report this to the administrator or try again.',':(','???','#','???');	
		return false;
	}
	
	var row = jsonResponse[id];
	editorUpdateMod(id,row.title,row.desc,row.version,row.author,row.author_id,row.views);
	
	if ( typeof(row.keywords) != 'undefined' )
		textboxEditorKeywords.innerHTML = row.keywords;
	
	checkboxesEditorFlags.walk( function(e, key) { e.checked = row['flag_'+key]; } );
}

function editorUpdateMod(id,title,desc,version,author,authorUrl,views) {
	formEditor.rowID.value = id;
	modEditorAuthor.innerHTML = author;
	modEditorAuthor.href = authorUrl;
	modEditorViews.innerHTML = views;
	modEditorLink.innerHTML = "<h2><span class='version'>[" + version + "]</span> " + title + "</h2>" + desc;
	modEditorLink.href = URL_TOPIC + id;
}

function editorSubmit() {
	formEditor.submit();
}

// ================
// SHARING HANDLING
// ================

function shareRow(id) {
	ajax('?raw=json&id='+id, shareCallback, true);
	delClass(windowShare, 'hidden');
}

function shareFavorites() {
	ajax('?raw=json&favorites', shareCallback, false);
	delClass(windowShare, 'hidden');
}

function shareCallback(response, single) {
	try {
		var jsonResponse = JSON.parse(response);
	} catch(e) {
		codeboxShareHTML.innerHTML = 'PANIC! Please report this to the administrator';
		codeboxShareBBCode.innerHTML = 'PANIC! Please report this to the administrator';
		codeboxShareReddit.innerHTML = 'PANIC! Please report this to the administrator';
		return false;
	}
	
	var codeHTML = '';
	var codeBBCode = '';
	var codeReddit = '';
	
	// ===== HTML code
	codeHTML += (single ? 'According to the &lt;a href=&quot;http://mods.simplaza.net/&quot;&gt;Minecraft Mod Index&lt;/a&gt;:' : '&lt;ul&gt;') + N;
	codeHTML += shareGenerateCode(jsonResponse, CODE_HTML, single);
	codeHTML += (single ? '' : '&lt;/ul&gt;');
	codeboxShareHTML.innerHTML = codeHTML;
	
	// ===== BBCode code
	codeBBCode += (single ? 'According to the [url="http://mods.simplaza.net/"]Minecraft Mod Index[/url]:' : '[list]') + N;
	codeBBCode += shareGenerateCode(jsonResponse, CODE_BBCODE, single);
	codeBBCode += (single ? '' : '[/list]');
	codeboxShareBBCode.innerHTML = codeBBCode;
	
	// ===== Reddit code
	codeReddit += (single ? 'According to the [Minecraft Mod Index](http://mods.simplaza.net/):' + N + N : '');
	codeReddit += shareGenerateCode(jsonResponse, CODE_REDDIT, single);
	codeboxShareReddit.innerHTML = codeReddit;
	
}

function shareGenerateCode(rows, type, single) {
	var code = '';
	
	switch (type) {
		case CODE_HTML:
			rows.walk( function(row, id) {				
				code += (single ? '' : '&lt;li&gt;');
				code += '<b>test</b>&lt;b&gt;&lt;a href=&quot;' + URL_TOPIC + id + '&quot;&gt;' + row.title + '&lt;/a&gt;&lt;/b&gt; by &lt;a href=&quot;' + URL_USER + row.author_id + '&quot;&gt;' + row.author + '&lt;/a&gt;';
				code += (single ? '' : '&lt;/li&gt;' + N);
			} );
		break;
		
		case CODE_BBCODE:
			rows.walk( function(row, id) {
				
				code += (single ? '' : '[*]');
				code += '[b][url="' + URL_TOPIC + id + '"]' + row.title + '[/url][/b] by [url="' + URL_USER + row.author_id + '"]' + row.author + '[/url]' + N;
				code += (single ? row.desc + N : '');
			} );
		break;
		
		case CODE_REDDIT:
			rows.walk( function(row, id) {				
				code += (single ? '' : '* ' ) + '**[' + row.title + '](' + URL_TOPIC + id + ')** by [' + row.author + '](' + URL_USER + row.author_id + ')' + N;
			} );
		break;
	}
	
	return code;
}


// ==============
// MISC FUNCTIONS
// ==============

// ===== MOTW FUNCTIONS

function motwHide() {
	settings.style.disable_motw = true;
	settingsSave();
	window.location.reload();
}

// ===== ARRAY FUNCTIONS

// Recursively merges array A onto B (destructive)
function arrayMerge(a, b) {
	var newArray = {};
	
	b.walk (function(e,key) {
		
		//if ( (typeof(a[element]) == 'object') && (typeof(b[element]) == 'object') )
		//	newArray[element] = arrayMerge(a[element],b[element]);	// If we got an array in array, recurse into it
		
		// If the element exists in A, take it from A, otherwise take it from B
		if (typeof(a[key]) != 'undefined')
			newArray[key] = a[key];
		else
			newArray[key] = e;

	} );
	
	return newArray;
}

// ===== COOKIE FUNCTIONS

function cookieNukeAll() {
	cookieDelete('settings');
	cookieDelete('email');
	cookieDelete('favorites');
	window.location.reload();
}