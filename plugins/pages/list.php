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
dcPage::check('pages,contentadmin');

/* Pager class
-------------------------------------------------------- */
class adminPageList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No page').'</strong></p>';
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);
			$entries = array();
			if (isset($_REQUEST['entries'])) {
				foreach ($_REQUEST['entries'] as $v) {
					$entries[(integer)$v]=true;
				}
			}			
			$html_block =
			'<div class="table-outer">'.
			'<table class="maximal dragable"><thead><tr>'.
			'<th colspan="3">'.__('Title').'</th>'.
			'<th>'.__('Date').'</th>'.
			'<th>'.__('Author').'</th>'.
			'<th>'.__('Comments').'</th>'.
			'<th>'.__('Trackbacks').'</th>'.
			'<th>'.__('Status').'</th>'.
			'</tr></thead><tbody id="pageslist">%s</tbody></table></div>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo $pager->getLinks();
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			$count = 0;
			while ($this->rs->fetch())
			{
				echo $this->postLine($count,isset($entries[$this->rs->post_id]));
				$count ++;
			}
			
			echo $blocks[1];
			
			echo $pager->getLinks();
		}
	}
	
	private function postLine($count,$checked)
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
		}
		
		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('Protected'),'locker.png');
		}
		
		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('Hidden'),'hidden.png');
		}
		
		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';
		
		$res .=
		'<td class="nowrap handle minimal">'.form::field(array('order['.$this->rs->post_id.']'),2,3,$count+1,'position','',false,'title="'.sprintf(__('position of %s'),html::escapeHTML($this->rs->post_title)).'"').'</td>'.
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,$checked,'','',!$this->rs->isEditable(),'title="'.__('Select this page').'"').'</td>'.
		'<td class="maximal"><a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		
		'<td class="nowrap">'.$this->rs->user_id.'</td>'.
		'<td class="nowrap">'.$this->rs->nb_comment.'</td>'.
		'<td class="nowrap">'.$this->rs->nb_trackback.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'</tr>';
		
		return $res;
	}
}

/* Getting pages
-------------------------------------------------------- */
$params = array(
	'post_type' => 'page'
);

$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	$nb_per_page = (integer) $_GET['nb'];
}

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;
$params['order'] = 'post_position ASC, post_title ASC';

try {
	$pages = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	$post_list = new adminPageList($core,$pages,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

class dcPagesActionsPage extends dcPostsActionsPage {

	public function __construct($core,$uri,$redirect_args=array()) {
		parent::__construct($core,$uri,$redirect_args);
		$this->redirect_fields = array();

	}
	
	public function beginPage($breadcrumb='',$head='') {
		echo '<html><head><title>'.__('Pages').'</title>'.
			dcPage::jsLoad('js/_posts_actions.js').
			'<script type="text/javascript">'.
			'//<![CDATA['.
			dcPage::jsVar('dotclear.msg.confirm_delete_posts',__("Are you sure you want to delete selected pages?")).
			'//]]>'.
			$head.
			'</script></head><body>';
			'</head><body>'.$breadcrumb;
	}
	
	public function endPage() {
		echo '</body></html>';
	}
	public function loadDefaults() {
		parent::loadDefaults();
		unset ($this->combos[__('Mark')]);
		unset ($this->actions['selected']);
		unset ($this->actions['unselected']);
		$this->actions['reorder']=array('dcPagesActionsPage','doReorderPages');
	}
	public function process() {
		// fake action for pages reordering
		if (!empty($this->from['reorder'])) {
			$this->from['action']='reorder';
		}
		$this->from['post_type']='page';
		return parent::process();
	}
	
	public static function doReorderPages($core, dcPostsActionsPage $ap, $post) {
		foreach($post['order'] as $post_id => $value) {
			if (!$core->auth->check('publish,contentadmin',$core->blog->id))
				throw new Exception(__('You are not allowed to change this entry status'));
			
			$strReq = "WHERE blog_id = '".$core->con->escape($core->blog->id)."' ".
					"AND post_id ".$core->con->in($post_id);
			
			#If user can only publish, we need to check the post's owner
			if (!$core->auth->check('contentadmin',$core->blog->id))
				$strReq .= "AND user_id = '".$core->con->escape($core->auth->userID())."' ";
			
			$cur = $core->con->openCursor($core->prefix.'post');
			
			$cur->post_position = (integer) $value-1;
			$cur->post_upddt = date('Y-m-d H:i:s');
			
			$cur->update($strReq);
			$core->blog->triggerBlog();
			
		}
		$ap->redirect(array('reo'=>1),false);
	}	
}

# Actions combo box

$pages_actions_page = new dcPagesActionsPage($core,'plugin.php',array('p'=>'pages'));

if (!$pages_actions_page->process()) {


# --BEHAVIOR-- adminPagesActionsCombo
$core->callBehavior('adminPagesActionsCombo',array(&$combo_action));

/* Display
-------------------------------------------------------- */
?>
<html>
<head>
  <title><?php echo __('Pages'); ?></title>
  <?php
  	echo dcPage::jsLoad('js/jquery/jquery-ui.custom.js').
  	     dcPage::jsLoad('index.php?pf=pages/list.js');
  ?>
  <script type="text/javascript">
  //<![CDATA[
  <?php echo dcPage::jsVar('dotclear.msg.confirm_delete_posts',__("Are you sure you want to delete selected pages?")); ?>
  //]]>
  </script>
</head>

<body>
<?php
echo dcPage::breadcrumb(
	array(
		html::escapeHTML($core->blog->name) => '',
		'<span class="page-title">'.__('Pages').'</span>' => ''
	));

if (!empty($_GET['upd'])) {
	dcPage::success(__('Selected pages have been successfully updated.'));
} elseif (!empty($_GET['del'])) {
	dcPage::success(__('Selected pages have been successfully deleted.'));
} elseif (!empty($_GET['reo'])) {
	dcPage::success(__('Selected pages have been successfully reordered.'));
}
echo
'<p class="top-add"><a class="button add" href="'.$p_url.'&amp;act=page">'.__('New page').'</a></p>';

if (!$core->error->flag())
{
	# Show pages
	$post_list->display($page,$nb_per_page,
	'<form action="plugin.php" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected pages action:').'</label> '.
	form::combo('action',$pages_actions_page->getCombo()).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden(array('post_type'),'page').
	form::hidden(array('p'),'pages').
	form::hidden(array('act'),'list').
	'</div>'.
	$core->formNonce().
	'<p class="clear form-note hidden-if-js">'.
	__('To rearrange pages order, change number at the begining of the line, then click on “Save pages order” button.').'</p>'.
	'<p class="clear form-note hidden-if-no-js">'.
	__('To rearrange pages order, move items by drag and drop, then click on “Save pages order” button.').'</p>'.
	'<input type="submit" value="'.__('Save pages order').'" name="reorder" class="clear"/>'.
	'</form>');
}
dcPage::helpBlock('pages');
?>
</body>
</html>
<?php
}
?>