// Accessibility links thks to vie-publique.fr
aFocus = function() {
	if(document.getElementById("prelude")) {
		var aElts = document.getElementById("prelude").getElementsByTagName("A");
		for (var i=0; i<aElts.length; i++) {
			aElts[i].className="hidden";
			aElts[i].onfocus=function() {
				this.className="";
			}
		}
	}
}
// events onload
function addLoadEvent(func) {
	if (window.addEventListener)
		window.addEventListener("load", func, false);
	else if (window.attachEvent)
		window.attachEvent("onload", func);
}
addLoadEvent(aFocus);