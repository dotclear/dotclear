/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready

  document.getElementById('tb_excerpt')?.addEventListener('keypress', function () {
    if (this.value.length > 255) {
      this.value = this.value.substring(0, 255);
    }
  });
});
