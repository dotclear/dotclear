<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

include dirname(__FILE__).'/_default_widgets.php';

# Loading navigation, extra widgets and custom widgets
$widgets_nav = null;
if ($core->blog->settings->widgets->widgets_nav) {
	$widgets_nav = dcWidgets::load($core->blog->settings->widgets->widgets_nav);
}
$widgets_extra = null;
if ($core->blog->settings->widgets->widgets_extra) {
	$widgets_extra = dcWidgets::load($core->blog->settings->widgets->widgets_extra);
}
$widgets_custom = null;
if ($core->blog->settings->widgets->widgets_custom) {
	$widgets_custom = dcWidgets::load($core->blog->settings->widgets->widgets_custom);
}

$append_combo = array(
	'-' => 0,
	__('navigation') => 'nav',
	__('extra') => 'extra',
	__('custom') => 'custom'
);

# Adding widgets to sidebars
if (!empty($_POST['append']) && is_array($_POST['addw']))
{
	# Filter selection
	$addw = array();
	foreach ($_POST['addw'] as $k => $v) {
		if (($v == 'extra' || $v == 'nav' || $v == 'custom') && $__widgets->{$k} !== null ) {
			$addw[$k] = $v;
		}
	}
	
	# Append widgets
	if (!empty($addw))
	{
		if (!($widgets_nav instanceof dcWidgets)) {
			$widgets_nav = new dcWidgets;
		}
		if (!($widgets_extra instanceof dcWidgets)) {
			$widgets_extra = new dcWidgets();
		}
		if (!($widgets_custom instanceof dcWidgets)) {
			$widgets_custom = new dcWidgets();
		}
		
		foreach ($addw as $k => $v)
		{
			switch ($v) {
				case 'nav':
					$widgets_nav->append($__widgets->{$k});
					break;
				case 'extra':
					$widgets_extra->append($__widgets->{$k});
					break;
				case 'custom':
					$widgets_custom->append($__widgets->{$k});
					break;
			}
		}
		
		try {
			$core->blog->settings->addNamespace('widgets');
			$core->blog->settings->widgets->put('widgets_nav',$widgets_nav->store());
			$core->blog->settings->widgets->put('widgets_extra',$widgets_extra->store());
			$core->blog->settings->widgets->put('widgets_custom',$widgets_custom->store());
			$core->blog->triggerBlog();
			http::redirect($p_url);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
}

# Update sidebars
if (!empty($_POST['wup']))
{
	if (!isset($_POST['w']) || !is_array($_POST['w'])) {
		$_POST['w'] = array();
	}
	
	try
	{
		# Removing mark as _rem widgets
		foreach ($_POST['w'] as $nsid => $nsw) {
			foreach ($nsw as $i => $v) {
				if (!empty($v['_rem'])) {
					unset($_POST['w'][$nsid][$i]);
					continue;
				}
			}
		}
		
		if (!isset($_POST['w']['nav'])) {
			$_POST['w']['nav'] = array();
		}
		if (!isset($_POST['w']['extra'])) {
			$_POST['w']['extra'] = array();
		}
		if (!isset($_POST['w']['custom'])) {
			$_POST['w']['custom'] = array();
		}
		
		$widgets_nav = dcWidgets::loadArray($_POST['w']['nav'],$__widgets);
		$widgets_extra = dcWidgets::loadArray($_POST['w']['extra'],$__widgets);
		$widgets_custom = dcWidgets::loadArray($_POST['w']['custom'],$__widgets);
		
		$core->blog->settings->addNamespace('widgets');
		$core->blog->settings->widgets->put('widgets_nav',$widgets_nav->store());
		$core->blog->settings->widgets->put('widgets_extra',$widgets_extra->store());
		$core->blog->settings->widgets->put('widgets_custom',$widgets_custom->store());
		$core->blog->triggerBlog();
		
		http::redirect($p_url);
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}
elseif (!empty($_POST['wreset']))
{
	try
	{
		$core->blog->settings->addNamespace('widgets');
		$core->blog->settings->widgets->put('widgets_nav','');
		$core->blog->settings->widgets->put('widgets_extra','');
		$core->blog->settings->widgets->put('widgets_custom','');
		$core->blog->triggerBlog();
		
		http::redirect($p_url);
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}
?>
<html>
<head>
  <title><?php echo __('Widgets'); ?></title>
  <style type="text/css">
  <?php echo file_get_contents(dirname(__FILE__).'/style.css'); ?>
  </style>
  <script type="text/javascript" src="js/tool-man/core.js"></script>
  <script type="text/javascript" src="js/tool-man/events.js"></script>
  <script type="text/javascript" src="js/tool-man/css.js"></script>
  <script type="text/javascript" src="js/tool-man/coordinates.js"></script>
  <script type="text/javascript" src="js/tool-man/drag.js"></script>
  <?php 
	$core->auth->user_prefs->addWorkspace('accessibility'); 
	$user_dm_nodragdrop = $core->auth->user_prefs->accessibility->nodragdrop;
  ?>
  <?php if (!$user_dm_nodragdrop) : ?>
  <script type="text/javascript" src="index.php?pf=widgets/dragdrop.js"></script>
  <?php endif; ?>
  <script type="text/javascript" src="index.php?pf=widgets/widgets.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  <?php echo dcPage::jsVar('dotclear.msg.confirm_widgets_reset',
  	__('Are you sure you want to reset sidebars?')); ?>
  //]]>
  </script>
  <?php echo(dcPage::jsConfirmClose('sidebarsWidgets')); ?>
</head>
<body>
<?php
echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Widgets').'</span></h2>';

# All widgets
echo
'<form id="listWidgets" action="'.$p_url.'" method="post"  class="widgets">'.
'<fieldset><legend>'.__('Available widgets').'</legend>'.
'<div id="widgets">';

$j = 0;
foreach ($__widgets->elements(true) as $w) {
	echo
	'<div>'.form::hidden(array('w[void][0][id]'),html::escapeHTML($w->id())).
	'<p class="widget-name">'.form::field(array('w[void][0][order]'),2,3,0,'hideControl').' '.
	$w->name().'</p>'.
	'<p class="js-remove"><label class="classic">'.__('Append to:').' '.
	form::combo(array('addw['.$w->id().']'),$append_combo).'</label></p>'.
	'<div class="widgetSettings">'.$w->formSettings('w[void][0]',$j).'</div>'.
	'</div>';
	$j++;
}

echo
'</div>'.
'</fieldset>'.
'<p><input type="submit" class="js-remove" name="append" value="'.__('Add widgets to sidebars').'" />'.
$core->formNonce().'</p>'.
'</form>';

echo '<form id="sidebarsWidgets" action="'.$p_url.'" method="post">';
# Nav sidebar
echo
'<div id="sidebarNav" class="widgets">'.
sidebarWidgets('dndnav',__('Navigation sidebar'),$widgets_nav,'nav',$__default_widgets['nav'],$j).
'</div>';

# Extra sidebar
echo
'<div id="sidebarExtra" class="widgets">'.
sidebarWidgets('dndextra',__('Extra sidebar'),$widgets_extra,'extra',$__default_widgets['extra'],$j).
'</div>';

# Custom sidebar
echo
'<div id="sidebarCustom" class="widgets">'.
sidebarWidgets('dndcustom',__('Custom sidebar'),$widgets_custom,'custom',$__default_widgets['custom'],$j).
'</div>';

echo
'<p id="sidebarsControl">'.
$core->formNonce().
'<input type="submit" name="wup" value="'.__('Update sidebars').'" /> '.
'<input type="submit" class="reset" name="wreset" value="'.__('Reset sidebars').'" /></p>'.
'</form>';

$widget_elements = new stdClass;
$widget_elements->content =
'<h3 class="clear">'.__('Use of widgets').'</h3>'.
'<p>'.__('Widgets may be used to add various blocks of content to be displayed on your public pages. To add a widget, '.
'drag it from the Available widgets list on the left to one of the sidebars on the right of this page. You can order '.
'your widgets in a sidebar by dragging them up or down. You must update sidebars to apply your changes.').'</p>'.
'<p>'.__('Once included in a sidebar, widgets have configuration options that you can reach by clicking on the + sign next '.
'to their name.').'</p>'.
'<p>'.__('Reset sidebars to get back to default widgets installation.').'</p>'.
'<h3 class="clear">'.__('Widget templates tags').'</h3>'.
'<div id="widgets-tpl">'.
'<p>'.__('If you are allowed to edit your theme templates, you can directly add widgets as '.
'templates tags, with their own configuration.').'</p>'.
'<p>'.__('To add a widget in your template, you need to write code like this:').'</p>'.
'<pre>&lt;tpl:Widget id="<strong>'.__('Widget ID').'</strong>"&gt;
  &lt;setting name="<strong>'.__('Setting name').'</strong>"&gt;<strong>'.__('Setting value').'</strong>&lt;/setting&gt;
  ...
&lt;/tpl:Widget&gt;</pre>'.
'<p>'.__('Here are the following available widgets for your blog:').'</p>';

$widget_elements->content .= '<dl>';
foreach ($__widgets->elements() as $w)
{
	$widget_elements->content .=
	'<dt><strong>'.html::escapeHTML($w->name()).'</strong> ('.
	__('Widget ID:').' <strong>'.html::escapeHTML($w->id()).'</strong>)</dt>'.
	'<dd>';
	
	$w_settings = $w->settings();
	if (empty($w_settings))
	{
		$widget_elements->content .= '<p>'.__('No setting for this widget').'</p>';
	}
	else
	{
		$widget_elements->content .= '<ul>';
		foreach ($w->settings() as $n => $s)
		{
			switch ($s['type']) {
				case 'check':
					$s_type = '0|1';
					break;
				case 'combo':
					$s_type = implode('|',$s['options']);
					break;
				case 'text':
				case 'textarea':
				default:
					$s_type = 'text';
					break;
			}
			
			$widget_elements->content .=
			'<li>'.
			__('Setting name:').' <strong>'.html::escapeHTML($n).'</strong>'.
			' ('.$s_type.')'.
			'</li>';
		}
		$widget_elements->content .= '</ul>';
	}
	$widget_elements->content .= '</dd>';
}
$widget_elements->content .= '</dl></div>';

dcPage::helpBlock($widget_elements);

function sidebarWidgets($id,$title,$widgets,$pr,$default_widgets,&$j)
{
	$res = '<fieldset><legend>'.$title.'</legend><div id="'.$id.'">';
	
	if (!($widgets instanceof dcWidgets))
	{
		$widgets = $default_widgets;
	}
	
	if ($widgets->isEmpty()) {
		$res .= '<p class="empty-widgets">'.__('No widget.').'</p>';
	}
	
	$i = 0;
	foreach ($widgets->elements() as $w)
	{
		$iname = 'w['.$pr.']['.$i.']';
		
		$res .=
		'<div>'.form::hidden(array($iname.'[id]'),html::escapeHTML($w->id())).
		'<p class="widget-name">'.form::field(array($iname.'[order]'),2,3,(string) $i,'js-hide','',0,'title="'.__('order').'"').' '.
		$w->name().'</p>'.
		'<p class="removeWidget js-remove"><label class="classic">'.
		form::checkbox(array($iname.'[_rem]'),'1',0).' '.__('Remove widget').
		'</label></p>'.
		'<div class="widgetSettings">'.$w->formSettings($iname,&$j).'</div>'.
		'</div>';
		
		$i++;
		$j++;
	}
	
	$res .= '</div></fieldset>';
	
	return $res;
}
?>
</body>
</html>