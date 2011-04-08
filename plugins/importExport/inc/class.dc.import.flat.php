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
if (!defined('DC_RC_PATH')) { return; }

class dcImportFlat extends dcIeModule
{
	protected $status = false;
	
	public function setInfo()
	{
		$this->type = 'i';
		$this->name = __('Flat file import');
		$this->description = __('Imports a blog or a full Dotclear installation from flat file.');
	}
	
	public function process($do)
	{
		if ($do == 'single' || $do == 'full') {
			$this->status = $do;
			return;
		}
		
		$to_unlink = false;
		
		# Single blog import
		$files = $this->getPublicFiles();
		$single_upl = null;
		if (!empty($_POST['public_single_file']) && in_array($_POST['public_single_file'],$files)) {
			$single_upl = false;
		} elseif (!empty($_FILES['up_single_file'])) {
			$single_upl = true;
		}
		
		if ($single_upl !== null)
		{
			if ($single_upl) {
				files::uploadStatus($_FILES['up_single_file']);
				$file = DC_TPL_CACHE.'/'.md5(uniqid());
				if (!move_uploaded_file($_FILES['up_single_file']['tmp_name'],$file)) {
					throw new Exception(__('Unable to move uploaded file.'));
				}
				$to_unlink = true;
			} else {
				$file = $_POST['public_single_file'];
			}
			
			try {
				$bk = new dcImport($this->core,$file);
				$bk->importSingle();
			} catch (Exception $e) {
				if ($to_unlink) {
					@unlink($file);
				}
				throw $e;
			}
			if ($to_unlink) {
				@unlink($file);
			}
			http::redirect($this->getURL().'&do=single');
		}
		
		# Full import
		$full_upl = null;
		if (!empty($_POST['public_full_file']) && in_array($_POST['public_full_file'],$files)) {
			$full_upl = false;
		} elseif (!empty($_FILES['up_full_file'])) {
			$full_upl = true;
		}
		
		if ($full_upl !== null && $this->core->auth->isSuperAdmin())
		{
			if (empty($_POST['your_pwd']) || !$this->core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY,$_POST['your_pwd']))) {
				throw new Exception(__('Password verification failed'));
			}
			
			if ($full_upl) {
				files::uploadStatus($_FILES['up_full_file']);
				$file = DC_TPL_CACHE.'/'.md5(uniqid());
				if (!move_uploaded_file($_FILES['up_full_file']['tmp_name'],$file)) {
					throw new Exception(__('Unable to move uploaded file.'));
				}
				$to_unlink = true;
			} else {
				$file = $_POST['public_full_file'];
			}
			
			try {
				$bk = new dcImport($this->core,$file);
				$bk->importFull();
			} catch (Exception $e) {
				if ($to_unlink) {
					@unlink($file);
				}
				throw $e;
			}
			if ($to_unlink) {
				@unlink($file);
			}
			http::redirect($this->getURL().'&do=full');
		}
		
		header('content-type:text/plain');
		var_dump($_POST);
		exit;
		
		$this->status = true;
	}
	
	public function gui()
	{
		if ($this->status == 'single')
		{
			echo '<p class="message">'.__('Single blog successfully imported.').'</p>';
			return;
		}
		if ($this->status == 'full')
		{
			echo '<p class="message">'.__('Content successfully imported.').'</p>';
			return;
		}
		
		echo
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		dcPage::jsVar('dotclear.msg.confirm_full_import',
			__('Are you sure you want to import a full backup file?')).
		"$(function() {".
			"$('#up_single_file').change(function() { ".
				"if (this.value != '') { $('#public_single_file').val(''); } ".
			"}); ".
			"$('#public_single_file').change(function() { ".
				"if (this.value != '') { $('#up_single_file').val(''); } ".
			"}); ".
			"$('#up_full_file').change(function() { ".
				"if (this.value != '') { $('#public_full_file').val(''); } ".
			"}); ".
			"$('#public_full_file').change(function() { ".
				"if (this.value != '') { $('#up_full_file').val(''); } ".
			"}); ".
			"$('#formfull').submit(function() { ".
				"return window.confirm(dotclear.msg.confirm_full_import); ".
			"}); ".
		"});\n".
		"//]]>\n".
		"</script>\n";
		
		echo
		'<h3>'.__('Import a single blog').'</h3>'.
		'<p>'.sprintf(__('This will import a single blog backup as new content in the current blog: %s.'),
		'<strong>'.html::escapeHTML($this->core->blog->name).'</strong>').'</p>'.
		'<form action="'.$this->getURL(true).'" method="post" enctype="multipart/form-data">'.
		
		'<fieldset>'.
		$this->core->formNonce().
		form::hidden(array('do'),1).
		form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).
		'<p><label>'.__('Upload a backup file').' '.
		'<input type="file" id="up_single_file" name="up_single_file" size="20" />'.
		'</label></p>';
		
		$public_files = $this->getPublicFiles();
		
		$empty = empty($public_files);
		$public_files = array_merge(array('-' => ''),$public_files);
		echo
		'<p><label>'.__('or pick up a local file in your public directory').' '.
		form::combo('public_single_file',$public_files, '', '', '', $empty).
		'</label></p>';
		
		echo
		'<p><input type="submit" value="'.__('Send').'" /></p>'.
		'</fieldset>'.
		'</form>';
		
		if ($this->core->auth->isSuperAdmin())
		{
			echo
			'<h3>'.__('Import a full backup file').'</h3>'.
			'<form action="'.$this->getURL(true).'" method="post" enctype="multipart/form-data" id="formfull">'.
			'<div>'.form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).'</div>'.
			
			'<fieldset>'.
			$this->core->formNonce().
			form::hidden(array('do'),1).
			form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).
			'<p><label>'.__('Upload a backup file').' '.
			'<input type="file" id="up_full_file" name="up_full_file" size="20" />'.
			'</label></p>';
			
			echo
			'<p><label>'.__('or pick up a local file in your public directory').' '.
			form::combo('public_full_file',$public_files, '', '', '', $empty).
			'</label></p>';
			
			echo
			'<p><strong>'.__('Warning: This will reset all the content of your database, except users.').'</strong></p>'.
			
			'<p><label>'.__('Your password:').
			form::password('your_pwd',20,255).'</label></p>'.
			
			'<p><input type="submit" value="'.__('Send').'" /></p>'.
			'</fieldset>'.
			'</form>';
		}
	}
	
	protected function getPublicFiles()
	{
		$public_files = array();
		$dir = @dir($this->core->blog->public_path);
		if ($dir)
		{
			while (($entry = $dir->read()) !== false) {
				$entry_path = $dir->path.'/'.$entry;
				
				if (is_file($entry_path) && is_readable($entry_path))
				{
					$fp = fopen($entry_path,'rb');
					if (strpos(fgets($fp),'///DOTCLEAR|') === 0) {
						$public_files[$entry] = $entry_path;
					}
					fclose($fp);
				}
			}
		}
		return $public_files;
	}
}
?>