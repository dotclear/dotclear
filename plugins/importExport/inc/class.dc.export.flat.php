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

class dcExportFlat extends dcIeModule
{
	public function setInfo()
	{
		$this->type = 'e';
		$this->name = __('Flat file export');
		$this->description = __('Exports a blog or a full Dotclear installation to flat file.');
	}
	
	public function process($do)
	{
		# Export a blog
		if ($do == 'export_blog' && $this->core->auth->check('admin',$this->core->blog->id))
		{			
			$fullname = $this->core->blog->public_path.'/.backup_'.sha1(uniqid());
			$blog_id = $this->core->con->escape($this->core->blog->id);
			
			try
			{
				$exp = new dbExport($this->core->con,$fullname,$this->core->prefix);
				fwrite($exp->fp,'///DOTCLEAR|'.DC_VERSION."|single\n");
				
				$exp->export('category',
					'SELECT * FROM '.$this->core->prefix.'category '.
					"WHERE blog_id = '".$blog_id."'"
				);
				$exp->export('link',
					'SELECT * FROM '.$this->core->prefix.'link '.
					"WHERE blog_id = '".$blog_id."'"
				);
				$exp->export('setting',
					'SELECT * FROM '.$this->core->prefix.'setting '.
					"WHERE blog_id = '".$blog_id."'"
				);
				$exp->export('post',
					'SELECT * FROM '.$this->core->prefix.'post '.
					"WHERE blog_id = '".$blog_id."'"
				);
				$exp->export('meta',
					'SELECT meta_id, meta_type, M.post_id '.
					'FROM '.$this->core->prefix.'meta M, '.$this->core->prefix.'post P '.
					'WHERE P.post_id = M.post_id '.
					"AND P.blog_id = '".$blog_id."'"
				);
				$exp->export('media',
					'SELECT * FROM '.$this->core->prefix."media WHERE media_path = '".
					$this->core->con->escape($this->core->blog->settings->system->public_path)."'"
				);
				$exp->export('post_media',
					'SELECT media_id, M.post_id '.
					'FROM '.$this->core->prefix.'post_media M, '.$this->core->prefix.'post P '.
					'WHERE P.post_id = M.post_id '.
					"AND P.blog_id = '".$blog_id."'"
				);
				$exp->export('ping',
					'SELECT ping.post_id, ping_url, ping_dt '.
					'FROM '.$this->core->prefix.'ping ping, '.$this->core->prefix.'post P '.
					'WHERE P.post_id = ping.post_id '.
					"AND P.blog_id = '".$blog_id."'"
				);
				$exp->export('comment',
					'SELECT C.* '.
					'FROM '.$this->core->prefix.'comment C, '.$this->core->prefix.'post P '.
					'WHERE P.post_id = C.post_id '.
					"AND P.blog_id = '".$blog_id."'"
				);
				
				# --BEHAVIOR-- exportSingle
				$this->core->callBehavior('exportSingle',$this->core,$exp,$blog_id);
				
				$_SESSION['export_file'] = $fullname;
				$_SESSION['export_filename'] = $_POST['file_name'];
				http::redirect($this->getURL().'&do=ok');
			}
			catch (Exception $e)
			{
				@unlink($fullname);
				throw $e;
			}
		}
		
		# Export all content
		if ($do == 'export_all' && $this->core->auth->isSuperAdmin())
		{
			$fullname = $this->core->blog->public_path.'/.backup_'.sha1(uniqid());
			try
			{
				$exp = new dbExport($this->core->con,$fullname,$this->core->prefix);
				fwrite($exp->fp,'///DOTCLEAR|'.DC_VERSION."|full\n");
				$exp->exportTable('blog');
				$exp->exportTable('category');
				$exp->exportTable('link');
				$exp->exportTable('setting');
				$exp->exportTable('user');
				$exp->exportTable('pref');
				$exp->exportTable('permissions');
				$exp->exportTable('post');
				$exp->exportTable('meta');
				$exp->exportTable('media');
				$exp->exportTable('post_media');
				$exp->exportTable('log');
				$exp->exportTable('ping');
				$exp->exportTable('comment');
				$exp->exportTable('spamrule');
				$exp->exportTable('version');
				
				# --BEHAVIOR-- exportFull
				$this->core->callBehavior('exportFull',$this->core,$exp);
				
				$_SESSION['export_file'] = $fullname;
				$_SESSION['export_filename'] = $_POST['file_name'];
				http::redirect($this->getURL().'&do=ok');
			}
			catch (Exception $e)
			{
				@unlink($fullname);
				throw $e;
			}
		}
		
		# Send file content
		if ($do == 'ok')
		{
			if (!file_exists($_SESSION['export_file'])) {
				throw new Exception(__('Export file not found.'));
			}
			
			ob_end_clean();
			header('Content-Disposition: attachment;filename='.$_SESSION['export_filename']);
			header('Content-Type: text/plain; charset=UTF-8');
			readfile($_SESSION['export_file']);
			unlink($_SESSION['export_file']);
			unset($_SESSION['export_file']);
			unset($_SESSION['export_filename']);
			exit;
		}
	}
	
	public function gui()
	{
		echo
		'<form action="'.$this->getURL(true).'" method="post">'.
		'<fieldset><legend>'.__('Export a blog').'</legend>'.
		'<p>'.sprintf(__('This will create an export of your current blog: %s'),
		'<strong>'.html::escapeHTML($this->core->blog->name).'</strong>').'</p>'.
		'<p><label for="file_name" class="classic">'.__('File name:').' '.
		form::field(array('file_name','file_name'),25,255,date('Y-m-d-').html::escapeHTML($this->core->blog->id.'-backup.txt')).
		'</label> '.
		'<input type="submit" value="'.__('Export').'" />'.
		form::hidden(array('do'),'export_blog').
		$this->core->formNonce().'</p>'.
		'<p class="zip-dl"><a href="media.php?d=&amp;zipdl=1">'.
		__('You may also want to download your media directory as a zip file').'</a></p>'.
		'</fieldset></form>';
		
		if ($this->core->auth->isSuperAdmin())
		{
			echo
			'<form action="'.$this->getURL(true).'" method="post">'.
			'<fieldset><legend>'.__('Export all content').'</legend>'.
			'<p><label for="file_name2" class="classic">'.__('File name:').' '.
			form::field(array('file_name','file_name2'),25,255,date('Y-m-d-').'dotclear-backup.txt').
			'</label> '.
			'<input type="submit" value="'.__('Export all content').'" />'.
			form::hidden(array('do'),'export_all').
			$this->core->formNonce().'</p>'.
			'</fieldset></form>';
		}
	}
}
?>