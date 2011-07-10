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
if (!defined('DC_RC_PATH')) { return; }

class dcImportFeed extends dcIeModule
{
	protected $status = false;
	protected $feed_url = '';
	
	public function setInfo()
	{
		$this->type = 'i';
		$this->name = __('Feed import');
		$this->description = __('Imports a feed as new entries.');
	}
	
	public function process($do)
	{
		if ($do == 'ok') {
			$this->status = true;
			return;
		}
		
		if (empty($_POST['feed_url'])) {
			return;
		}
		
		$this->feed_url = $_POST['feed_url'];
		
		$feed = feedReader::quickParse($this->feed_url);
		if ($feed === false) {
			throw new Exception(__('Cannot retrieve feed URL.'));
		}
		if (count($feed->items) == 0) {
			throw new Exception(__('No items in feed.'));
		}
		
		$cur = $this->core->con->openCursor($this->core->prefix.'post');
		$this->core->con->begin();
		foreach ($feed->items as $item)
		{
			$cur->clean();
			$cur->user_id = $this->core->auth->userID();
			$cur->post_content = $item->content ? $item->content : $item->description;
			$cur->post_title = $item->title ? $item->title : text::cutString(html::clean($cur->post_content),60);
			$cur->post_format = 'xhtml';
			$cur->post_status = -2;
			$cur->post_dt = strftime('%Y-%m-%d %H:%M:%S',$item->TS);
			
			try {
				$post_id = $this->core->blog->addPost($cur);
			} catch (Exception $e) {
				$this->core->con->rollback();
				throw $e;
			}
			
			foreach ($item->subject as $subject) {
				$this->core->meta->setPostMeta($post_id,'tag',dcMeta::sanitizeMetaID($subject));
			}
		}
		
		$this->core->con->commit();
		http::redirect($this->getURL().'&do=ok');
		
	}
	
	public function gui()
	{
		if ($this->status) {
			echo '<p class="message">'.__('Content successfully imported.').'</p>';
		}
		
		echo
		'<h3>'.__('Import from a feed').'</h3>'.
		'<p>'.sprintf(__('This will import a feed (RSS or Atom) a as new content in the current blog: %s.'),
		'<strong>'.html::escapeHTML($this->core->blog->name).'</strong>').'</p>'.
		'<form action="'.$this->getURL(true).'" method="post">'.
		
		'<div class="fieldset">'.
		$this->core->formNonce().
		form::hidden(array('do'),1).
		'<p><label for="feed_url">'.__('Feed URL:').'</label>'.
		form::field('feed_url',40,300,html::escapeHTML($this->feed_url)).'</p>'.
		'<p><input type="submit" value="'.__('Import').'" /></p>'.
		'</div>'.
		'</form>';
	}
}
?>