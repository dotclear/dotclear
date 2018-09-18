/*global chainHandler */
'use strict';

function confirmClose() {
  if (arguments.length > 0) {
    for (let i = 0; i < arguments.length; i++) {
      this.forms_id.push(arguments[i]);
    }
  }
}

confirmClose.prototype = {
  prompt: 'You have unsaved changes.',
  forms_id: [],
  forms: [],
  formSubmit: false,

  getCurrentForms: function() {
    // Store current form's element's values

    const formsInPage = this.getForms();
    const This = this;
    this.forms = [];
    for (let i = 0; i < formsInPage.length; i++) {
      const f = formsInPage[i];
      let tmpForm = [];
      for (let j = 0; j < f.elements.length; j++) {
        const e = this.getFormElementValue(f[j]);
        if (e != undefined) {
          tmpForm.push(e);
        }
      }
      this.forms.push(tmpForm);

      chainHandler(f, 'onsubmit', function() {
        This.formSubmit = true;
      });
    }
  },

  compareForms: function() {
    // Compare current form's element's values to their original values
    // Return false if any difference, else true

    if (this.forms.length == 0) {
      return true;
    }

    const formsInPage = this.getForms();
    for (let i = 0; i < formsInPage.length; i++) {
      const f = formsInPage[i];
      var tmpForm = [];
      for (let j = 0; j < f.elements.length; j++) {
        const e = this.getFormElementValue(f[j]);
        if (e != undefined) {
          tmpForm.push(e);
        }
      }
      for (let j = 0; j < this.forms[i].length; j++) {
        if (this.forms[i][j] != tmpForm[j]) {
          return false;
        }
      }
    }

    return true;
  },

  getForms: function() {
    // Get current list of forms as HTMLCollection(s)

    if (!document.getElementsByTagName || !document.getElementById) {
      return [];
    }

    if (this.forms_id.length > 0) {
      let res = [];
      for (let i = 0; i < this.forms_id.length; i++) {
        const f = document.getElementById(this.forms_id[i]);
        if (f != undefined) {
          res.push(f);
        }
      }
      return res;
    } else {
      return document.getElementsByTagName('form');
    }

    return [];
  },

  getFormElementValue: function(e) {
    // Return current value of an form element

    if (e == undefined) {
      // Unknown object
      return undefined;
    }
    if (e.type != undefined && e.type == 'button') {
      // Ignore button element
      return undefined;
    }
    if (e.classList.contains('meta-helper') || e.classList.contains('checkbox-helper')) {
      // Ignore some application helper element
      return undefined;
    }
    if (e.type != undefined && e.type == 'radio') {
      // Return actual radio button value if selected, else null
      return this.getFormRadioValue(e);
    } else if (e.type != undefined && e.type == 'checkbox') {
      // Return actual checkbox button value if checked, else null
      return this.getFormCheckValue(e);
    } else if (e.type != undefined && e.type == 'password') {
      // Ignore password element
      return null;
    } else if (e.value != undefined) {
      // Return element value if not undefined
      return e.value;
    } else {
      // Every other case, return null
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
    for (let i = 0; i < e.length; i++) {
      if (e[i].checked) {
        return e[i].value;
      } else {
        return null;
      }
    }
    return null;
  }
};

let confirmClosePage = new confirmClose();

chainHandler(window, 'onload', function() {
  confirmClosePage.getCurrentForms();
});

chainHandler(window, 'onbeforeunload', function(event_) {
  if (event_ == undefined && window.event) {
    event_ = window.event;
  }

  if (!confirmClosePage.formSubmit && !confirmClosePage.compareForms()) {
    event_.returnValue = confirmClosePage.prompt;
    return confirmClosePage.prompt;
  }
  return false;
});
