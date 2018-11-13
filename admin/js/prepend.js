/*exported getData */
'use strict';

function getData(id, clear = true) {
  let data = {};
  // Read the JSON-formatted data from the DOM. (from https://mathiasbynens.be/notes/json-dom-csp)
  // To be use with: <script type="application/json" id="myid-data">{"key":value, …}</script>
  const element = document.getElementById(`${id}-data`);
  if (element) {
    try {
      data = JSON.parse(element.textContent);
      if (clear) {
        // Clear the element’s contents
        element.innerHTML = '';
      }
    } catch (e) {}
  }
  return data;
}
