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
if (!defined('DC_RC_PATH')) { return; }

class dcMaintenanceSynchpostsmeta extends dcMaintenanceTask
{
	protected $ajax = true;
	protected $group = 'index';
	protected $limit = 100;
	protected $step_task;

	protected function init()
	{
		$this->name 		= __('Entries metadata');
		$this->task 		= __('Synchronize entries metadata');
		$this->step_task	= __('Next');
		$this->step 		= __('Synchronize entry %d to %d.');
		$this->success 		= __('Entries metadata synchronize done.');
		$this->error 		= __('Failed to synchronize entries metadata.');

		$this->description = __('Synchronize all entries metadata could be useful after importing content in your blog or do bad operation on database tables.');
	}

	public function execute()
	{
		$this->code = $this->synchronizeAllPostsmeta($this->code, $this->limit);

		return $this->code ? $this->code : true;
	}

	public function task()
	{
		return $this->code ? $this->step_task : $this->task;
	}

	public function step()
	{
		return $this->code ? sprintf($this->step, $this->code - $this->limit, $this->code) : null;
	}

	public function success()
	{
		return $this->code ? sprintf($this->step, $this->code - $this->limit, $this->code) : $this->success;
	}

	protected function synchronizeAllPostsmeta($start=null,$limit=null)
	{
		// Get number of posts
		$rs = $this->core->con->select('SELECT COUNT(post_id) FROM '.$this->core->prefix.'post');
		$count = $rs->f(0);

		// Get posts ids to update
		$req_limit = $start !== null && $limit !== null ? $this->core->con->limit($start, $limit) : '';
		$rs = $this->core->con->select('SELECT post_id FROM '.$this->core->prefix.'post '.$req_limit, true);

		// Update posts meta
		while ($rs->fetch()) {
			$rs_meta = $this->core->con->select('SELECT meta_id, meta_type FROM '.$this->core->prefix.'meta WHERE post_id = '.$rs->post_id.' ');

			$meta = array();
			while ($rs_meta->fetch()) {
				$meta[$rs_meta->meta_type][] = $rs_meta->meta_id;
			}

			$cur = $this->core->con->openCursor($this->core->prefix.'post');
			$cur->post_meta = serialize($meta);
			$cur->update('WHERE post_id = '.$rs->post_id);
		}
		$this->core->blog->triggerBlog();

		// Return next step
		return $start + $limit > $count ? null : $start + $limit;
	}
}
