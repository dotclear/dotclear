<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
# This file is part of Ductile, a theme for Dotclear
#
# Copyright (c) 2011 - Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

l10n::set(dirname(__FILE__).'/locales/'.$_lang.'/admin');

$img_url = $core->blog->settings->system->themes_url.'/'.$core->blog->settings->system->theme.'/img/';
$img_url = http::concatURL($core->blog->url,$img_url);
$img_path = dirname(__FILE__).'/img/';

$standalone_config = (boolean) $core->themes->moduleInfo($core->blog->settings->system->theme,'standalone_config');

$list_types = array(
	__('Title') => 'title',
	__('Short') => 'short',
	__('Full') => 'full'
);

$contexts = array(
	'default' => __('Home (first page)'),
	'default-page' => __('Home (other pages)'),
	'category' => __('Entries for a category'),
	'tag' => __('Entries for a tag'),
	'search' => __('Search result entries'),
	'archive' => __('Month archive entries')
);

$fonts = array(
	__('default') => '',
	__('Ductile primary') => 'Ductile body',
	__('Ductile secondary') => 'Ductile alternate',
	__('Times New Roman') => 'Times New Roman',
	__('Georgia') => 'Georgia',
	__('Garamond') => 'Garamond',
	__('Helvetica/Arial') => 'Helvetica/Arial',
	__('Verdana') => 'Verdana',
	__('Trebuchet MS') => 'Trebuchet MS',
	__('Impact') => 'Impact',
	__('Monospace') => 'Monospace'
);

function adjustFontSize($s)
{
	if (preg_match('/^([0-9.]+)\s*(%|pt|px|em|ex)?$/',$s,$m)) {
		if (empty($m[2])) {
			$m[2] = 'em';
		}
		return $m[1].$m[2];
	}

	return null;
}

function adjustColor($c)
{
	if ($c === '') {
		return '';
	}

	$c = strtoupper($c);

	if (preg_match('/^[A-F0-9]{3,6}$/',$c)) {
		$c = '#'.$c;
	}

	if (preg_match('/^#[A-F0-9]{6}$/',$c)) {
		return $c;
	}

	if (preg_match('/^#[A-F0-9]{3,}$/',$c)) {
		return '#'.substr($c,1,1).substr($c,1,1).substr($c,2,1).substr($c,2,1).substr($c,3,1).substr($c,3,1);
	}

	return '';
}

$ductile_base = array(
	// HTML
	'subtitle_hidden' => null,
	// CSS
	'body_font' => null,
	'alternate_font' => null,
	'blog_title_w' => null,
	'blog_title_s' => null,
	'blog_title_c' => null,
	'post_title_w' => null,
	'post_title_s' => null,
	'post_title_c' => null,
	'post_link_w' => null,
	'post_link_v_c' => null,
	'post_link_f_c' => null,
	'blog_title_w_m' => null,
	'blog_title_s_m' => null,
	'blog_title_c_m' => null,
	'post_title_w_m' => null,
	'post_title_s_m' => null,
	'post_title_c_m' => null,
	'post_simple_title_c' => null
);

$ductile_lists_base = array(
	'default' => 'short',
	'default-page' => 'short',
	'category' => 'short',
	'tag' => 'short',
	'search' => 'short',
	'archive' => 'title'
);

$ductile_counts_base = array(
	'default' => null,
	'category' => null,
	'tag' => null,
	'search' => null
);

$ductile_user = $core->blog->settings->themes->get($core->blog->settings->system->theme.'_style');
$ductile_user = @unserialize($ductile_user);
if (!is_array($ductile_user)) {
	$ductile_user = array();
}
$ductile_user = array_merge($ductile_base,$ductile_user);

$ductile_lists = $core->blog->settings->themes->get($core->blog->settings->system->theme.'_entries_lists');
$ductile_lists = @unserialize($ductile_lists);
if (!is_array($ductile_lists)) {
	$ductile_lists = $ductile_lists_base;
}

$ductile_counts = $core->blog->settings->themes->get($core->blog->settings->system->theme.'_entries_counts');
$ductile_counts = @unserialize($ductile_counts);
if (!is_array($ductile_counts)) {
	$ductile_counts = $ductile_counts_base;
}

$ductile_stickers = $core->blog->settings->themes->get($core->blog->settings->system->theme.'_stickers');
$ductile_stickers = @unserialize($ductile_stickers);

// If no stickers defined, add feed Atom one
if (!is_array($ductile_stickers)) {
	$ductile_stickers = array(array(
		'label' => __('Subscribe'),
		'url' => $core->blog->url.$core->url->getBase('feed').'/atom',
		'image' => 'sticker-feed.png'
		));
}

$ductile_stickers_full = array();
// Get all sticker images already used
if (is_array($ductile_stickers)) {
	foreach ($ductile_stickers as $v) {
		$ductile_stickers_full[] = $v['image'];
	}
}
// Get all sticker-*.png in img folder of theme
$ductile_stickers_images = files::scandir($img_path);
if (is_array($ductile_stickers_images)) {
	foreach ($ductile_stickers_images as $v) {
		if (preg_match('/^sticker\-(.*)\.png$/',$v)) {
			if (!in_array($v,$ductile_stickers_full)) {
				// image not already used
				$ductile_stickers[] = array(
					'label' => null,
					'url' => null,
					'image' => $v);
			}
		}
	}
}

$conf_tab = isset($_POST['conf_tab']) ? $_POST['conf_tab'] : 'html';

if (!empty($_POST))
{
	try
	{
		# HTML
		if ($conf_tab == 'html') {
			$ductile_user['subtitle_hidden'] = (integer) !empty($_POST['subtitle_hidden']);

			$ductile_stickers = array();
			for ($i = 0; $i < count($_POST['sticker_image']); $i++) {
				$ductile_stickers[] = array(
					'label' => $_POST['sticker_label'][$i],
					'url' => $_POST['sticker_url'][$i],
					'image' => $_POST['sticker_image'][$i]
				);
			}

			$order = array();
			if (empty($_POST['ds_order']) && !empty($_POST['order'])) {
				$order = $_POST['order'];
				asort($order);
				$order = array_keys($order);
			}
			if (!empty($order)) {
				$new_ductile_stickers = array();
				foreach ($order as $i => $k) {
					$new_ductile_stickers[] = array(
						'label' => $ductile_stickers[$k]['label'],
						'url' => $ductile_stickers[$k]['url'],
						'image' => $ductile_stickers[$k]['image']
					);
				}
				$ductile_stickers = $new_ductile_stickers;
			}
			
			for ($i = 0; $i < count($_POST['list_type']); $i++) {
				$ductile_lists[$_POST['list_ctx'][$i]] = $_POST['list_type'][$i];
			}
	 		
			for ($i = 0; $i < count($_POST['count_nb']); $i++) {
				$ductile_counts[$_POST['count_ctx'][$i]] = $_POST['count_nb'][$i];
			}
	 		
		}
		
		# CSS
		if ($conf_tab == 'css') {
			$ductile_user['body_font'] = $_POST['body_font'];
			$ductile_user['alternate_font'] = $_POST['alternate_font'];

			$ductile_user['blog_title_w'] = (integer) !empty($_POST['blog_title_w']);
			$ductile_user['blog_title_s'] = adjustFontSize($_POST['blog_title_s']);
			$ductile_user['blog_title_c'] = adjustColor($_POST['blog_title_c']);
		
			$ductile_user['post_title_w'] = (integer) !empty($_POST['post_title_w']);
			$ductile_user['post_title_s'] = adjustFontSize($_POST['post_title_s']);
			$ductile_user['post_title_c'] = adjustColor($_POST['post_title_c']);
		
			$ductile_user['post_link_w'] = (integer) !empty($_POST['post_link_w']);
			$ductile_user['post_link_v_c'] = adjustColor($_POST['post_link_v_c']);
			$ductile_user['post_link_f_c'] = adjustColor($_POST['post_link_f_c']);
		
			$ductile_user['post_simple_title_c'] = adjustColor($_POST['post_simple_title_c']);
		
			$ductile_user['blog_title_w_m'] = (integer) !empty($_POST['blog_title_w_m']);
			$ductile_user['blog_title_s_m'] = adjustFontSize($_POST['blog_title_s_m']);
			$ductile_user['blog_title_c_m'] = adjustColor($_POST['blog_title_c_m']);
		
			$ductile_user['post_title_w_m'] = (integer) !empty($_POST['post_title_w_m']);
			$ductile_user['post_title_s_m'] = adjustFontSize($_POST['post_title_s_m']);
			$ductile_user['post_title_c_m'] = adjustColor($_POST['post_title_c_m']);
		}
		
		$core->blog->settings->addNamespace('themes');
		$core->blog->settings->themes->put($core->blog->settings->system->theme.'_style',serialize($ductile_user));
		$core->blog->settings->themes->put($core->blog->settings->system->theme.'_stickers',serialize($ductile_stickers));
		$core->blog->settings->themes->put($core->blog->settings->system->theme.'_entries_lists',serialize($ductile_lists));
		$core->blog->settings->themes->put($core->blog->settings->system->theme.'_entries_counts',serialize($ductile_counts));

		// Blog refresh
		$core->blog->triggerBlog();

		// Template cache reset
		$core->emptyTemplatesCache();
		
		echo
		'<div class="message"><p>'.
		__('Theme configuration upgraded.').
		'</p></div>';
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

// Legacy mode
if (!$standalone_config) echo '</form>';

# HTML Tab

echo '<div class="multi-part" id="themes-list'.($conf_tab == 'html' ? '' : '-html').'" title="'.__('Content').'">';

echo '<form id="theme_config" action="blog_theme.php?conf=1" method="post" enctype="multipart/form-data">';

echo '<fieldset><legend>'.__('Header').'</legend>'.
'<p class="field"><label for="subtitle_hidden">'.__('Hide blog description:').' '.
form::checkbox('subtitle_hidden',1,$ductile_user['subtitle_hidden']).'</label>'.'</p>';
if ($core->plugins->moduleExists('simpleMenu'))
{
	echo '<p>'.sprintf(__('To configure the top menu go to the <a href="%s">Simple Menu administration page</a>.'),'plugin.php?p=simpleMenu').'</p>';
}
echo '</fieldset>';

echo '<fieldset><legend>'.__('Stickers').'</legend>';

echo '<table class="dragable">'.'<caption>'.__('Stickers (footer)').'</caption>'.
'<thead>'.
'<tr>'.
'<th scope="col">'.'</th>'.
'<th scope="col">'.__('Image').'</th>'.
'<th scope="col">'.__('Label').'</th>'.
'<th scope="col">'.__('URL').'</th>'.
'</tr>'.
'</thead>'.
'<tbody id="stickerslist">';
$count = 0;
foreach ($ductile_stickers as $i => $v) {
	$count++;
	echo 
	'<tr class="line" id="l_'.$i.'">'.
	'<td class="handle minimal">'.form::field(array('order['.$i.']'),2,3,$count,'position','',false).
		form::hidden(array('dynorder[]','dynorder-'.$i),$i).'</td>'.
	'<td>'.form::hidden(array('sticker_image[]'),$v['image']).'<img src="'.$img_url.$v['image'].'" /> '.'</td>'.
	'<td scope="raw">'.form::field(array('sticker_label[]','dsl-'.$i),20,255,$v['label']).'</td>'.
	'<td>'.form::field(array('sticker_url[]','dsu-'.$i),40,255,$v['url']).'</td>'.
	'</tr>';
}
echo
'</tbody>'.
'</table>';

echo '</fieldset>';

echo '<fieldset><legend>'.__('Entries list types and limits').'</legend>';

echo '<table id="entrieslist">'.'<caption>'.__('Entries lists').'</caption>'.
'<thead>'.
'<tr>'.
'<th scope="col">'.__('Context').'</th>'.
'<th scope="col">'.__('Entries list type').'</th>'.
'<th scope="col">'.__('Number of entries').'</th>'.
'</tr>'.
'</thead>'.
'<tbody>';
foreach ($ductile_lists as $k => $v) {
	echo 
		'<tr>'.
		'<td scope="raw">'.$contexts[$k].'</td>'.
		'<td>'.form::hidden(array('list_ctx[]'),$k).form::combo(array('list_type[]'),$list_types,$v).'</td>';
	if (array_key_exists($k,$ductile_counts)) {
		echo '<td>'.form::hidden(array('count_ctx[]'),$k).form::field(array('count_nb[]'),2,3,$ductile_counts[$k]).'</td>';
	} else {
		echo '<td></td>';
	}
	echo
		'</tr>';
}
echo
'</tbody>'.
'</table>';

echo '</fieldset>';

echo '<input type="hidden" name="conf_tab" value="html">';
echo '<p class="clear">'.form::hidden('ds_order','').'<input type="submit" value="'.__('Save').'" />'.$core->formNonce().'</p>';
echo '</form>';

echo '</div>'; // Close tab

# CSS tab

echo '<div class="multi-part" id="themes-list'.($conf_tab == 'css' ? '' : '-css').'" title="'.__('Presentation').'">';

echo '<form id="theme_config" action="blog_theme.php?conf=1" method="post" enctype="multipart/form-data">';

echo '<h3>'.__('General settings').'</h3>';

echo '<fieldset><legend>'.__('Fonts').'</legend>'.
'<p class="field"><label for="body_font">'.__('Main:').' '.
form::combo('body_font',$fonts,$ductile_user['body_font']).'</label></p>'.

'<p class="field"><label for="alternate_font">'.__('Secondary:').' '.
form::combo('alternate_font',$fonts,$ductile_user['alternate_font']).'</label></p>'.
'</fieldset>';

echo '<div class="two-cols">';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Blog title').'</legend>'.
'<p class="field"><label for="blog_title_w">'.__('In bold:').' '.
form::checkbox('blog_title_w',1,$ductile_user['blog_title_w']).'</label>'.'</p>'.

'<p class="field"><label for="blog_title_s">'.__('Font size (in em by default):').'</label> '.
form::field('blog_title_s',7,7,$ductile_user['blog_title_s']).'</p>'.

'<p class="field picker"><label for="blog_title_c">'.__('Color:').'</label> '.
form::field('blog_title_c',7,7,$ductile_user['blog_title_c'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Post title').'</legend>'.
'<p class="field"><label for="post_title_w">'.__('In bold:').' '.
form::checkbox('post_title_w',1,$ductile_user['post_title_w']).'</label>'.'</p>'.

'<p class="field"><label for="post_title_s">'.__('Font size (in em by default):').'</label> '.
form::field('post_title_s',7,7,$ductile_user['post_title_s']).'</p>'.

'<p class="field picker"><label for="post_title_c">'.__('Color:').'</label> '.
form::field('post_title_c',7,7,$ductile_user['post_title_c'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '</div>';

echo '<fieldset><legend>'.__('Titles without link').'</legend>'.

'<p class="field picker"><label for="post_simple_title_c">'.__('Color:').'</label> '.
form::field('post_simple_title_c',7,7,$ductile_user['post_simple_title_c'],'colorpicker').'</p>'.
'</fieldset>';

echo '<fieldset><legend>'.__('Inside posts links').'</legend>'.
'<p class="field"><label for="post_link_w">'.__('In bold:').' '.
form::checkbox('post_link_w',1,$ductile_user['post_link_w']).'</label>'.'</p>'.

'<p class="field picker"><label for="post_link_v_c">'.__('Normal and visited links color:').'</label> '.
form::field('post_link_v_c',7,7,$ductile_user['post_link_v_c'],'colorpicker').'</p>'.

'<p class="field picker"><label for="body_link_f_c">'.__('Active, hover and focus links color:').'</label> '.
form::field('post_link_f_c',7,7,$ductile_user['post_link_f_c'],'colorpicker').'</p>'.
'</fieldset>';

echo '<h3>'.__('Mobile specific settings').'</h3>';

echo '<div class="two-cols">';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Blog title').'</legend>'.
'<p class="field"><label for="blog_title_w_m">'.__('In bold:').' '.
form::checkbox('blog_title_w_m',1,$ductile_user['blog_title_w_m']).'</label>'.'</p>'.

'<p class="field"><label for="blog_title_s_m">'.__('Font size (in em by default):').'</label> '.
form::field('blog_title_s_m',7,7,$ductile_user['blog_title_s_m']).'</p>'.

'<p class="field picker"><label for="blog_title_c_m">'.__('Color:').'</label> '.
form::field('blog_title_c_m',7,7,$ductile_user['blog_title_c_m'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '<div class="col">';

echo '<fieldset><legend>'.__('Post title').'</legend>'.
'<p class="field"><label for="post_title_w_m">'.__('In bold:').' '.
form::checkbox('post_title_w_m',1,$ductile_user['post_title_w_m']).'</label>'.'</p>'.

'<p class="field"><label for="post_title_s_m">'.__('Font size (in em by default):').'</label> '.
form::field('post_title_s_m',7,7,$ductile_user['post_title_s_m']).'</p>'.

'<p class="field picker"><label for="post_title_c_m">'.__('Color:').'</label> '.
form::field('post_title_c_m',7,7,$ductile_user['post_title_c_m'],'colorpicker').'</p>'.
'</fieldset>';

echo '</div>';
echo '</div>';

echo '<input type="hidden" name="conf_tab" value="css">';
echo '<p class="clear"><input type="submit" value="'.__('Save').'" />'.$core->formNonce().'</p>';
echo '</form>';

echo '</div>'; // Close tab

dcPage::helpBlock('ductile');

// Legacy mode
if (!$standalone_config) echo '<form style="display:none">';
?>