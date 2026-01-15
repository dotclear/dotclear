/*global dotclear */
'use strict';

dotclear.confirmClose = class {
  // Init properties
  prompt = 'You have unsaved changes.';
  forms_id = [];
  forms = [];
  form_submit = false;

  constructor(...args) {
    // Add given forms
    if (args.length > 0) {
      for (const argument of args) {
        this.forms_id.push(argument);
      }
    }
  }

  getCurrentForms() {
    // Store current form's element's values

    const eltRef = (e) => (e.id !== undefined && e.id !== '' ? e.id : e.name);

    const formsInPage = this.getForms();
    this.forms = [];
    for (const form of formsInPage) {
      const tmpForm = [];
      if (form?.elements)
        for (let form_item = 0; form_item < form.elements.length; form_item++) {
          const form_item_value = this.getFormElementValue(form[form_item]);
          if (form_item_value !== undefined) {
            tmpForm[eltRef(form[form_item])] = form_item_value;
          }
        }
      // Loop on form iframes
      const iframes = form.getElementsByTagName('iframe');
      if (iframes !== undefined) {
        for (const iframe of iframes) {
          if (iframe.contentDocument.body.id !== undefined && iframe.contentDocument.body.id !== '') {
            tmpForm[iframe.contentDocument.body.id] = iframe.contentDocument.body.innerHTML;
          }
        }
      }
      this.forms.push(tmpForm);

      form.addEventListener('submit', () => {
        this.form_submit = true;
      });
    }
  }

  compareForms() {
    // Compare current form's element's values to their original values
    // Return false if any difference, else true

    if (this.forms.length === 0) {
      return true;
    }

    const formMatch = (current, source) =>
      Object.keys(current).every(
        (key) => !Object.hasOwn(source, key) || (Object.hasOwn(source, key) && source[key] === current[key]),
      );
    const eltRef = (e) => (e.id !== undefined && e.id !== '' ? e.id : e.name);
    const formFirstDiff = (current, source) => {
      let diff = '<none>';
      Object.keys(current).every((key) => {
        if (Object.hasOwn(source, key) && current[key] !== source[key]) {
          diff = `Key = [${key}] - Original = [${source[key]}] - Current = [${current[key]}]`;
          return false;
        }
        return true;
      });
      return diff;
    };

    const formsInPage = this.getForms();
    for (let form_item = 0; form_item < formsInPage.length; form_item++) {
      const form = formsInPage[form_item];
      // Loop on form elements (Codemirror)
      for (let form_element = 0; form_element < form.elements.length; form_element++) {
        if (form[form_element].type === 'textarea' && form[form_element].classList.contains('cm_dirty')) {
          return false;
        }
      }
      // Loop on form elements
      const tmpForm = [];
      for (let form_element = 0; form_element < form.elements.length; form_element++) {
        const form_element_value = this.getFormElementValue(form[form_element]);
        if (form_element_value !== undefined) {
          tmpForm[eltRef(form[form_element])] = form_element_value;
        }
      }
      // Loop on form iframes
      const iframes = form.getElementsByTagName('iframe');
      if (iframes !== undefined) {
        for (const iframe of iframes) {
          if (iframe.contentDocument.body.id !== undefined && iframe.contentDocument.body.id !== '') {
            tmpForm[iframe.contentDocument.body.id] = iframe.contentDocument.body.innerHTML;
          }
        }
      }
      if (!formMatch(tmpForm, this.forms[form_item])) {
        return false;
      }
    }

    return true;
  }

  getForms() {
    // Get current list of forms as HTMLCollection(s)

    if (!document.getElementsByTagName || !document.getElementById) {
      return [];
    }

    if (this.forms_id.length > 0) {
      const res = [];
      for (const form_id of this.forms_id) {
        const f = document.getElementById(form_id);
        if (f) {
          res.push(f);
        }
      }
      return res;
    }
    return document.getElementsByTagName('form');
  }

  getFormElementValue(e) {
    // Return current value of an form element

    if (
      // Unknown object
      e === undefined ||
      // Ignore unidentified object
      ((e.id === undefined || e.id === '') && (e.name === undefined || e.name === '')) ||
      // Ignore button element
      (e.type !== undefined && e.type === 'button') ||
      // Ignore submit element
      (e.type !== undefined && e.type === 'submit') ||
      // Ignore readonly element
      e.hasAttribute('readonly') ||
      // Ignore some application helper element
      e.classList.contains('meta-helper') ||
      e.classList.contains('checkbox-helper')
    ) {
      return undefined;
    }

    if (e.type !== undefined && (e.type === 'radio' || e.type === 'checkbox')) {
      // Return actual radio button value if selected, else null
      return e.checked ? e.value : null;
    }
    if (e.type !== undefined && e.type === 'password') {
      // Ignore password element
      return null;
    }
    return e.value === undefined ? null : e.value;
  }
};

globalThis.addEventListener('load', () => {
  const confirm_close = dotclear.getData('confirm_close');

  dotclear.confirmClosePage = new dotclear.confirmClose(...confirm_close.forms);
  dotclear.confirmClosePage.prompt = confirm_close.prompt;
  dotclear.confirmClosePage.lowbattery = confirm_close.lowbattery;

  // Wait one second to let CKEditor loading its instances if any
  setTimeout(() => dotclear.confirmClosePage.getCurrentForms(), 1000);

  if (navigator.getBattery) {
    const checkBattery = () => {
      navigator.getBattery().then((battery) => {
        const level = battery.level * 100;
        if (
          level < 5 &&
          dotclear.confirmClosePage !== undefined &&
          !dotclear.confirmClosePage.form_submit &&
          !dotclear.confirmClosePage.compareForms()
        ) {
          // Form unsaved, emit a warning
          const message = dotclear.confirmClosePage.lowbattery.replace(/%d/, level);
          alert(message);
        }
      });
    };
    // Add monitor to detect low battery
    dotclear.confirmClosePage.monitor = setInterval(checkBattery, 60 * 5 * 1000);
  }

  const checkDirty = () => {
    //  Add/remove .dirty class/data attribute to document if at least one of monitored forms is dirty
    const target = document.documentElement;
    if (
      dotclear.confirmClosePage !== undefined &&
      !dotclear.confirmClosePage.form_submit &&
      !dotclear.confirmClosePage.compareForms()
    ) {
      target.classList.add('dirty');
      target.dataset.dirty = '1';
    } else {
      target.classList.remove('dirty');
      target.dataset.dirty = '';
    }
  };
  // Add monitor to detect dirty forms every 5 seconds
  dotclear.confirmClosePage.dirty = setInterval(checkDirty, 5 * 1000);
});

globalThis.addEventListener('beforeunload', (event) => {
  if (
    !(
      dotclear.confirmClosePage !== undefined &&
      !dotclear.confirmClosePage.form_submit &&
      !dotclear.confirmClosePage.compareForms()
    )
  ) {
    return;
  }
  if (dotclear.debug) {
    console.log('Confirmation before exiting is required.');
  }
  event.preventDefault(); // HTML5 specification
});
