/* 	============================================
	THE MINECRAFT MOD INDEX
	USER INTERFACE KEYBOARD FUNCTIONALITY

	
	by The Major / Crome Tysnomi / Ayman Habayeb
	http://gnu32.deviantart.com
	============================================
*/

// ================
// GLOBAL VARIABLES
// ================
KCODE = [38,38,40,40,37,39,37,39,66,65,13];
RCODE = ['YOU DESERVE MORE THAN THIS','THERE IS NOTHING TO FEAR','YOU CANNOT DIGITISE LIFE','WE WILL SEE YOU ON THE OTHER SIDE','WE ARE THE FIRST OF THE CHILDREN','YOU ARE MORE THAN A NUMBER','HOPE LIES IN THE RUINS','IN TIME YOU WILL THANK US','OUR SPIRITS ARE BEING CRUSHED']

function keyboardInit() {
	document.onkeyup = keyboardHandler;
}

function keyboardHandler(e) {
	var k = e.keyCode;
	
	konamiCode(k);
}

function konamiCode(k) {
	if (KCODE.next() != k) {
		KCODE.pointer = 0;
		return false;
	}
	
	if (KCODE.pointer == 0 && k == 13) {
		document.onkeyup = null;
		document.body.style.overflowX = 'hidden';
		setInterval(revelation, 100);
		setTimeout('window.location = "http://simplaza.net/hax/bsod/?win9x";',5000 + r(7500) );
	}
	
}

function revelation() {
    var rev = document.createElement("div");
    rev.className = "rev";
    rev.style.left = r(window.innerWidth) + "px";
    rev.style.top = (window.scrollY + r(window.innerHeight)) + "px";
    rev.innerHTML = RCODE.random();

    document.body.appendChild(rev);
}