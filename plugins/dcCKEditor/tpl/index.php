<html>
  <head>
    <title>
      dcCKEditor
    </title>
  </head>
  <body>
    <?php echo dcPage::breadcrumb([__('Plugins') => '', __('dcCKEditor') => '']) . dcPage::notices(); ?>
    <?php if ($is_admin): /* @phpstan-ignore-line */ ?>
    <h3 class="hidden-if-js">
      <?php echo __('Settings'); ?>
    </h3>
    <form action="<?php echo $p_url; ?>" enctype="multipart/form-data" method="post">
      <div class="fieldset">
        <h3>
          <?php echo __('Plugin activation'); ?>
        </h3>
        <p>
          <label class="classic" for="dcckeditor_active">
            <?php echo form::checkbox('dcckeditor_active', 1, $dcckeditor_active); /* @phpstan-ignore-line */ ?>
            <?php echo __('Enable dcCKEditor plugin'); ?>
          </label>
        </p>
      </div>
      <?php if ($dcckeditor_active): /* @phpstan-ignore-line */ ?>
      <div class="fieldset">
        <h3>
          <?php echo __('Options'); ?>
        </h3>
        <p>
          <?php echo form::checkbox('dcckeditor_alignment_buttons', 1, $dcckeditor_alignment_buttons); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_alignment_buttons">
            <?php echo __('Add alignment buttons'); ?>
          </label>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_list_buttons', 1, $dcckeditor_list_buttons); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_list_buttons">
            <?php echo __('Add lists buttons'); ?>
          </label>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_textcolor_button', 1, $dcckeditor_textcolor_button); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_textcolor_button">
            <?php echo __('Add text color button'); ?>
          </label>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_background_textcolor_button', 1, $dcckeditor_background_textcolor_button); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_background_textcolor_button">
            <?php echo __('Add background text color button'); ?>
          </label>
        </p>
        <p class="area">
          <label for="dcckeditor_custom_color_list">
            <?php echo __('Custom colors list:'); ?>
          </label>
          <?php echo form::textarea('dcckeditor_custom_color_list', 60, 5, ['default' => html::escapeHTML($dcckeditor_custom_color_list)]); /* @phpstan-ignore-line */ ?>
        </p>
        <p class="clear form-note">
          <?php echo __('Add colors without # separated by a comma.'); ?><br />
          <?php echo __('Leave empty to use the default palette:'); ?>
          <blockquote><pre><code>1abc9c,2ecc71,3498db,9b59b6,4e5f70,f1c40f,16a085,27ae60,2980b9,8e44ad,2c3e50,f39c12,e67e22,e74c3c,ecf0f1,95a5a6,dddddd,ffffff,d35400,c0392b,bdc3c7,7f8c8d,999999,000000</code></pre></blockquote>
          <?php echo __('Example of custom color list:'); ?>
          <blockquote><pre><code>000,800000,8b4513,2f4f4f,008080,000080,4b0082,696969,b22222,a52a2a,daa520,006400,40e0d0,0000cd,800080,808080,f00,ff8c00,ffd700,008000,0ff,00f,ee82ee,a9a9a9,ffa07a,ffa500,ffff00,00ff00,afeeee,add8e6,dda0dd,d3d3d3,fff0f5,faebd7,ffffe0,f0fff0,f0ffff,f0f8ff,e6e6fa,fff</code></pre></blockquote>
        </p>
        <p class="field">
          <label for="dcckeditor_colors_per_row">
            <?php echo __('Colors per row in palette:') . ' '; ?>
          </label>
          <?php echo form::number('dcckeditor_colors_per_row', ['min' => 4, 'max' => 16, 'default' => $dcckeditor_colors_per_row]);  /* @phpstan-ignore-line */ ?>
        </p>
        <p class="clear form-note">
          <?php echo __('Leave empty to use default (6)'); ?>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_cancollapse_button', 1, $dcckeditor_cancollapse_button); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_cancollapse_button">
            <?php echo __('Add collapse button'); ?>
          </label>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_format_select', 1, $dcckeditor_format_select); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_format_select">
            <?php echo __('Add format selection'); ?>
          </label>
        </p>
        <p>
          <label class="classic" for="dcckeditor_format_tags">
            <?php echo __('Custom formats'); ?>
          </label>
          <?php echo form::field('dcckeditor_format_tags', 100, 255, $dcckeditor_format_tags); /* @phpstan-ignore-line */ ?>
        </p>
        <p class="clear form-note">
          <?php echo __('Default formats are p;h1;h2;h3;h4;h5;h6;pre;address'); ?>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_table_button', 1, $dcckeditor_table_button); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_table_button">
            <?php echo __('Add table button'); ?>
          </label>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_clipboard_buttons', 1, $dcckeditor_clipboard_buttons); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_clipboard_buttons">
            <?php echo __('Add clipboard buttons'); ?>
          </label>
        </p>
        <p class="clear form-note">
          <?php echo __('Copy, Paste, Paste Text, Paste from Word'); ?>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_action_buttons', 1, $dcckeditor_action_buttons); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_action_buttons">
            <?php echo __('Add undo/redo buttons'); ?>
          </label>
        </p>
        <p>
          <?php echo form::checkbox('dcckeditor_disable_native_spellchecker', 1, $dcckeditor_disable_native_spellchecker); /* @phpstan-ignore-line */ ?>
          <label class="classic" for="dcckeditor_disable_native_spellchecker">
            <?php echo __('Disables the built-in spell checker if the browser provides one'); ?>
          </label>
        </p>
      </div>
      <?php endif;?>
      <p>
        <input name="p" type="hidden" value="dcCKEditor"/>
        <?php echo $core->
    formNonce(); ?>
        <input name="saveconfig" type="submit" value="<?php echo __('Save configuration'); ?>"/>
        <input type="button" value="<?php echo  __('Cancel'); ?>" class="go-back reset hidden-if-no-js" />
      </p>
    </form>
    <?php endif;?>
    <?php dcPage::helpBlock('dcCKEditor');?>
  </body>
</html>
