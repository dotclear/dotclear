<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }
dcPage::check('admin');

dcAntispam::initFilters();
$filters = dcAntispam::$filters->getFilters();

$page_name = __('Antispam');
$filter_gui = false;
$default_tab = null;

try
{
	# Show filter configuration GUI
	if (!empty($_GET['f']))
	{
		if (!isset($filters[$_GET['f']])) {
			throw new Exception(__('Filter does not exist.'));
		}

		if (!$filters[$_GET['f']]->hasGUI()) {
			throw new Exception(__('Filter has no user interface.'));
		}

		$filter = $filters[$_GET['f']];
		$filter_gui = $filter->gui($filter->guiURL());
	}

	# Remove all spam
	if (!empty($_POST['delete_all']))
	{
		$ts = dt::str('%Y-%m-%d %H:%M:%S',$_POST['ts'],$core->blog->settings->system->blog_timezone);

		dcAntispam::delAllSpam($core,$ts);
		http::redirect($p_url.'&del=1');
	}

	# Update filters
	if (isset($_POST['filters_upd']))
	{
		$filters_opt = array();
		$i = 0;
		foreach ($filters as $fid => $f) {
			$filters_opt[$fid] = array(false,$i);
			$i++;
		}

		# Enable active filters
		if (isset($_POST['filters_active']) && is_array($_POST['filters_active'])) {
			foreach ($_POST['filters_active'] as $v) {
				$filters_opt[$v][0] = true;
			}
		}

		# Order filters
		if (!empty($_POST['f_order']) && empty($_POST['filters_order']))
		{
			$order = $_POST['f_order'];
			asort($order);
			$order = array_keys($order);
		}
		elseif (!empty($_POST['filters_order']))
		{
			$order = explode(',',trim($_POST['filters_order'],','));
		}

		if (isset($order)) {
			foreach ($order as $i => $f) {
				$filters_opt[$f][1] = $i;
			}
		}

		# Set auto delete flag
		if (isset($_POST['filters_auto_del']) && is_array($_POST['filters_auto_del'])) {
			foreach ($_POST['filters_auto_del'] as $v) {
				$filters_opt[$v][2] = true;
			}
		}

		dcAntispam::$filters->saveFilterOpts($filters_opt);
		http::redirect($p_url.'&upd=1');
	}
}
catch (Exception $e)
{
	$core->error->add($e->getMessage());
}
?>
<html>
<head>
  <title><?php echo $page_name; ?></title>
  <?php
  echo
  dcPage::jsToolMan().
  dcPage::jsPageTabs($default_tab).
  dcPage::jsLoad('index.php?pf=antispam/antispam.js');
  ?>
  <link rel="stylesheet" type="text/css" href="index.php?pf=antispam/style.css" />
</head>
<body>
<?php
echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; '.$page_name.'</h2>';

if ($filter_gui !== false)
{
	echo '<p><a href="'.$p_url.'">'.__('Return to filters').'</a></p>';
	printf('<h3>'.__('%s configuration').'</h3>',$filter->name);

	echo $filter_gui;
}
else
{
	# Information
	$spam_count = dcAntispam::countSpam($core);
	$published_count = dcAntispam::countPublishedComments($core);
	$moderationTTL = $core->blog->settings->antispam->antispam_moderation_ttl;

	echo
	'<form action="'.$p_url.'" method="post">'.
	'<fieldset><legend>'.__('Information').'</legend>';

	if (!empty($_GET['del'])) {
		echo '<p class="message">'.__('Spam comments have been successfully deleted.').'</p>';
	}

	echo
	'<ul class="spaminfo">'.
	'<li class="spamcount"><a href="comments.php?status=-2">'.__('Junk comments:').'</a> '.
	'<strong>'.$spam_count.'</strong></li>'.
	'<li class="hamcount"><a href="comments.php?status=1">'.__('Published comments:').'</a> '.
	$published_count.'</li>'.
	'</ul>';

	if ($spam_count > 0)
	{
		echo
		'<p>'.$core->formNonce().
		form::hidden('ts',time()).
		'<input name="delete_all" class="delete" type="submit" value="'.__('Delete all spams').'" /></p>';
	}
	if ($moderationTTL != null && $moderationTTL >=0) {
		echo '<p>'.sprintf(__('All spam comments older than %s day(s) will be automatically deleted.'), $moderationTTL).'</p>';
	}
	echo '</fieldset></form>';


	# Filters
	echo
	'<form action="'.$p_url.'" method="post">'.
	'<fieldset><legend>'.__('Available spam filters').'</legend>';

	if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Filters configuration has been successfully saved.').'</p>';
	}

	echo
	'<table class="dragable">'.
	'<thead><tr>'.
	'<th>'.__('Order').'</th>'.
	'<th>'.__('Active').'</th>'.
	'<th>'.__('Auto Del.').'</th>'.
	'<th class="nowrap">'.__('Filter name').'</th>'.
	'<th colspan="2">'.__('Description').'</th>'.
	'</tr></thead>'.
	'<tbody id="filters-list" >';

	$i = 0;
	foreach ($filters as $fid => $f)
	{
		$gui_link = '&nbsp;';
		if ($f->hasGUI()) {
			$gui_link =
			'<a href="'.html::escapeHTML($f->guiURL()).'">'.
			'<img src="images/edit-mini.png" alt="'.__('Filter configuration').'" '.
			'title="'.__('Filter configuration').'" /></a>';
		}

		echo
		'<tr class="line'.($f->active ? '' : ' offline').'" id="f_'.$fid.'">'.
		'<td class="handle">'.form::field(array('f_order['.$fid.']'),2,5,(string) $i, '', '', false, 'title="'.__('position').'"').'</td>'.
		'<td class="nowrap">'.form::checkbox(array('filters_active[]'),$fid,$f->active, '', '', false, 'title="'.__('Active').'"').'</td>'.
		'<td class="nowrap">'.form::checkbox(array('filters_auto_del[]'),$fid,$f->auto_delete, '', '', false, 'title="'.__('Auto Del.').'"').'</td>'.
		'<td class="nowrap">'.$f->name.'</td>'.
		'<td class="maximal">'.$f->description.'</td>'.
		'<td class="status">'.$gui_link.'</td>'.
		'</tr>';
		$i++;
	}
	echo
	'</tbody></table>'.
	'<p>'.form::hidden('filters_order','').
	$core->formNonce().
	'<input type="submit" name="filters_upd" value="'.__('Save').'" /></p>'.
	'</fieldset></form>';


	# Syndication
	if (DC_ADMIN_URL)
	{
		$ham_feed = $core->blog->url.$core->url->getBase('hamfeed').'/'.$code = dcAntispam::getUserCode($core);
		$spam_feed = $core->blog->url.$core->url->getBase('spamfeed').'/'.$code = dcAntispam::getUserCode($core);

		echo
		'<fieldset><legend>'.__('Syndication').'</legend>'.
		'<ul class="spaminfo">'.
		'<li class="feed"><a href="'.$spam_feed.'">'.__('Junk comments RSS feed').'</a></li>'.
		'<li class="feed"><a href="'.$ham_feed.'">'.__('Published comments RSS feed').'</a></li>'.
		'</ul>'.
		'</fieldset>';
	}
}
?>

</body>
</html>

