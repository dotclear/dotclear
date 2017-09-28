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

define('UTF8MB4_MAXLEN',191);

class dcMaintenanceUtf8mb4 extends dcMaintenanceTask
{
	protected $perm = null;		// super admin only
	protected $step_task;
	protected $list;

	protected function init()
	{
		$this->name 		= __('UTF8-mb4 check');
		$this->task 		= __('UTF8-mb4 compatibility check');
		$this->step			= __('Next: %s');
		$this->step_task	= __('Next');
		$this->success 		= __('Check end');
		$this->error 		= __('Some data will not be fully importable in a UTF8-mb4 database. You need to reduce them before.');

		$this->description = __('Check various data for compatibility with UTF8-mb4 (full UTF8 encoding storage) before exporting and importing them in a new UTF8-mb4 MySQL database.');
	}

	public function execute()
	{
		$this->code = $this->checkUtf8mb4($this->code);
		return $this->code ?: true;
	}

	public function task()
	{
		return $this->code ? $this->step_task : $this->task;
	}

	public function step()
	{
		return $this->code ? '<p>'.$this->list.'</p>' : null;
	}

	public function success()
	{
		return $this->code ? sprintf($this->step, $this->step_task) : $this->success;
	}

	public function header()
	{
		return sprintf($this->step, $this->step_task);
	}

	protected function checkUtf8mb4($code=null)
	{
		switch ($code) {
			case null:
			case 0:
				# check posts
				$this->list = __('All post URLs are importable in UTF8-mb4 database');

				$rs = $this->core->con->select(
					'SELECT post_id, post_type, post_title, post_url, LENGTH(post_url) AS xlen FROM '.$this->core->prefix.'post '.
					'WHERE LENGTH(post_url) > '.UTF8MB4_MAXLEN.' ORDER BY post_id', true);
				if (!$rs->isEmpty()) {
					$this->list =
						'<p class="step-msg">'.
						sprintf(__('%s post URLs are longer than %d characters:'),$rs->count(),UTF8MB4_MAXLEN).'</p>'.
						'<div class="table-outer">'.
						'<table>'.
						'<th class="first">'.__('Title').'</th>'.
						'<th>'.__('URL Length').'</th>';
					while ($rs->fetch()) {
						$this->list .=
							'<tr class="line" id="p'.$rs->post_id.'">'.
							'<td class="maximal" scope="row"><a href="'.
								$this->core->getPostAdminURL($rs->post_type,$rs->post_id).'">'.
								html::escapeHTML($rs->post_title).'</a></td>'.
							'<td class="nowrap count">'.$rs->xlen.'</td>'.
							'</tr>';
					}
					$this->list .=
						'</table>'.
						'</div>';
				}
				$code++;
				break;
			case 1:
				# check pings
				$this->list = __('All ping URLs are importable in UTF8-mb4 database');

				$rs = $this->core->con->select(
					'SELECT post_id, ping_url, LENGTH(ping_url) AS xlen FROM '.$this->core->prefix.'ping '.
					'WHERE LENGTH(ping_url) > '.UTF8MB4_MAXLEN.' ORDER BY post_id', true);
				if (!$rs->isEmpty()) {
					$this->list =
						'<p class="step-msg">'.
						sprintf(__('%s ping URLs are longer than %d characters:'),$rs->count(),UTF8MB4_MAXLEN).'</p>'.
						'<div class="table-outer">'.
						'<table>'.
						'<th class="first">'.__('Ping URL').'</th>'.
						'<th>'.__('URL Length').'</th>';
					while ($rs->fetch()) {
						$this->list .=
							'<tr class="line" id="p'.$rs->post_id.'">'.
							'<td class="maximal" scope="row"><a href="'.
								$this->core->getPostAdminURL('post',$rs->post_id).'#trackbacks">'.
								html::escapeHTML($rs->ping_url).'</a></td>'.
							'<td class="nowrap count">'.$rs->xlen.'</td>'.
							'</tr>';
					}
					$this->list .=
						'</table>'.
						'</div>';
				}
				$code++;
				break;
			case 2:
				# check meta
				$this->list = __('All meta IDs are importable in UTF8-mb4 database');

				$rs = $this->core->con->select(
					'SELECT meta_id, meta_type, LENGTH(meta_id) AS xlen FROM '.$this->core->prefix.'meta '.
					'WHERE LENGTH(meta_id) > '.UTF8MB4_MAXLEN.' ORDER BY meta_id', true);
				if (!$rs->isEmpty()) {
					$this->list =
						'<p class="step-msg">'.
						sprintf(__('%s meta IDs are longer than %d characters:'),$rs->count(),UTF8MB4_MAXLEN).'</p>'.
						'<div class="table-outer">'.
						'<table>'.
						'<th class="first">'.__('Meta ID').'</th>'.
						'<th>'.__('Type').'</th>'.
						'<th>'.__('Length').'</th>';
					while ($rs->fetch()) {
						$this->list .=
							'<tr class="line" id="m-'.$rs->meta_id.'">'.
							'<td class="maximal" scope="row">'.
								($rs->meta_type == 'tag' ?
									'<a href="'.$this->core->adminurl->get('admin.plugin.tags',
										array('m' => 'tag_posts','tag' => $rs->meta_id)).'">' : '').
								html::escapeHTML($rs->meta_id).($rs->meta_type == 'tag' ? '</a>' : '').'</td>'.
							'<td>'.$rs->meta_type.'</td>'.
							'<td class="nowrap count">'.$rs->xlen.'</td>'.
							'</tr>';
					}
					$this->list .=
						'</table>'.
						'</div>';
				}
				$code++;
				break;
			case 3:
				# check categories
				$this->list = __('All Category URLs are importable in UTF8-mb4 database');

				$rs = $this->core->con->select(
					'SELECT cat_id, cat_title, cat_url, LENGTH(cat_url) AS xlen FROM '.$this->core->prefix.'category '.
					'WHERE LENGTH(cat_url) > '.UTF8MB4_MAXLEN.' ORDER BY cat_id', true);
				if (!$rs->isEmpty()) {
					$this->list =
						'<p class="step-msg">'.
						sprintf(__('%s category URLs are longer than %d characters:'),$rs->count(),UTF8MB4_MAXLEN).'</p>'.
						'<div class="table-outer">'.
						'<table>'.
						'<th class="first">'.__('Title').'</th>'.
						'<th>'.__('URL Length').'</th>';
					while ($rs->fetch()) {
						$this->list .=
							'<tr class="line" id="c-'.$rs->cat_id.'">'.
							'<td class="maximal" scope="row">'.
								'<a href="'.$this->core->adminurl->get('admin.category',array('id' => $rs->cat_id)).'">'.
								html::escapeHTML($rs->cat_title).'</a></td>'.
							'<td class="nowrap count">'.$rs->xlen.'</td>'.
							'</tr>';
					}
					$this->list .=
						'</table>'.
						'</div>';
				}
				$code++;
				break;
			case 4:
				# check prefs
				$this->list = __('All User preferences are importable in UTF8-mb4 database');

				$rs = $this->core->con->select(
					'SELECT pref_id, pref_ws, LENGTH(pref_id) AS xlen FROM '.$this->core->prefix.'pref '.
					'WHERE LENGTH(pref_id) > '.UTF8MB4_MAXLEN.' ORDER BY pref_ws,pref_id', true);
				if (!$rs->isEmpty()) {
					$this->list =
						'<p class="step-msg">'.
						sprintf(__('%s User preference IDs are longer than %d characters:'),$rs->count(),UTF8MB4_MAXLEN).'</p>'.
						'<div class="table-outer">'.
						'<table>'.
						'<th class="first">'.__('Preference ID').'</th>'.
						'<th>'.__('Workspace').'</th>'.
						'<th>'.__('Length').'</th>';
					while ($rs->fetch()) {
						$this->list .=
							'<tr class="line" id="p-'.$rs->pref_id.'">'.
							'<td class="maximal" scope="row">'.html::escapeHTML($rs->pref_id).'</td>'.
							'<td>'.html::escapeHTML($rs->pref_ws).'</td>'.
							'<td class="nowrap count">'.$rs->xlen.'</td>'.
							'</tr>';
					}
					$this->list .=
						'</table>'.
						'</div>';
				}
				$code++;
				break;
			case 5:
				# check settings
				$this->list = __('All Blog settings are importable in UTF8-mb4 database');

				$rs = $this->core->con->select(
					'SELECT setting_id, setting_ns, LENGTH(setting_id) AS xlen FROM '.$this->core->prefix.'setting '.
					'WHERE LENGTH(setting_id) > '.UTF8MB4_MAXLEN.' ORDER BY setting_ns,setting_id', true);
				if (!$rs->isEmpty()) {
					$this->list =
						'<p class="step-msg">'.
						sprintf(__('%s Blog setting IDs are longer than %d characters:'),$rs->count(),UTF8MB4_MAXLEN).'</p>'.
						'<div class="table-outer">'.
						'<table>'.
						'<th class="first">'.__('Setting ID').'</th>'.
						'<th>'.__('Namespace').'</th>'.
						'<th>'.__('Length').'</th>';
					while ($rs->fetch()) {
						$this->list .=
							'<tr class="line" id="s-'.$rs->setting_id.'">'.
							'<td class="maximal" scope="row">'.html::escapeHTML($rs->setting_id).'</td>'.
							'<td>'.html::escapeHTML($rs->setting_ns).'</td>'.
							'<td class="nowrap count">'.$rs->xlen.'</td>'.
							'</tr>';
					}
					$this->list .=
						'</table>'.
						'</div>';
				}
				$code++;
				break;
			default:
				# Ending check
				$code = null;
				break;
		}
		return $code;
	}

}
