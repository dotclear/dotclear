/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  dotclear.mergeDeep(dotclear, dotclear.getData('blowup'));

  // Predefined styles
  dotclear.blowup_styles = {
    'Plumetis 2.6':
      'body_bg_c:"#F5F5F5"; body_bg_g:"solid"; body_txt_f:"ss1"; body_txt_s:"14px"; body_txt_c:"#333333"; body_line_height:"24px"; top_image:"plumetis"; blog_title_hide:"0"; blog_title_f:"ss1"; blog_title_s:"36px"; blog_title_c:"#333333"; blog_title_a:"left"; blog_title_p:"20:94"; body_link_c:"#000000"; body_link_f_c:"#D33800"; body_link_v_c:"#333333"; sidebar_position:""; sidebar_text_f:"ss1"; sidebar_text_s:"12px"; sidebar_text_c:""; sidebar_title_f:""; sidebar_title_s:"14px"; sidebar_title_c:"#666666"; sidebar_title2_f:""; sidebar_title2_s:"12px"; sidebar_title2_c:"#000000"; sidebar_line_c:"#A6D9DC"; sidebar_link_c:""; sidebar_link_f_c:"#D33800"; sidebar_link_v_c:""; date_title_f:""; date_title_s:"12px"; date_title_c:"#666666"; post_title_f:"ss1"; post_title_s:"24px"; post_title_c:"#D33800"; post_comment_bg_c:"#E2EDED"; post_comment_c:"#333333"; post_commentmy_bg_c:"#A6D9DC"; post_commentmy_c:"#000000"; prelude_c:"#A6D9DC"; footer_f:""; footer_s:"12px"; footer_c:"#FFFFFF"; footer_l_c:"#FFFFFF"; footer_bg_c:"#999999"; extra_css:"h1, .post-title {font-weight: normal;text-shadow: 1px 1px 0 #fff;}#footer {background-image: none}body {border-top: 72px solid #A6D9DC}#top {margin-top: -36px}.post a {border-bottom: 1px solid #999}.post-title a, .post-info-co a {border-bottom: none}a:hover {background-color: #eee;text-decoration:none;}"',
    Forest:
      'body_bg_c:"#80661A"; body_bg_g:"light"; body_txt_f:""; body_txt_s:""; body_txt_c:"#0A0A00"; body_line_height:"1.4em"; top_image:"default"; blog_title_hide:"0"; blog_title_f:"s3"; blog_title_s:"4em"; blog_title_c:"#D9D9BF"; blog_title_a:""; blog_title_p:""; body_link_c:"#666600"; body_link_f_c:"#CC9933"; body_link_v_c:"#8D8D40"; sidebar_position:""; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:"#0A0A00"; sidebar_title_f:"s2"; sidebar_title_s:"1.6em"; sidebar_title_c:"#4D4D00"; sidebar_title2_f:"s2"; sidebar_title2_s:""; sidebar_title2_c:"#575700"; sidebar_line_c:"#D9D9BF"; sidebar_link_c:"#40330D"; sidebar_link_f_c:"#666600"; sidebar_link_v_c:"#40330D"; date_title_f:""; date_title_s:""; date_title_c:"#B3B380"; post_title_f:"s2"; post_title_s:"2em"; post_title_c:"#4D4D00"; post_comment_bg_c:"#F0F0E6"; post_comment_c:"#0A0A00"; prelude_c:"#140F05"; footer_f:""; footer_s:""; footer_c:"#FFFFFF"; footer_l_c:"#D9AD2B"; footer_bg_c:"#33260D"',
    Flamingo:
      'body_bg_c:"#CC9999"; body_bg_g:"light"; body_txt_f:"ss3"; body_txt_s:"1.2em"; body_txt_c:"#1A1A00"; body_line_height:"1.5em"; top_image:"flamingo"; blog_title_hide:"0"; blog_title_f:"ss1"; blog_title_s:"3.5em"; blog_title_c:"#FFFFFF"; blog_title_a:""; blog_title_p:""; body_link_c:"#AD8282"; body_link_f_c:"#8282D9"; body_link_v_c:"#997373"; sidebar_position:""; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:""; sidebar_title_f:"ss4"; sidebar_title_s:"1.4em"; sidebar_title_c:"#8282D9"; sidebar_title2_f:"ss3"; sidebar_title2_s:"1.2em"; sidebar_title2_c:"#AD8282"; sidebar_line_c:"#CDCDFF"; sidebar_link_c:"#262640"; sidebar_link_f_c:"#AD8282"; sidebar_link_v_c:"#262640"; date_title_f:"ss4"; date_title_s:""; date_title_c:"#D9B3B3"; post_title_f:"ss4"; post_title_s:"1.8em"; post_title_c:"#8282D9"; post_comment_bg_c:"#F2E5E5"; post_comment_c:""; prelude_c:"#140F0F"; footer_f:""; footer_s:""; footer_c:"#FFFFFF"; footer_l_c:""; footer_bg_c:"#140F0F"',
    Iceberg:
      'body_bg_c:"#5280A3"; body_bg_g:"dark"; body_txt_f:"ss3"; body_txt_s:"1.1em"; body_txt_c:"#757575"; body_line_height:"1.5em"; top_image:"default"; blog_title_hide:"0"; blog_title_f:"s2"; blog_title_s:"3em"; blog_title_c:"#FFFFFF"; blog_title_a:""; blog_title_p:""; body_link_c:"#BDB000"; body_link_f_c:"#F3E66D"; body_link_v_c:"#BDB000"; sidebar_position:"left"; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:""; sidebar_title_f:"ss3"; sidebar_title_s:"1.4em"; sidebar_title_c:"#689B9C"; sidebar_title2_f:"ss3"; sidebar_title2_s:"1.2em"; sidebar_title2_c:"#737300"; sidebar_line_c:"#E6E6CD"; sidebar_link_c:"#66664D"; sidebar_link_f_c:"#5280A3"; sidebar_link_v_c:"#66664D"; date_title_f:""; date_title_s:""; date_title_c:"#000000"; post_title_f:"s2"; post_title_s:"1.8em"; post_title_c:"#6F6800"; post_comment_bg_c:"#E4E4E2"; post_comment_c:""; prelude_c:"#0E2734"; footer_f:""; footer_s:""; footer_c:"#FFFFFF"; footer_l_c:""; footer_bg_c:"#0E2734"',
    Night:
      'body_bg_c:"#0D1A26"; body_bg_g:"dark"; body_txt_f:"ss3"; body_txt_s:"1.1em"; body_txt_c:"#050A0F"; body_line_height:"1.5em"; top_image:"default"; blog_title_hide:"0"; blog_title_f:"s2"; blog_title_s:"3.5em"; blog_title_c:"#F2F2E5"; blog_title_a:""; body_link_c:"#336699"; body_link_f_c:"#66664D"; body_link_v_c:"#2B5782"; sidebar_position:""; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:""; sidebar_title_f:"ss3"; sidebar_title_s:"1.4em"; sidebar_title_c:"#336699"; sidebar_title2_f:"ss3"; sidebar_title2_s:"1.2em"; sidebar_title2_c:"#737300"; sidebar_line_c:"#E6E6CD"; sidebar_link_c:"#66664D"; sidebar_link_f_c:"#336699"; sidebar_link_v_c:"#66664D"; date_title_f:""; date_title_s:""; date_title_c:"#ADAD82"; post_title_f:"s2"; post_title_s:"1.8em"; post_title_c:"#737300"; post_comment_bg_c:"#E6E6CD"; post_comment_c:""; prelude_c:"#070E14"; footer_f:""; footer_s:""; footer_c:"#FFFFFF"; footer_l_c:""; footer_bg_c:"#14140F"; blog_title_p:""',
    'Peas & Carrots':
      'body_bg_c:"#9DCA25"; body_bg_g:"light"; body_txt_f:"ss1"; body_txt_s:"1.2em"; body_txt_c:"#383839"; body_line_height:"1.5em"; top_image:"butterflies"; blog_title_hide:"0"; blog_title_f:"ss4"; blog_title_s:"3em"; blog_title_c:"#DBDB9D"; blog_title_a:"left"; blog_title_p:""; body_link_c:"#646B10"; body_link_f_c:"#DF6C01"; body_link_v_c:"#919924"; sidebar_position:"left"; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:""; sidebar_title_f:"ss4"; sidebar_title_s:""; sidebar_title_c:"#FE9017"; sidebar_title2_f:"s2"; sidebar_title2_s:"1.2em"; sidebar_title2_c:"#826228"; sidebar_line_c:"#D3EB8B"; sidebar_link_c:"#858547"; sidebar_link_f_c:"#FE9017"; sidebar_link_v_c:"#8F9645"; date_title_f:""; date_title_s:""; date_title_c:"#826228"; post_title_f:"ss4"; post_title_s:"1.8em"; post_title_c:"#806432"; post_comment_bg_c:"#EFFDCC"; post_comment_c:"#826228"; prelude_c:"#C8E186"; footer_f:""; footer_s:"1em"; footer_c:"#FFFFFF"; footer_l_c:"#FFFFFF"; footer_bg_c:"#484432"',
    Rabbit:
      'body_bg_c:"#8F9645"; body_bg_g:"solid"; body_txt_f:"ss1"; body_txt_s:"1.2em"; body_txt_c:"#625D47"; body_line_height:"1.4em"; top_image:"rabbit"; blog_title_hide:"0"; blog_title_f:"ss1"; blog_title_s:"3.5em"; blog_title_c:"#DBDB9D"; blog_title_a:""; blog_title_p:"130:70"; body_link_c:"#646B10"; body_link_f_c:"#484C12"; body_link_v_c:"#919924"; sidebar_position:""; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:"#858547"; sidebar_title_f:""; sidebar_title_s:""; sidebar_title_c:"#8F9645"; sidebar_title2_f:"s2"; sidebar_title2_s:""; sidebar_title2_c:"#826228"; sidebar_line_c:"#95956B"; sidebar_link_c:"#858547"; sidebar_link_f_c:"#826228"; sidebar_link_v_c:"#8F9645"; date_title_f:"s2"; date_title_s:"1em"; date_title_c:"#826228"; post_title_f:"s2"; post_title_s:"1.6em"; post_title_c:"#806432"; post_comment_bg_c:"#D6DE91"; post_comment_c:"#826228"; prelude_c:"#484432"; footer_f:""; footer_s:"1em"; footer_c:"#A6AF50"; footer_l_c:"#DBDB9D"; footer_bg_c:"#484432"',
    'Rec Room':
      'body_bg_c:"#9B5E1C"; body_bg_g:"dark"; body_txt_f:"ss3"; body_txt_s:"1.1em"; body_txt_c:"#757575"; body_line_height:"1.5em"; top_image:"default"; blog_title_hide:"0"; blog_title_f:"s2"; blog_title_s:"3em"; blog_title_c:"#F9FAD6"; blog_title_a:""; blog_title_p:""; body_link_c:"#D1BF1D"; body_link_f_c:"#EEE168"; body_link_v_c:"#D1BF1D"; sidebar_position:"left"; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:""; sidebar_title_f:"ss3"; sidebar_title_s:"1.2em"; sidebar_title_c:"#689B9C"; sidebar_title2_f:"ss3"; sidebar_title2_s:"1.2em"; sidebar_title2_c:"#737300"; sidebar_line_c:"#E6E6CD"; sidebar_link_c:"#66664D"; sidebar_link_f_c:"#689B9C"; sidebar_link_v_c:"#66664D"; date_title_f:""; date_title_s:""; date_title_c:"#000000"; post_title_f:"s2"; post_title_s:"1.8em"; post_title_c:"#689B9C"; post_comment_bg_c:"#E4E4E2"; post_comment_c:""; prelude_c:"#3B2C16"; footer_f:""; footer_s:""; footer_c:"#FFFFFF"; footer_l_c:""; footer_bg_c:"#3B2C16"',
    Seville:
      'body_bg_c:"#B51A0D"; body_bg_g:"dark"; body_txt_f:"ss3"; body_txt_s:"1.1em"; body_txt_c:"#383839"; body_line_height:"1.5em"; top_image:"default"; blog_title_hide:"0"; blog_title_f:"s2"; blog_title_s:"3em"; blog_title_c:"#FFFFFF"; blog_title_a:""; blog_title_p:""; body_link_c:"#F18A32"; body_link_f_c:"#F1B232"; body_link_v_c:"#F18A32"; sidebar_position:"left"; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:""; sidebar_title_f:"ss3"; sidebar_title_s:"1.4em"; sidebar_title_c:"#97471C"; sidebar_title2_f:"ss3"; sidebar_title2_s:"1.2em"; sidebar_title2_c:"#737300"; sidebar_line_c:"#E6E6CD"; sidebar_link_c:"#6E6E72"; sidebar_link_f_c:"#F18A32"; sidebar_link_v_c:"#6E6E72"; date_title_f:""; date_title_s:""; date_title_c:"#97471C"; post_title_f:"s2"; post_title_s:"1.8em"; post_title_c:"#F18A32"; post_comment_bg_c:"#E4E4E2"; post_comment_c:""; prelude_c:"#381A1A"; footer_f:""; footer_s:""; footer_c:"#FFFFFF"; footer_l_c:""; footer_bg_c:"#381A1A"',
    'Spring Time':
      'body_bg_c:"#E0E0E0"; body_bg_g:"light"; body_txt_f:"ss1"; body_txt_s:"1.2em"; body_txt_c:"#6B6B6B"; body_line_height:"1.4em"; top_image:"light-trails-1"; blog_title_hide:"0"; blog_title_f:""; blog_title_s:"3.5em"; blog_title_c:"#9AC528"; blog_title_a:"center"; blog_title_p:""; body_link_c:"#279AC4"; body_link_f_c:"#6D8824"; body_link_v_c:"#279AC4"; sidebar_position:""; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:"#6B6B6B"; sidebar_title_f:""; sidebar_title_s:""; sidebar_title_c:"#8FB22F"; sidebar_title2_f:""; sidebar_title2_s:""; sidebar_title2_c:"#279AC4"; sidebar_line_c:"#FFD02C"; sidebar_link_c:"#6B6B6B"; sidebar_link_f_c:"#9AC528"; sidebar_link_v_c:"#6B6B6B"; date_title_f:""; date_title_s:"1em"; date_title_c:"#279AC4"; post_title_f:""; post_title_s:"1.7em"; post_title_c:"#9AC528"; post_comment_bg_c:"#FFFAD1"; post_comment_c:"#6B6B6B"; post_commentmy_bg_c:"#F5F9D9"; post_commentmy_c:"#6B6B6B"; prelude_c:"#EDEDED"; footer_f:""; footer_s:"1.2em"; footer_c:"#9AC528"; footer_l_c:"#6D8824"; footer_bg_c:"#E0E0E0"',
    Typo: 'body_bg_c:"#FFFFFF"; body_bg_g:"solid"; body_txt_f:"ss1"; body_txt_s:"1.2em"; body_txt_c:"#000000"; body_line_height:"1.4em"; top_image:"typo"; blog_title_hide:"0"; blog_title_f:"s2"; blog_title_s:"3.5em"; blog_title_c:"#B11508"; blog_title_a:"left"; blog_title_p:"140:50"; body_link_c:"#B11508"; body_link_f_c:"#000000"; body_link_v_c:"#4D4D4D"; sidebar_position:""; sidebar_text_f:""; sidebar_text_s:""; sidebar_text_c:"#000000"; sidebar_title_f:"s2"; sidebar_title_s:""; sidebar_title_c:"#B11508"; sidebar_title2_f:"s2"; sidebar_title2_s:""; sidebar_title2_c:"#000000"; sidebar_line_c:"#000000"; sidebar_link_c:"#000000"; sidebar_link_f_c:"#B11508"; sidebar_link_v_c:"#000000"; date_title_f:"s2"; date_title_s:"1em"; date_title_c:"#000000"; post_title_f:"s2"; post_title_s:"1.6em"; post_title_c:"#B11508"; post_comment_bg_c:"#FFFFFF"; post_comment_c:"#000000"; prelude_c:"#FFFFFF"; footer_f:""; footer_s:"1em"; footer_c:"#000000"; footer_l_c:"#B11508"; footer_bg_c:"#FFFFFF"',
  };

  // Hide main title management
  const hideTitleSet = (hide) => {
    for (const id of ['blog_title_f', 'blog_title_s', 'blog_title_c', 'blog_title_a', 'blog_title_p']) {
      const element = document.getElementById(id);
      if (element) {
        if (element.getAttribute('disabled') !== null && !hide) element.removeAttribute('disabled');
        else if (element.getAttribute('disabled') === null && hide) element.setAttribute('disabled', '');
      }
    }
  };
  const hide_title = document.getElementById('blog_title_hide');
  if (hide_title) {
    hideTitleSet(hide_title.checked);
    hide_title.addEventListener('click', () => {
      hideTitleSet(hide_title.checked);
    });
  }

  // Get style and apply it
  const applyBlowupValues = (code) => {
    code = code.replace('\n', '');

    const re = /(^| )(\w+):"([^"]*?)"(;|$)/g;
    const found = code.match(re);

    if (!(typeof found === 'object' && found.length > 0)) {
      return;
    }

    const count = found.length;
    const reg = /^(.+):"([^"]*)"(;?)\s*$/;

    for (let i = 0; i < count; i++) {
      const member = reg.exec(found[i]);
      if (member !== null) {
        const target = member[1].replace(' ', '');
        const value = member[2].replace(' ', '');
        const element = document.getElementById(target);
        if (element) {
          if (element.type === 'checkbox') {
            element.checked = value === '1';
            if (target === 'blog_title_hide' && hide_title) {
              hideTitleSet(hide_title.checked);
            }
          } else {
            if (element.type === 'color') element.value = value === '' ? '#000000' : value;
            else element.value = value;
          }
        }
      }
    }
  };

  // Upload form
  const top_image = document.getElementById('top_image');
  const uploader = document.getElementById('uploader');
  const image_preview = document.getElementById('image-preview');
  if (top_image && uploader) {
    uploader.style.display = top_image.value === 'custom' ? '' : 'none';
    top_image.addEventListener('change', (event) => {
      if (event.target.value === 'custom') {
        uploader.style.display = '';
        image_preview.setAttribute('src', `${dotclear.blowup_public_url}/page-t.png`);
        return;
      }
      uploader.style.display = 'none';
      const file = document.querySelector('#uploader input');
      if (file) file.value = '';
      image_preview.setAttribute('src', `${dotclear.blowup_theme_url}/alpha-img/page-t/${event.target.value}.png`);
    });
  }

  const theme_config = document.getElementById('theme_config');
  if (theme_config) {
    // Predefined styles
    const para = document.createElement('p');
    const styles_combo = document.createElement('select');
    styles_combo.id = 'style';
    styles_combo.title = dotclear.msg.predefined_style_title;
    styles_combo.append(dotclear.htmlToNode('<option value="">&nbsp;</option>'));
    styles_combo.append(dotclear.htmlToNode('<option value="none">none</option>'));
    for (const style in dotclear.blowup_styles) {
      const option = document.createElement('option');
      option.value = dotclear.blowup_styles[style];
      option.innerText = style;
      styles_combo.append(option);
    }
    para.appendChild(styles_combo);
    theme_config.after(para);
    theme_config.after(dotclear.htmlToNode(`<h4>${dotclear.msg.predefined_styles}</h4>`));

    styles_combo.addEventListener('change', (event) => {
      const form = event.target.closest('form');
      for (const select of form?.querySelectorAll('select')) {
        if (select !== event.target) select.value = '';
      }
      const extra_css = form?.querySelector('#extra_css');
      if (extra_css) extra_css.value = '';
      if (top_image) top_image.value = 'default';
      if (event.target.value !== 'none') {
        applyBlowupValues(event.target.value);
      }
      if (image_preview)
        image_preview.setAttribute('src', `${dotclear.blowup_theme_url}/alpha-img/page-t/${top_image.value}.png`);
    });
  }

  // Code import
  const export_content = document.querySelectorAll('#bu_export_content');
  dotclear.toggleWithLegend(document.getElementById('bu_export'), export_content);

  const apply_content = document.createElement('button');
  apply_content.innerText = dotclear.msg.apply_code;
  apply_content.addEventListener('click', (event) => {
    const code = document.getElementById('export_code');
    if (code.value !== '') {
      applyBlowupValues(code.value);
      if (image_preview)
        image_preview.setAttribute('src', `${dotclear.blowup_theme_url}/alpha-img/page-t/${top_image.value}.png`);
    }
    event?.preventDefault();
    event?.stopPropagation();
    return false;
  });
  export_content[0].append(apply_content);
});
