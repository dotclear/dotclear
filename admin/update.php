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

require dirname(__FILE__).'/../inc/admin/prepend.php';

if (!defined('DC_BACKUP_PATH')) {
	define('DC_BACKUP_PATH',DC_ROOT);
}

dcPage::checkSuper();

if (!is_readable(DC_DIGESTS)) {
	dcPage::open(__('Dotclear update'));
	echo '<h2>Access denied</h2>';
	dcPage::close();
	exit;
}

$updater = new dcUpdate(DC_UPDATE_URL,'dotclear',DC_UPDATE_VERSION,DC_TPL_CACHE.'/versions');
$new_v = $updater->check(DC_VERSION);
$zip_file = $new_v ? DC_BACKUP_PATH.'/'.basename($updater->getFileURL()) : '';
$version_info = $new_v ? $updater->getInfoURL() : '';

# Hide "update me" message
if (!empty($_GET['hide_msg'])) {
	$updater->setNotify(false);
	http::redirect('index.php');
}

$p_url = 'update.php';

$step = isset($_GET['step']) ? $_GET['step'] : '';
$step = in_array($step,array('check','download','backup','unzip')) ? $step : '';

$archives = array();
foreach (files::scanDir(DC_BACKUP_PATH) as $v) {
	if (preg_match('/backup-([0-9A-Za-z\.-]+).zip/',$v)) {
		$archives[] = $v;
	}
}

# Revert or delete backup file
if (!empty($_POST['backup_file']) && in_array($_POST['backup_file'],$archives))
{
	$b_file = $_POST['backup_file'];
	
	try
	{
		if (!empty($_POST['b_del']))
		{
			if (!@unlink(DC_BACKUP_PATH.'/'.$b_file)) {
				throw new Exception(sprintf(__('Unable to delete file %s'),html::escapeHTML($b_file)));
			}
			http::redirect($p_url);
		}
		
		if (!empty($_POST['b_revert']))
		{
			$zip = new fileUnzip(DC_BACKUP_PATH.'/'.$b_file);
			$zip->unzipAll(DC_BACKUP_PATH.'/');
			@unlink(DC_BACKUP_PATH.'/'.$b_file);
			http::redirect($p_url);
		}
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

# Upgrade process
if ($new_v && $step)
{
	try
	{
		$updater->setForcedFiles('inc/digests');
		
		switch ($step)
		{
			case 'check':
				$updater->checkIntegrity(DC_ROOT.'/inc/digests',DC_ROOT);
				http::redirect($p_url.'?step=download');
				break;
			case 'download':
				$updater->download($zip_file);
				if (!$updater->checkDownload($zip_file)) {
					throw new Exception(
						sprintf(__('Downloaded Dotclear archive seems to be corrupted. '.
						'Try <a %s>download it</a> again.'),'href="'.$p_url.'?step=download"')
					);
				}
				http::redirect($p_url.'?step=backup');
				break;
			case 'backup':
				$updater->backup(
					$zip_file, 'dotclear/inc/digests',
					DC_ROOT, DC_ROOT.'/inc/digests',
					DC_BACKUP_PATH.'/backup-'.DC_VERSION.'.zip'
				);
				http::redirect($p_url.'?step=unzip');
				break;
			case 'unzip':
				$updater->performUpgrade(
					$zip_file, 'dotclear/inc/digests', 'dotclear',
					DC_ROOT, DC_ROOT.'/inc/digests'
				);
				break;
		}
	}
	catch (Exception $e)
	{
		$msg = $e->getMessage();
		
		if ($e->getCode() == dcUpdate::ERR_FILES_CHANGED)
		{
			$msg =
			__('The following files of your Dotclear installation '.
			'have been modified so we won\'t try to update your installation. '.
			'Please try to <a href="http://dotclear.org/download">update manually</a>.');
		}
		elseif ($e->getCode() == dcUpdate::ERR_FILES_UNREADABLE)
		{
			$msg =
			sprintf(__('The following files of your Dotclear installation are not readable. '.
			'Please fix this or try to make a backup file named %s manually.'),
			'<strong>backup-'.DC_VERSION.'.zip</strong>');
		}
		elseif ($e->getCode() == dcUpdate::ERR_FILES_UNWRITALBE)
		{
			$msg =
			__('The following files of your Dotclear installation cannot be written. '.
			'Please fix this or try to <a href="http://dotclear.org/download">update manually</a>.');
		}
		
		if (isset($e->bad_files)) {
			$msg .=
			'<ul><li><strong>'.
			implode('</strong></li><li><strong>',$e->bad_files).
			'</strong></li></ul>';
		}
		
		$core->error->add($msg);
		
		$core->callBehavior('adminDCUpdateException',$e);
	}
}

/* DISPLAY Main page
-------------------------------------------------------- */
dcPage::open(__('Dotclear update'));

if (!$core->error->flag()) {
	echo '<h2>'.__('Dotclear update').'</h2>';
}

if (!$step)
{
	if (empty($new_v))
	{
		echo '<p><strong>'.__('No newer Dotclear version available.').'</strong></p>';
	}
	else
	{
		echo
			'<p class="static-msg">'.sprintf(__('Dotclear %s is available.'),$new_v).
				($version_info ? ' <a href="'.$version_info.'">('.__('information about this version').')</a>' : '').
				'</p>'.
		
		'<p>'.__('To upgrade your Dotclear installation simply click on the following button. '.
			'A backup file of your current installation will be created in your root directory.').'</p>'.
		'<form action="'.$p_url.'" method="get">'.
		'<p><input type="hidden" name="step" value="check" />'.
		'<input type="submit" value="'.__('Update Dotclear').'" /></p>'.
		'</form>';
	}
	
	if (!empty($archives))
	{
		echo
		'<h3>'.__('Update backup files').'</h3>'.
		'<p>'.__('The following files are backups of previously updates. '.
		'You can revert your previous installation or delete theses files.').'</p>';
		
		echo	'<form action="'.$p_url.'" method="post">';
		
		foreach ($archives as $v) {
			echo
			'<p><label class="classic">'.form::radio(array('backup_file'),html::escapeHTML($v)).' '.
			html::escapeHTML($v).'</label></p>';
		}
		
		echo
		'<p><strong>'.__('Please note that reverting your Dotclear version may have some '.
		'unwanted side-effects. Consider reverting only if you experience strong issues with this new version.').'</strong> '.
		sprintf(__('You should not revert to version prior to last one (%s).'),end($archives)).
		'</p>'.
		'<p><input type="submit" class="delete" name="b_del" value="'.__('Delete selected file').'" /> '.
		'<input type="submit" name="b_revert" value="'.__('Revert to selected file').'" />'.
		$core->formNonce().'</p>'.
		'</form>';
	}
}
elseif ($step == 'unzip' && !$core->error->flag())
{
	echo
	'<p class="message">'.
	__("Congratulations, you're one click away from the end of the update.").
	' <strong><a href="index.php?logout=1">'.__('Finish the update.').'</a></strong>'.
	'</p>';
}

dcPage::close();
?>