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

dcPage::check('pages,contentadmin');

class adminPageList extends adminItemsList
{
	public function setColumns()
	{
		$this->addColumn('title','post_title',__('Title'),array('adminPostList','getTitle'),array('class' => array('maximal')),true,true,false);
		$this->addColumn('date','post_dt',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('datetime','post_dt',__('Date and time'),array('adminPostList','getDateTime'));
		$this->addColumn('author','user_id',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('comments','nb_comment',__('Comments'),array('adminPostList','getComments'));
		$this->addColumn('trackbacks','nb_trackback',__('Trackbacks'),array('adminPostList','getTrackbacks'));
		$this->addColumn('status','post_status',__('Status'),array('adminPostList','getStatus'));
		$this->default_sortby = 'datetime';
		$this->default_order = 'desc';

	}
	
	protected function getDefaultCaption()
	{
		return __('Pages list');
	}
	
	protected function getDefaultLine()
	{
		return
		'<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">%s</tr>';
	}
	
	protected function getTitle()
	{
		return
		'<th scope="row" class="maximal">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,'','','',!$this->rs->isEditable()).'&nbsp;'.
		'<a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.
		html::escapeHTML($this->rs->post_title).'</a></th>';
	}
	
	protected function getDate()
	{
		return '<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d'),$this->rs->post_dt).'</td>';
	}
	
	protected function getDateTime()
	{
		return '<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>';
	}
	
	protected function getAuthor()
	{
		return '<td class="nowrap">'.$this->rs->user_id.'</td>';
	}
	
	protected function getComments()
	{
		return '<td class="nowrap">'.$this->rs->nb_comment.'</td>';
	}
	
	protected function getTrackbacks()
	{
		return '<td class="nowrap">'.$this->rs->nb_trackback.'</td>';
	}
	
	protected function getStatus()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('pending'),'check-wrn.png');
				break;
		}
		
		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('protected'),'locker.png');
		}
		
		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		return '<td class="nowrap status">'.$img_status.' '.$protected.' '.$attach.'</td>';
	}
}


# Getting authors
try {
	$users = $core->blog->getPostsUsers('page');
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting dates
try {
	$dates = $core->blog->getDates(array('type'=>'month','post_type'=>'page'));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting langs
try {
	$langs = $core->blog->getLangs(array('post_type'=>'page'));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Creating filter combo boxes
if (!$core->error->flag())
{
	# Filter form we'll put in html_block
	$users_combo = array();
	while ($users->fetch())
	{
		$user_cn = dcUtils::getUserCN($users->user_id,$users->user_name,
		$users->user_firstname,$users->user_displayname);
		
		if ($user_cn != $users->user_id) {
			$user_cn .= ' ('.$users->user_id.')';
		}
		
		$users_combo[$user_cn] = $users->user_id; 
	}
	
	$status_combo = array(
	);
	foreach ($core->blog->getAllPostStatus() as $k => $v) {
		$status_combo[$v] = (string) $k;
	}
	
	# Months array
	while ($dates->fetch()) {
		$dt_m_combo[dt::str('%B %Y',$dates->ts())] = $dates->year().$dates->month();
	}
	
	while ($langs->fetch()) {
		$lang_combo[$langs->post_lang] = $langs->post_lang;
	}
}

# Actions combo box
$combo_action = array();
if ($core->auth->check('publish,contentadmin',$core->blog->id))
{
	$combo_action[__('Status')] = array(
		__('Publish') => 'publish',
		__('Unpublish') => 'unpublish',
		__('Schedule') => 'schedule',
		__('Mark as pending') => 'pending'
	);
}
if ($core->auth->check('admin',$core->blog->id))
{
	$combo_action[__('Change')] = array(__('Change author') => 'author');
}
if ($core->auth->check('delete,contentadmin',$core->blog->id))
{
	$combo_action[__('Delete')] = array(__('Delete') => 'delete');
}

# --BEHAVIOR-- adminPagesActionsCombo
$core->callBehavior('adminPagesActionsCombo',array(&$combo_action));


/* Get posts
-------------------------------------------------------- */
$post_list = new adminPageList($core);

$params = new ArrayObject();
$params['post_type'] = 'page';
$params['no_content'] = true;

$filterSet = new dcFilterSet('pages',$p_url);
class monthComboFilter extends comboFilter {
	public function applyFilter($params) {
		$month=$this->values[0];
		$params['post_month'] = substr($month,4,2);
		$params['post_year'] = substr($month,0,4);
	}
}
$filterSet
	->addFilter(new comboFilter(
		'users',__('Author'), __('Author'), 'user_id', $users_combo))
	->addFilter(new comboFilter(
		'post_status',__('Status'), __('Status'), 'post_status', $status_combo))
	->addFilter(new comboFilter(
		'lang',__('Lang'), __('Lang'), 'post_lang', $lang_combo))
	->addFilter(new monthComboFilter(
		'month',__('Month'),__('Month'), 'post_month', $dt_m_combo,array('singleval' => 1)))
	->addFilter(new textFilter(
		'search',__('Contains'),__('The page contains'), 'search',20,255));

$core->callBehavior('adminPagesFilters',$filterSet);
$filterSet->setExtra($post_list);

$filterSet->setup($_GET,$_POST);

# Get pages
try {
	$nfparams = $params->getArrayCopy();
	$filtered = $filterSet->applyFilters($params);
	$core->callBehavior('adminPostsParams',$params);
	$posts = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	if ($filtered) {
		$totalcounter = $core->blog->getPosts($nfparams,true);
		$page_title = sprintf(__('Pages / %s filtered out of %s'),$counter->f(0),$totalcounter->f(0));
	} else {
		$page_title = __('Pages');
		$filters_info = '';
	}
	$post_list->setItems($posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}


/* Display
-------------------------------------------------------- */
?>
<html>
<head>
  <title><?php echo __('Pages'); ?></title>
  <script type="text/javascript" src="js/_posts_list.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  <?php echo dcPage::jsVar('dotclear.msg.confirm_delete_posts',__("Are you sure you want to delete selected pages?")); ?>
  //]]>
  </script>
<?php
echo $filterSet->header();
?>
</head>

<body>
<?php
if (!$core->error->flag())
{
	echo 
	'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Pages').'</span></h2>'.
	'<p class="top-add"><a class="button add" href="'.$p_url.'&amp;act=page">'.__('New page').'</a></p>';

	$filterSet->display();
	# Show pages
	$post_list->display('<form action="posts_actions.php" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected pages action:').'</label> '.
	form::combo('action',$combo_action).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	str_replace('%','%%',$filterSet->getFormFieldsAsHidden()).
	$core->formNonce().
	'</div>'.
	'</form>'
	);
}

dcPage::helpBlock('pages');
?>
</body>
</html>