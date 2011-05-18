function confirmClose() {
	
	if (arguments.length > 0) {
		for (var i=0; i<arguments.length; i++) {
			this.forms_id.push(arguments[i]);
		}
	}
};

confirmClose.prototype = {
	prompt: 'You have unsaved changes.',
	forms_id: new Array(),
	forms: new Array(),
	formSubmit: false,
	
	getCurrentForms: function() {
		var formsInPage = this.getForms();
		var f;
		var This = this;
		for (var i=0; i<formsInPage.length; i++) {
			f = formsInPage[i];
			var tmpForm = new Array();
			for (var j=0; j<f.elements.length; j++) {
				tmpForm.push(this.getFormElementValue(f[j]));
			}
			this.forms.push(tmpForm);
			
			chainHandler(f,'onsubmit',function() {
				This.formSubmit = true;
			});
		}
	},
	
	compareForms: function() {
		if (this.forms.length == 0) {
			return true;
		}
		
		var formsInPage = this.getForms();
		var f;
		for (var i=0; i<formsInPage.length; i++) {
			f = formsInPage[i];
			for (var j=0; j<f.elements.length; j++) {
				if (this.forms[i][j] != this.getFormElementValue(f[j])) {
					return false;
				}
			}
		}
		
		return true;
	},
	
	getForms: function() {
		if (!document.getElementsByTagName || !document.getElementById) {
			return new Array();
		}
		
		if (this.forms_id.length > 0) {
			var res = new Array();
			var f;
			for (var i=0; i<this.forms_id.length; i++) {
				f = document.getElementById(this.forms_id[i]);
				if (f != undefined) {
					res.push(f);
				}
			}
			return res;
		} else {
			return document.getElementsByTagName('form');
		}
		
		return new Array();
	},
	
	getFormElementValue: function(e) {
		if (e.type != undefined && e.type == 'radio') {
			return this.getFormRadioValue(e);
		} else if (e.type != undefined && e.type == 'checkbox') {
			return this.getFormCheckValue(e);
		} else if (e.value != undefined) {
			return e.value;
		} else {
			return null;
		}
	},
	
	getFormCheckValue: function(e) {
		if (e.checked) {
			return e.value;
		}
		return null;
	},
	
	getFormRadioValue: function(e) {
		for (var i=0; i <e.length; i++) {
			if (e[i].checked) {
				return e[i].value;
			} else {
				return null;
			}
		}
		return null;
	}
};

var confirmClosePage = new confirmClose();

chainHandler(window,'onload',function() {
	confirmClosePage.getCurrentForms();
});

chainHandler(window,'onbeforeunload',function(event_) {
	if (event_ == undefined && window.event) {
		event_ = window.event;
	}
	
	if (!confirmClosePage.formSubmit && !confirmClosePage.compareForms()) {
		event_.returnValue = confirmClosePage.prompt;
		return confirmClosePage.prompt;
	}
	return false;
});