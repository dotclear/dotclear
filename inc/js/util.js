/* exported getData */
'use strict';

const getData = function(id) {
  // Read the JSON-formatted data from the DOM. (from https://mathiasbynens.be/notes/json-dom-csp)
  // To be use with: <script type="application/json" id="myid-data">{"key":value, …}</script>
  const element = document.getElementById(`${id}-data`);
  const string = element.textContent;
  const data = JSON.parse(string);
  // Clear the element’s contents now that we have a copy of the data.
  element.innerHTML = '';
  return data;
};
