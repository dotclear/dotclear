<?php
/**
 * @file
 * @brief     The module backend manage contents
 * @ingroup   dcLegacyEditor
 *
 * @package   Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
echo \Dotclear\Core\Backend\Page::breadcrumb([__('Plugins') => '', __('dcLegacyEditor') => '']) . \Dotclear\Core\Backend\Notices::getNotices(); ?>

<?php if (\Dotclear\App::backend()->editor_is_admin): ?>
  <h3 class="hidden-if-js"><?php echo __('Settings'); ?></h3>
  <form action="<?php echo \Dotclear\App::backend()->getPageURL(); ?>" method="post" enctype="multipart/form-data">
    <fieldset>
      <legend><?php echo __('Plugin activation'); ?></legend>
      <p>
        <label class="classic" for="dclegacyeditor_active">
          <?php echo form::checkbox('dclegacyeditor_active', 1, \Dotclear\App::backend()->editor_std_active); ?>
          <?php echo __('Enable standard editor plugin'); ?>
        </label>
      </p>
    </fieldset>
    <fieldset>
      <legend><?php echo __('Plugin settings'); ?></legend>
      <p>
        <label class="classic" for="dclegacyeditor_dynamic">
          <?php echo form::checkbox('dclegacyeditor_dynamic', 1, \Dotclear\App::backend()->editor_std_dynamic); ?>
          <?php echo __('Adjust height of input area during editing'); ?>
        </label>
      </p>
    </fieldset>

    <p>
    <input type="hidden" name="p" value="dcLegacyEditor">
    <?php echo \Dotclear\App::nonce()->getFormNonce(); ?>
    <input type="submit" name="saveconfig" value="<?php echo __('Save configuration'); ?>">
    <input type="button" value="<?php echo  __('Back'); ?>" class="go-back reset hidden-if-no-js">
    </p>
  </form>
<?php endif;?>

<?php \Dotclear\Core\Backend\Page::helpBlock('dcLegacyEditor');?>
