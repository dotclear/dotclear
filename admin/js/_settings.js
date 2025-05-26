/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  // Table sorting enabler
  const enableTableSort = (tableId, offset = 0, semver = -1, numeric = -1) => {
    const table = document.getElementById(tableId);
    if (!table) {
      return;
    }
    const headers = table.querySelectorAll('th');
    const tableBody = table.querySelector('tbody');
    const rows = tableBody.querySelectorAll('tr');
    // Track sort directions
    const directions = Array.from(headers).map(() => {});
    // Sort system
    const sortColumn = (indexData, indexHead) => {
      // Get the current direction
      const direction = directions[indexHead] || 'asc';

      // A factor based on the direction
      const multiplier = direction === 'asc' ? 1 : -1;

      // Clone the rows
      const newRows = Array.from(rows);

      function comparePartials(a, b) {
        if (a === b) {
          return 0;
        }
        const splitA = a.split('.');
        const splitB = b.split('.');
        const length = Math.max(splitA.length, splitB.length);
        for (let i = 0; i < length; i++) {
          // flip
          if (
            Number.parseInt(splitA[i]) > Number.parseInt(splitB[i]) ||
            (splitA[i] === splitB[i] && Number.isNaN(splitB[i + 1]))
          ) {
            return 1 * multiplier;
          }
          // don't flip
          if (
            Number.parseInt(splitA[i]) < Number.parseInt(splitB[i]) ||
            (splitA[i] === splitB[i] && Number.isNaN(splitA[i + 1]))
          ) {
            return -1 * multiplier;
          }
        }
      }

      // Sort rows by the content of cells
      newRows.sort((rowA, rowB) => {
        // Get the content of cells
        let cellA = rowA.querySelectorAll('td')[indexData].innerHTML;
        let cellB = rowB.querySelectorAll('td')[indexData].innerHTML;

        if (semver === indexHead) return comparePartials(cellA, cellB);
        if (numeric === indexHead) {
          cellA = Number.parseInt(rowA.querySelectorAll('td')[indexData].innerText);
          cellB = Number.parseInt(rowB.querySelectorAll('td')[indexData].innerText);
          switch (true) {
            case cellA.localeCompare(cellB):
              return 1 * multiplier;
            case cellA < cellB:
              return -1 * multiplier;
            case cellA === cellB:
              return 0;
          }
        }
        return cellA.localeCompare(cellB) * multiplier;
      });

      // Remove old rows
      for (const row of rows) tableBody.removeChild(row);

      // Append new row
      for (const row of newRows) tableBody.appendChild(row);

      // Remove old headers class
      for (const header of headers) {
        header.classList.remove('sorted-asc');
        header.classList.remove('sorted-desc');
      }

      // Set new header class
      headers[indexHead].classList.add(`sorted-${direction}`);

      // Reverse the direction
      directions[indexHead] = direction === 'asc' ? 'desc' : 'asc';
    };
    if (headers.length)
      for (let index = headers.length - 1; index >= 0; index--) {
        headers[index].addEventListener('click', () => sortColumn(index + offset, index));
      }
  };

  // Add sorting mecanism to some tables
  enableTableSort('settings');
});
