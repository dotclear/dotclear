<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

require dirname(__FILE__).'/class.themeEditor.php';

$file_default = $file = array('c'=>null, 'w'=>false, 'type'=>null, 'f'=>null, 'default_file'=>false);

# Loading themes
$core->themes = new dcThemes($core);
$core->themes->loadModules($core->blog->themes_path,null);
$T = $core->themes->getModules($core->blog->settings->system->theme);
$o = new dcThemeEditor($core);

try
{
	try
	{
		if (!empty($_REQUEST['tpl'])) {
			$file = $o->getFileContent('tpl',$_REQUEST['tpl']);
		} elseif (!empty($_REQUEST['css'])) {
			$file = $o->getFileContent('css',$_REQUEST['css']);
		} elseif (!empty($_REQUEST['js'])) {
			$file = $o->getFileContent('js',$_REQUEST['js']);
		}
	}
	catch (Exception $e)
	{
		$file = $file_default;
		throw $e;
	}
	
	# Write file
	if (!empty($_POST['write']))
	{
		$file['c'] = $_POST['file_content'];
		$o->writeFile($file['type'],$file['f'],$file['c']);
	}
}
catch (Exception $e)
{
	$core->error->add($e->getMessage());
}
?>

<html>
<head>
  <title><?php echo __('Theme Editor'); ?></title>
  <link rel="stylesheet" type="text/css" href="index.php?pf=themeEditor/style.css" />
  <script type="text/javascript">
  //<![CDATA[
  <?php echo dcPage::jsVar('dotclear.msg.saving_document',__("Saving document...")); ?>
  <?php echo dcPage::jsVar('dotclear.msg.document_saved',__("Document saved")); ?>
  <?php echo dcPage::jsVar('dotclear.msg.error_occurred',__("An error occurred:")); ?>
  //]]>
  </script>
  <script type="text/javascript" src="index.php?pf=themeEditor/script.js"></script>
</head>

<body>
<?php echo '<h2>'.html::escapeHTML($core->blog->name).
' &rsaquo; <a href="blog_theme.php">'.__('Blog appearance').'</a> &rsaquo; '.__('Theme Editor').'</h2>'; ?>

<p><strong><?php echo sprintf(__('Your current theme on this blog is "%s".'),html::escapeHTML($T['name'])); ?></strong></p>

<?php if ($core->blog->settings->system->theme == 'default') { ?>
	<div class="error"><p><?php echo __("You can't edit default theme."); ?></p></div>
	</body></html>
<?php } ?>

<div id="file-box">
<div id="file-editor">
<?php
if ($file['c'] === null)
{
	echo '<p>'.__('Please select a file to edit.').'</p>';
}
else
{
	echo
	'<form id="file-form" action="'.$p_url.'" method="post">'.
	'<fieldset><legend>'.__('File editor').'</legend>'.
	'<p><label for="file_content">'.sprintf(__('Editing file %s'),'<strong>'.$file['f']).'</strong></label></p>'.
	'<p>'.form::textarea('file_content',72,25,html::escapeHTML($file['c']),'maximal','',!$file['w']).'</p>';
	
	if ($file['w'])
	{
		echo
		'<p><input type="submit" name="write" value="'.__('save').' (s)" accesskey="s" /> '.
		$core->formNonce().
		($file['type'] ? form::hidden(array($file['type']),$file['f']) : '').
		'</p>';
	}
	else
	{
		echo '<p>'.__('This file is not writable. Please check your theme files permissions.').'</p>';
	}
	
	echo
	'</fieldset></form>';
}
?>
</div>
</div>

<div id="file-chooser">
<h3><?php echo __('Templates files'); ?></h3>
<?php echo $o->filesList('tpl','<a href="'.$p_url.'&amp;tpl=%2$s" class="tpl-link">%1$s</a>'); ?>

<h3><?php echo __('CSS files'); ?></h3>
<?php echo $o->filesList('css','<a href="'.$p_url.'&amp;css=%2$s" class="css-link">%1$s</a>'); ?>

<h3><?php echo __('JavaScript files'); ?></h3>
<?php echo $o->filesList('js','<a href="'.$p_url.'&amp;js=%2$s" class="js-link">%1$s</a>'); ?>
</div>

<?php dcPage::helpBlock('themeEditor'); ?>
</body>
</html>