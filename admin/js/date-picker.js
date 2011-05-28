function datePicker(target)
{
	if (!document.getElementById) { return; }
	
	if (!target || target.nodeName.toLowerCase() != 'input') {
		return;
	}
	
	this.target = target;
	this.oTable = document.createElement('table');
	this.oBody = document.createElement('tbody');
	this.oDates = new Array();
	this.oMonth = document.createElement('span');
	this.oYear = document.createElement('span');
	this.oHour = document.createElement('input');
	this.oMinute = document.createElement('input');
	this.oTable.id = 'dc_datepicker_'+target.id;
	this.oTable.className = 'date-picker';
	
	var cur = 1;
	var oRow, oHeading, oSpan;
	
	// Set title
	oRow = document.createElement('tr');
	
	// Month block
	oHeading = document.createElement('th');
	oHeading.colSpan = 4;
	oHeading.className = 'date-picker-month';
	
	var nav = document.createElement('span');
	nav.appendChild(document.createTextNode(String.fromCharCode(171)));
	nav.fn = this.changeMonth;
	nav.obj = this;
	nav.onclick = function() { this.fn.call(this.obj,-1); };
	nav.className = 'date-picker-control';
	oHeading.appendChild(nav);
	
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	
	nav = document.createElement('span');
	nav.appendChild(document.createTextNode(String.fromCharCode(187)));
	nav.fn = this.changeMonth;
	nav.obj = this;
	nav.onclick = function() { this.fn.call(this.obj,+1); };
	nav.className = 'date-picker-control';
	oHeading.appendChild(nav);
	
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	
	oHeading.appendChild(this.oMonth);
	
	oRow.appendChild(oHeading);
	
	// Year block
	oHeading = document.createElement('th');
	oHeading.colSpan = 3;
	oHeading.className = 'date-picker-year';
	
	oHeading.appendChild(this.oYear);
	
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	
	nav = document.createElement('span');
	nav.appendChild(document.createTextNode(String.fromCharCode(171)));
	nav.fn = this.changeYear;
	nav.obj = this;
	nav.onclick = function() { this.fn.call(this.obj,-1); };
	nav.className = 'date-picker-control';
	oHeading.appendChild(nav);
	
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	
	nav = document.createElement('span');
	nav.appendChild(document.createTextNode(String.fromCharCode(187)));
	nav.fn = this.changeYear;
	nav.obj = this;
	nav.onclick = function() { this.fn.call(this.obj,+1); };
	nav.className = 'date-picker-control';
	oHeading.appendChild(nav);
	
	oRow.appendChild(oHeading);
	
	this.oBody.appendChild(oRow);
	
	// Create legend
	oRow = document.createElement('tr');
	var cday;
	for (i=0; i<this.days.length; i++) {
		cday = this.days[i].substring(0,1).toUpperCase();
		oHeading = document.createElement('th');
		oHeading.appendChild(document.createTextNode(cday));
		oHeading.setAttribute('title',this.days[i]);
		oRow.appendChild(oHeading);
	}
	this.oBody.appendChild(oRow);
	
	// Create 6 rows of 7 cols for days
	for (var i=0; i<6; i++) {
		oRow = document.createElement('tr');
		
		for (var j=0; j<7; j++) {
			this.oDates[cur] = document.createElement('td');
			this.oDates[cur].appendChild(document.createTextNode(
				String.fromCharCode(160)));
			oRow.appendChild(this.oDates[cur]);
			cur++;
		}
		
		this.oBody.appendChild(oRow);
	}
	
	// Time controls
	oRow = document.createElement('tr');
	
	oHeading = document.createElement('th');
	oHeading.className = 'date-picker-control';
	oHeading.appendChild(document.createTextNode('!'));
	oHeading.setAttribute('title',this.now_msg);
	oHeading.fn = this.sendNow;
	oHeading.obj = this;
	oHeading.onclick = function() { this.fn.call(this.obj); };
	
	oRow.appendChild(oHeading);
	
	oHeading = document.createElement('th');
	oHeading.colSpan = 5;
	
	oSpan = document.createElement('span');
	oSpan.className = 'date-picker-control';
	oSpan.appendChild(document.createTextNode('-'));
	oSpan.fn = this.changeHour;
	oSpan.obj = this;
	oSpan.onclick = function() { this.fn.call(this.obj,-1); };
	oHeading.appendChild(oSpan);
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	oSpan = document.createElement('span');
	oSpan.className = 'date-picker-control';
	oSpan.appendChild(document.createTextNode('+'));
	oSpan.fn = this.changeHour;
	oSpan.obj = this;
	oSpan.onclick = function() { this.fn.call(this.obj,+1); };
	oHeading.appendChild(oSpan);
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	
	this.oHour.size = 3;
	oHeading.appendChild(this.oHour);
	
	oHeading.appendChild(document.createTextNode(' : '));
	
	this.oMinute.size = 3;
	oHeading.appendChild(this.oMinute);
	
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	oSpan = document.createElement('span');
	oSpan.className = 'date-picker-control';
	oSpan.appendChild(document.createTextNode('-'));
	oSpan.fn = this.changeMinute;
	oSpan.obj = this;
	oSpan.onclick = function() { this.fn.call(this.obj,-1); };
	oHeading.appendChild(oSpan);
	oHeading.appendChild(document.createTextNode(String.fromCharCode(160)));
	oSpan = document.createElement('span');
	oSpan.className = 'date-picker-control';
	oSpan.appendChild(document.createTextNode('+'));
	oSpan.fn = this.changeMinute;
	oSpan.obj = this;
	oSpan.onclick = function() { this.fn.call(this.obj,+1); };
	
	oHeading.appendChild(oSpan);
	
	oRow.appendChild(oHeading);
	
	// Close control
	oHeading = document.createElement('th');
	oHeading.className = 'date-picker-control';
	oHeading.appendChild(document.createTextNode('x'));
	oHeading.setAttribute('title',this.close_msg);
	oHeading.fn = this.close;
	oHeading.obj = this;
	oHeading.onclick = function() { this.fn.call(this.obj); };
	
	oRow.appendChild(oHeading);
	
	this.oBody.appendChild(oRow);
};

datePicker.prototype = {
	year: 0,
	month: 0,
	day: 0,
	hour: 0,
	minute: 0,
	
	img_src: '',
	img_top: '0.2em',
	now_msg: 'now',
	close_msg: 'close',
	
	days: new Array('Monday','Tuesday','Wednesday','Thursday','Friday',
	'Saturday','Sunday'),
	
	months: new Array('January','February','March','April','May','June',
	'July','August','September','October','November','December'),
	
	
	setDate: function() {
		if (this.numberOfDays() < this.day) {
			this.day = this.numberOfDays();
		}
		
		while (this.oYear.hasChildNodes()) {
			this.oYear.removeChild(this.oYear.firstChild)
		}
		this.oYear.appendChild(document.createTextNode(this.year));
		
		while (this.oMonth.hasChildNodes()) {
			this.oMonth.removeChild(this.oMonth.firstChild)
		}
		this.oMonth.appendChild(document.createTextNode(
			this.months[this.month-1]));
		
		var firstDay = this.firstDay();
		var nbDays = this.numberOfDays();
		
		// Empty days
		for (var i=1; i<=42; i++) {
			while (this.oDates[i].hasChildNodes()) {
				this.oDates[i].removeChild(this.oDates[i].firstChild)
			}
			this.oDates[i].appendChild(document.createTextNode('-'));
			this.oDates[i].className = '';
			this.oDates[i].onclick = function() { return; };
		}
		
		// Insert days from the first day to the last
		for (i=1; i<=nbDays; i++) {
			var j=firstDay+i-1;
			
			while (this.oDates[j].hasChildNodes()) {
				this.oDates[j].removeChild(this.oDates[j].firstChild)
			}
			
			this.oDates[j].appendChild(document.createTextNode(i));
			this.oDates[j].index = i;
			this.oDates[j].fn = this.sendDate;
			this.oDates[j].obj = this;
			this.oDates[j].onclick = function() { this.fn.call(this.obj,this.index); };
			if (i == this.day) {
				this.oDates[j].className = 'date-picker-today';
			} else {
				this.oDates[j].className = 'date-picker-day';
			}
		}
		
		// Set time
		this.setHour(this.hour);
		this.setMinute(this.minute);
	},
	
	setHour: function(h) {
		if (h < 0) { h = 23; }
		if (h > 23) { h = 0; }
		if (h < 10) { h = '0'+h; }
		
		this.hour = h*1;
		this.oHour.value = h;
	},
	
	setMinute: function(m) {
		if (m < 0) { m = 59; }
		if (m > 59) { m = 0; }
		if (m < 10) { m = '0'+m; }
		
		this.minute = m*1;
		this.oMinute.value = m;
	},
	
	changeMonth: function(dir) {
		var y = this.year;
		var m = this.month;
		m = m+dir;
		
		if (m > 12) { this.month = 1; this.year++; }
		else if ( m < 1) { this.month = 12; this.year--; }
		else { this.month = m; }
		
		this.setDate();
	},
	
	changeYear: function(dir) {
		this.year = this.year + dir;
		this.setDate();
	},
	
	changeHour: function(dir) {
		this.setHour(this.hour*1+dir);
	},
	
	changeMinute: function(dir) {
		this.setMinute(this.minute*1+dir);
	},
	
	sendDate: function(d) {
		var m = this.month;
		var hour = this.oHour.value*1;
		var minute = this.oMinute.value*1;
		
		if (hour < 0 || hour > 23 || isNaN(hour)) { hour = 0; }
		if (minute < 0 || minute > 59 || isNaN(minute)) { minute = 0; }
		
		if (m < 10) { m = '0'+m; }
		if (d < 10) { d = '0'+d; }
		if (hour < 10) { hour = '0'+hour; }
		if (minute < 10) { minute = '0'+minute; }
		
		this.target.value = this.year+'-'+m+'-'+d+' '+hour+':'+minute;
		this.close();
	},
	
	sendNow: function() {
		var dt = new Date();
		var y = dt.getFullYear();
		var m = dt.getMonth() + 1;
		var d = dt.getDate();
		var h = dt.getHours();
		var i = dt.getMinutes();
		
		if (m < 10) { m = '0'+m; }
		if (d < 10) { d = '0'+d; }
		if (h < 10) { h = '0'+h; }
		if (i < 10) { i = '0'+i; }
		
		this.target.value = y+'-'+m+'-'+d+' '+h+':'+i;
		this.close();
	},
	
	close: function() {
		document.body.removeChild(this.oTable);
	},
	
	numberOfDays: function() {
		var res = 31;
		if (this.month == 4 || this.month == 6 || this.month == 9 ||
		this.month == 11) {
			res = 30;
		} else if (this.month == 2) {
			res = 28;
			if (this.year%4 == 0 && (this.year%100 != 0 ||
			this.year%400 == 0)) {
				res = 29;
			}
		}
		
		return res;
	},
	
	firstDay: function() {
		var dt = new Date(this.year,this.month-1,1);
		var res = dt.getDay();
		
		if (res == 0) {
			res = 7;
		}
		
		return res;
	},
	
	show: function() {
		// Parsing target value
		var re = /(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/;
		var match = re.exec(this.target.value);
		if (match) {
			this.year = match[1]*1;
			this.month = match[2]*1;
			this.day = match[3]*1;
			this.hour = match[4]*1;
			this.minute = match[5]*1;
		} else {
			var dt = new Date();
			this.year = dt.getFullYear();
			this.month = dt.getMonth() + 1;
			this.day = dt.getDate();
			this.hour = dt.getHours();
			this.minute = dt.getMinutes();
		}
		
		this.oTable.appendChild(this.oBody);
		this.setDate();
		this.setPosition();
		document.body.appendChild(this.oTable);
		this.oHour.focus();
	},
	
	setPosition: function() {
		var t_x = this.findPosX(this.target);
		var t_y = this.findPosY(this.target);
		var o_h = this.oTable.offsetHeight;
		var o_w = this.oTable.offsetWidth;
		
		this.oTable.style.position = 'absolute';
		this.oTable.style.zIndex = '100';
		this.oTable.style.top = t_y+'px';
		this.oTable.style.left = t_x+'px';
	},
	
	findPosX: function(obj) {
		var curleft = 0;
		if(obj.offsetParent) {
			while(1) {
				curleft += obj.offsetLeft;
				if(!obj.offsetParent) {
					break;
				}
				obj = obj.offsetParent;
			}
		} else if(obj.x) {
			curleft += obj.x;
		}
		return curleft;
	},
	
	findPosY: function(obj) {
		var curtop = 0;
		if(obj.offsetParent) {
			while(1) {
				curtop += obj.offsetTop;
				if(!obj.offsetParent) {
					break;
				}
				obj = obj.offsetParent;
			}
		} else if(obj.y) {
			curtop += obj.y;
		}
		return curtop;
	},
	
	draw: function() {
		var imgE = document.createElement('img');
		imgE.src = this.img_src;
		imgE.style.position = 'absolute';
		imgE.style.top = this.img_top;
		imgE.style.left = (this.target.clientWidth+4)+'px';
		imgE.obj = this;
		imgE.fn = this.show;
		imgE.onclick = function() { this.fn.apply(this.obj); };
		
		this.target.parentNode.style.position = 'relative';
		this.target.parentNode.insertBefore(imgE,this.target.nextSibling);
	}
};
