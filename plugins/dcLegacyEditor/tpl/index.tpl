<html>
  <head>
    <title>dcLegacyEditor</title>
  </head>
  <body>
    <h2><?php echo html::escapeHTML($core->blog->name);?> &gt; dcLegacyEditor</h2>
    <?php if (!empty($message)):?>
    <p class="message"><?php echo $message;?></p>
    <?php endif;?>

    <?php if ($is_super_admin):?>
    <h3 class="hidden-if-js"><?php echo __('Settings');?></h3>
    <form action="<?php echo $p_url;?>" method="post" enctype="multipart/form-data">
      <div class="fieldset">
	<h3><?php echo __('Plugin activation');?></h3>
	<p>
	  <label class="classic" for="dclegacyeditor_active">
	    <?php echo form::checkbox('dclegacyeditor_active', 1, $dclegacyeditor_active);?>
	    <?php echo __('Enable dcLegacyEditor plugin');?>
	  </label>
	</p>
      </div>
      
      <p>
	<input type="hidden" name="p" value="dcLegacyEditor"/>
	<?php echo $core->formNonce();?>
	<input type="submit" name="saveconfig" value="<?php echo __('Save configuration');?>" />
      </p>
    </form>
    <?php endif;?>
    <?php dcPage::helpBlock('dcLegacyEditor');?>
  </body>
</html>
