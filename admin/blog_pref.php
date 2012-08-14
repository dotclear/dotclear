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

$standalone = !isset($edit_blog_mode);

$blog_id = false;

if ($standalone)
{
	require dirname(__FILE__).'/../inc/admin/prepend.php';
	dcPage::check('admin');
	$blog_id = $core->blog->id;
	$blog_status = $core->blog->status;
	$blog_name = $core->blog->name;
	$blog_desc = $core->blog->desc;
	$blog_settings = $core->blog->settings;
	$blog_url = $core->blog->url;
	
	$action = 'blog_pref.php';
	$redir = 'blog_pref.php?upd=1';
}
else
{
	dcPage::checkSuper();
	try
	{
		if (empty($_REQUEST['id'])) {
			throw new Exception(__('No given blog id.'));
		}
		$blogs = $core->getBlog($_REQUEST['id']);
		
		if (count($blogs) == 0) {
			throw new Exception(__('No such blog.'));
		}
		$blog = $blogs->current();
		$blog_id = $blog->blog_id;
		$blog_status = $blog->blog_status;
		$blog_name = $blog->blog_name;
		$blog_desc = $blog->blog_desc;
		$blog_settings = new dcSettings($core,$blog_id);
		$blog_url = $blog->blog_url ; 
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
	
	$action = 'blog.php';
	$redir = 'blog.php?id=%s&upd=1';
}

# Language codes
$langs = l10n::getISOcodes(1,1);
foreach ($langs as $k => $v) {
	$lang_avail = $v == 'en' || is_dir(DC_L10N_ROOT.'/'.$v);
	$lang_combo[] = new formSelectOption($k,$v,$lang_avail ? 'avail10n' : '');
}

# Status combo
foreach ($core->getAllBlogStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}

# URL scan modes
$url_scan_combo = array(
	'PATH_INFO' => 'path_info',
	'QUERY_STRING' => 'query_string'
);

# Post URL combo
$post_url_combo = array(
	__('year/month/day/title') => '{y}/{m}/{d}/{t}',
	__('year/month/title') => '{y}/{m}/{t}',
	__('year/title') => '{y}/{t}',
	__('title') => '{t}'
);
if (!in_array($blog_settings->system->post_url_format,$post_url_combo)) {
	$post_url_combo[html::escapeHTML($blog_settings->system->post_url_format)] = html::escapeHTML($blog_settings->system->post_url_format);
}


# Robots policy options
$robots_policy_options = array(
	'INDEX,FOLLOW' => __("I would like search engines and archivers to index and archive my blog's content."),
	'INDEX,FOLLOW,NOARCHIVE' => __("I would like search engines and archivers to index but not archive my blog's content."),
	'NOINDEX,NOFOLLOW,NOARCHIVE' => __("I would like to prevent search engines and archivers from indexing or archiving my blog's content."),
);

# Update a blog
if ($blog_id && !empty($_POST) && $core->auth->check('admin',$blog_id))
{
	$cur = $core->con->openCursor($core->prefix.'blog');
	if ($core->auth->isSuperAdmin()) {
		$cur->blog_id = $_POST['blog_id'];
		$cur->blog_url = preg_replace('/\?+$/','?',$_POST['blog_url']); 
		if (in_array($_POST['blog_status'],$status_combo)) {
			$cur->blog_status = (integer) $_POST['blog_status'];
		}
	}
	$cur->blog_name = $_POST['blog_name'];
	$cur->blog_desc = $_POST['blog_desc'];
	
	$nb_post_per_page = abs((integer) $_POST['nb_post_per_page']);
	if ($nb_post_per_page <= 1) { $nb_post_per_page = 1; }
	
	$nb_post_per_feed = abs((integer) $_POST['nb_post_per_feed']);
	if ($nb_post_per_feed <= 1) { $nb_post_per_feed = 1; }
	
	try
	{
		if ($cur->blog_id != null && $cur->blog_id != $blog_id) {
			$blogs = $core->getBlog($cur->blog_id);
			
			if (count($blogs)>0) {
				throw new Exception(__('That blog Id is already in use.'));
			}
		}
		
		# --BEHAVIOR-- adminBeforeBlogUpdate
		$core->callBehavior('adminBeforeBlogUpdate',$cur,$blog_id);
		
		if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/',$_POST['lang'])) {
			throw new Exception(__('Invalid language code'));
		}
		
		$core->updBlog($blog_id,$cur);
		
		# --BEHAVIOR-- adminAfterBlogUpdate
		$core->callBehavior('adminAfterBlogUpdate',$cur,$blog_id);
		
		if ($cur->blog_id != null && $cur->blog_id != $blog_id) {
			if ($blog_id == $core->blog->id) {
				$core->setBlog($cur->blog_id);
				$_SESSION['sess_blog_id'] = $cur->blog_id;
				$blog_settings = $core->blog->settings;
			} else {
				$blog_settings = new dcSettings($core,$cur->blog_id);
			}
			
			$blog_id = $cur->blog_id;
		}
		
		
		$blog_settings->addNameSpace('system');
		
		$blog_settings->system->put('editor',$_POST['editor']);
		$blog_settings->system->put('copyright_notice',$_POST['copyright_notice']);
		$blog_settings->system->put('post_url_format',$_POST['post_url_format']);
		$blog_settings->system->put('lang',$_POST['lang']);
		$blog_settings->system->put('blog_timezone',$_POST['blog_timezone']);
		$blog_settings->system->put('date_format',$_POST['date_format']);
		$blog_settings->system->put('time_format',$_POST['time_format']);
		$blog_settings->system->put('enable_xmlrpc',!empty($_POST['enable_xmlrpc']));
		
		$blog_settings->system->put('nb_post_per_page',$nb_post_per_page);
		$blog_settings->system->put('use_smilies',!empty($_POST['use_smilies']));
		$blog_settings->system->put('nb_post_per_feed',$nb_post_per_feed);
		$blog_settings->system->put('short_feed_items',!empty($_POST['short_feed_items']));
		
		if (isset($_POST['robots_policy'])) {
			$blog_settings->system->put('robots_policy',$_POST['robots_policy']);
		}
		
		# --BEHAVIOR-- adminBeforeBlogSettingsUpdate
		$core->callBehavior('adminBeforeBlogSettingsUpdate',$blog_settings);
		
		if ($core->auth->isSuperAdmin() && in_array($_POST['url_scan'],$url_scan_combo)) {
			$blog_settings->system->put('url_scan',$_POST['url_scan']);
		}
		
		http::redirect(sprintf($redir,$blog_id));
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

dcPage::open(__('Blog settings'),
	'<script type="text/javascript">'."\n".
	"//<![CDATA["."\n".
	dcPage::jsVar('dotclear.msg.warning_path_info',
		__('Warning: except for special configurations, it is generally advised to have a trailing "/" in your blog URL in PATH_INFO mode.'))."\n".
	dcPage::jsVar('dotclear.msg.warning_query_string',
		__('Warning: except for special configurations, it is generally advised to have a trailing "?" in your blog URL in QUERY_STRING mode.'))."\n".
	"//]]>".
	"</script>".
	dcPage::jsConfirmClose('blog-form').
	dcPage::jsLoad('js/_blog_pref.js').
	
	
	# --BEHAVIOR-- adminBlogPreferencesHeaders
	$core->callBehavior('adminBlogPreferencesHeaders').
	
	dcPage::jsPageTabs()
);

if ($blog_id)
{
	echo '<h2>'.(!$standalone ? '<a href="blogs.php">'.__('Blogs').'</a> &rsaquo; ' : '').
	html::escapeHTML($blog_name).' &rsaquo; <span class="page-title">'.
	__('Blog settings').'</span></h2>';
	
	if (!empty($_GET['add'])) {
		echo '<p class="message">'.__('Blog has been successfully created.').'</p>';
	}
	
	if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Blog has been successfully updated.').'</p>';
	}
	
	echo
	'<div class="multi-part" id="params" title="'.__('Parameters').'">'.
	'<h3>'.__('Parameters').'</h3>'.
	'<form action="'.$action.'" method="post" id="blog-form">';
	
	echo
	'<fieldset><legend>'.__('Blog details').'</legend>'.
	$core->formNonce();
	
	if ($core->auth->isSuperAdmin())
	{
		echo
		'<p><label for="blog_id" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Blog ID:').
		form::field('blog_id',30,32,html::escapeHTML($blog_id)).'</label></p>'.
		'<p class="form-note">'.__('At least 2 characters using letters, numbers or symbols.').'</p> '.
		'<p class="form-note warn">'.__('Please note that changing your blog ID may require changes in your public index.php file.').'</p>';
	}
	
	echo
	'<p><label for="blog_name" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Blog name:').
	form::field('blog_name',30,255,html::escapeHTML($blog_name)).'</label></p>';
	
	if ($core->auth->isSuperAdmin())
	{
		echo
		'<p><label for="blog_url" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Blog URL:').
		form::field('blog_url',30,255,html::escapeHTML($blog_url)).'</label></p>'.
		
		'<p><label for="url_scan">'.__('URL scan method:').
		form::combo('url_scan',$url_scan_combo,$blog_settings->system->url_scan).'</label></p>'.
		
		'<p><label for="blog_status">'.__('Blog status:').
		form::combo('blog_status',$status_combo,$blog_status).'</label></p>';
	}
	
	echo
	'<p class="area"><label for="blog_desc">'.__('Blog description:').'</label>'.
	form::textarea('blog_desc',60,5,html::escapeHTML($blog_desc)).'</p>'.
	'</fieldset>';
	
	
	echo
	'<fieldset><legend>'.__('Blog configuration').'</legend>'.
	'<div class="two-cols">'.
	'<div class="col">'.
	'<p><label for="editor">'.__('Blog editor name:').
	form::field('editor',30,255,html::escapeHTML($blog_settings->system->editor)).
	'</label></p>'.
	
	'<p><label for="lang">'.__('Default language:').
	form::combo('lang',$lang_combo,$blog_settings->system->lang,'l10n').
	'</label></p>'.
	
	'<p><label for="blog_timezone">'.__('Blog timezone:').
	form::combo('blog_timezone',dt::getZones(true,true),html::escapeHTML($blog_settings->system->blog_timezone)).
	'</label></p>'.
	'</div>'.
	
	'<div class="col">'.
	'<p><label for="copyright_notice">'.__('Copyright notice:').
	form::field('copyright_notice',30,255,html::escapeHTML($blog_settings->system->copyright_notice)).
	'</label></p>'.
	
	'<p><label for="post_url_format">'.__('New post URL format:').
	form::combo('post_url_format',$post_url_combo,html::escapeHTML($blog_settings->system->post_url_format)).
	'</label></p>'.
	
	'<p><label for="enable_xmlrpc" class="classic">'.
	form::checkbox('enable_xmlrpc','1',$blog_settings->system->enable_xmlrpc).
	__('Enable XML/RPC interface').'</label>'.
	' - <a href="#xmlrpc">'.__('more information').'</a></p>'.
	'</div>'.
	'</div>'.
	'<br class="clear" />'. //Opera sucks
	'</fieldset>';
	
	echo
	'<fieldset><legend>'.__('Blog presentation').'</legend>'.
	'<div class="two-cols">'.
	'<div class="col">'.
	'<p><label for="date_format">'.__('Date format:').
	form::field('date_format',30,255,html::escapeHTML($blog_settings->system->date_format)).
	'</label></p>'.
	
	'<p><label for="time_format">'.__('Time format:').
	form::field('time_format',30,255,html::escapeHTML($blog_settings->system->time_format)).
	'</label></p>'.
	
	'<p><label for="use_smilies" class="classic">'.
	form::checkbox('use_smilies','1',$blog_settings->system->use_smilies).
	__('Display smilies on entries and comments').'</label></p>'.
	'</div>'.
	
	'<div class="col">'.
	'<p><label for="nb_post_per_page" class="classic">'.sprintf(__('Display %s entries per page'),
	form::field('nb_post_per_page',2,3,$blog_settings->system->nb_post_per_page)).
	'</label></p>'.
	
	'<p><label for="nb_post_per_feed" class="classic">'.sprintf(__('Display %s entries per feed'),
	form::field('nb_post_per_feed',2,3,$blog_settings->system->nb_post_per_feed)).
	'</label></p>'.
	
	'<p><label for="short_feed_items" class="classic">'.
	form::checkbox('short_feed_items','1',$blog_settings->system->short_feed_items).
	__('Truncate feeds').'</label></p>'.
	'</div>'.
    '</div>'.
	'<br class="clear" />'. //Opera sucks
	'</fieldset>';
	
	
	echo
	'<fieldset><legend>'.__('Search engines robots policy').'</legend>';
	
	$i = 0;
	foreach ($robots_policy_options as $k => $v)
	{
		echo '<p><label for="robots_policy-'.$i.'" class="classic">'.
		form::radio(array('robots_policy','robots_policy-'.$i),$k,$blog_settings->system->robots_policy == $k).' '.$v.'</label></p>';
		$i++;
	}
	
	echo '</fieldset>';
	
	
	# --BEHAVIOR-- adminBlogPreferencesForm
	$core->callBehavior('adminBlogPreferencesForm',$core,$blog_settings);
	
	echo
	'<p><input type="submit" accesskey="s" value="'.__('Save').'" />'.
	(!$standalone ? form::hidden('id',$blog_id) : '').
	'</p>'.
	'</form>';
	
	if ($core->auth->isSuperAdmin() && $blog_id != $core->blog->id)
	{
		echo
		'<form action="blog_del.php" method="post">'.
		'<p><input type="submit" class="delete" value="'.__('Delete this blog').'" />'.
		form::hidden(array('blog_id'),$blog_id).
		$core->formNonce().'</p>'.
		'</form>';
	}
	
	# XML/RPC information
	echo '<h3 id="xmlrpc">'.__('XML/RPC interface').'</h3>';
	
	echo '<p>'.__('XML/RPC interface allows you to edit your blog with an external client.').'</p>';
	
	if (!$blog_settings->system->enable_xmlrpc)
	{
		echo '<p>'.__('XML/RPC interface is not active. Change settings to enable it.').'</p>';
	}
	else
	{
		echo
		'<p>'.__('XML/RPC interface is active. You should set the following parameters on your XML/RPC client:').'</p>'.
		'<ul>'.
		'<li>'.__('Server URL:').' <strong>'.
		sprintf(DC_XMLRPC_URL,$core->blog->url,$core->blog->id).
		'</strong></li>'.
		'<li>'.__('Blogging system:').' <strong>Movable Type</strong></li>'.
		'<li>'.__('User name:').' <strong>'.$core->auth->userID().'</strong></li>'.
		'<li>'.__('Password:').' <strong>'.__('your password').'</strong></li>'.
		'<li>'.__('Blog ID:').' <strong>1</strong></li>'.
		'</ul>';
	}
	
	echo '</div>';
	
	#
	# Users on the blog (with permissions)
	
	$blog_users = $core->getBlogPermissions($blog_id,$core->auth->isSuperAdmin());
	$perm_types = $core->auth->getPermissionsTypes();
	
	echo
	'<div class="multi-part" id="users" title="'.__('Users').'">'.
	'<h3>'.__('Users on this blog').'</h3>';
	
	if (empty($blog_users))
	{
		echo '<p>'.__('No users').'</p>';
	}
	else
	{
		if ($core->auth->isSuperAdmin()) {
			$user_url_p = '<a href="user.php?id=%1$s">%1$s</a>';
		} else {
			$user_url_p = '%1$s';
		}
		
		foreach ($blog_users as $k => $v)
		{
			if (count($v['p']) > 0)
			{
				echo
				'<h4>'.sprintf($user_url_p,html::escapeHTML($k)).
				' ('.html::escapeHTML(dcUtils::getUserCN(
					$k, $v['name'], $v['firstname'], $v['displayname']
				)).')';
				
				if (!$v['super'] && $core->auth->isSuperAdmin()) {
					echo
					' - <a href="permissions.php?blog_id[]='.$blog_id.'&amp;user_id[]='.$k.'">'
					.__('Change permissions').'</a>';
				}
				
				echo '</h4>';
				
				echo '<ul>';
				if ($v['super']) {
					echo '<li>'.__('Super administrator').'</li>';
				} else {
					foreach ($v['p'] as $p => $V) {
						echo '<li>'.__($perm_types[$p]).'</li>';
					}
				}
				echo '</ul>';
			}
		}
	}
	
	echo '</div>';
}

dcPage::helpBlock('core_blog_pref');
dcPage::close();
?>