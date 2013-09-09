<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : null;
$start = !empty($_GET['start']) ? abs((integer) $_GET['start']) : 0;

if ($action == 'vacuum')
{
	try
	{
		$schema = dbSchema::init($core->con);
		$db_tables = $schema->getTables();
		
		foreach ($db_tables as $t) {
			if (strpos($t,$core->prefix) === 0) {
				$core->con->vacuum($t);
			}
		}
		http::redirect($p_url.'&vacuum=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}
elseif ($action == 'commentscount')
{
	try {
		$core->countAllComments();
		http::redirect($p_url.'&commentscount=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}
elseif ($action == 'empty_cache')
{
	try {
		$core->emptyTemplatesCache();
		http::redirect($p_url.'&empty_cache=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}
elseif ($action == 'log')
{
	try {
		$core->log->delLogs(null,true);
		http::redirect($p_url.'&delete_logs=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

?>
<html>
<head>
  <title><?php echo __('Maintenance'); ?></title>
</head>

<body>
<?php
	echo dcPage::breadcrumb(
		array(
			__('Plugins') => '',
			'<span class="page-title">'.__('Maintenance').'</span>' => ''
		));
?>

<?php
if (!empty($_GET['vacuum'])) {
	dcPage::success(__('Optimization successful.'));
}
if (!empty($_GET['commentscount'])) {
	dcPage::success(__('Comments and trackback counted.'));
}
if (!empty($_GET['empty_cache'])) {
	dcPage::success(__('Templates cache directory emptied.'));
}
if (!empty($_GET['delete_logs'])) {
	dcPage::success(__('Logs deleted.'));
}

if ($action == 'index' && !empty($_GET['indexposts']))
{
	$limit = 1000;
	echo '<p>'.sprintf(__('Indexing entry %d to %d.'),$start,$start+$limit).'</p>';
	
	$new_start = $core->indexAllPosts($start,$limit);
	
	if ($new_start)
	{
		$new_url = $p_url.'&action=index&indexposts=1&start='.$new_start;
		echo
		'<script type="text/javascript">'."\n".
		"//<![CDATA\n".
		"window.location = '".$new_url."'\n".
		"//]]>\n".
		'</script>'.
		'<noscript><p><a href="'.html::escapeURL($new_url).'">'.__('next').'</a></p></noscript>';
	}
	else
	{
		dcPage::message(__('Entries index done.'));
		echo '<p><a class="back" href="'.$p_url.'">'.__('Back').'</a></p>';
	}
}
elseif ($action == 'index' && !empty($_GET['indexcomments']))
{
	$limit = 1000;
	echo '<p>'.sprintf(__('Indexing comment %d to %d.'),$start,$start+$limit).'</p>';
	
	$new_start = $core->indexAllComments($start,$limit);
	
	if ($new_start)
	{
		$new_url = $p_url.'&action=index&indexcomments=1&start='.$new_start;
		echo
		'<script type="text/javascript">'."\n".
		"//<![CDATA\n".
		"window.location = '".$new_url."'\n".
		"//]]>\n".
		'</script>'.
		'<noscript><p><a href="'.html::escapeURL($new_url).'">'.__('next').'</a></p></noscript>';
	}
	else
	{
		dcPage::message(__('Comments index done.'));
		echo '<p><a class="back" href="'.$p_url.'">'.__('Back').'</a></p>';
	}
}
else
{
	echo
	'<div class="two-boxes">'.
	'<h3>'.__('Optimize database room').'</h3>'.
	'<form action="plugin.php" method="post">'.
	'<p><input type="submit" value="'.__('Vacuum tables').'" /> '.
	$core->formNonce().
	form::hidden(array('action'),'vacuum').
	form::hidden(array('p'),'maintenance').'</p>'.
	'</form></div>';
	
	echo
	'<div class="two-boxes">'.
	'<h3>'.__('Counters').'</h3>'.
	'<form action="plugin.php" method="post">'.
	'<p><input type="submit" value="'.__('Reset comments and ping counters').'" /> '.
	$core->formNonce().
	form::hidden(array('action'),'commentscount').
	form::hidden(array('p'),'maintenance').'</p>'.
	'</form></div>';
	
	echo
	'<div class="two-boxes">'.
	'<h3>'.__('Search engine index').'</h3>'.
	'<form action="plugin.php" method="get">'.
	'<p><input type="submit" name="indexposts" value="'.__('Index all posts').'" /> '.
	'<input type="submit" name="indexcomments" value="'.__('Index all comments').'" /> '.
	form::hidden(array('action'),'index').
	form::hidden(array('p'),'maintenance').'</p>'.
	'<p class="form-note info">'.__('This may take a very long time').'.</p>'.
	'</form></div>';
	
	echo
	'<div class="two-boxes">'.
	'<h3>'.__('Vacuum logs').'</h3>'.
	'<form action="plugin.php" method="post">'.
	'<p><input type="submit" value="'.__('Delete all logs').'" /> '.
	$core->formNonce().
	form::hidden(array('action'),'log').
	form::hidden(array('p'),'maintenance').'</p>'.
	'</form></div>';
	
	echo
	'<div class="two-boxes">'.
	'<h3>'.__('Empty templates cache directory').'</h3>'.
	'<form action="plugin.php" method="post">'.
	'<p><input type="submit" value="'.__('Empty directory').'" /> '.
	$core->formNonce().
	form::hidden(array('action'),'empty_cache').
	form::hidden(array('p'),'maintenance').'</p>'.
	'</form></div>';
}
dcPage::helpBlock('maintenance');
?>

</body>
</html>