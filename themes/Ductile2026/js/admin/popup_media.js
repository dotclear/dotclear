/*global dotclear */
'use strict';
dotclear.ready(() => {
    document.querySelector('#media-select-cancel')?.addEventListener('click', () => window.close());

    document.querySelector('#media-select-ok')?.addEventListener('click', () => {
        const main = window.opener;
        const media_url = document.querySelector("input[name='src']:checked").value;
        main.document.querySelector('#user_image_src').setAttribute('src', media_url);
        main.document.querySelector('#user_image').value = media_url;

        window.close();
    });
});
