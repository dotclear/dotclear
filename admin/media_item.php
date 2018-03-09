<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('media,media_admin');

$tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

$post_id = !empty($_REQUEST['post_id']) ? (integer) $_REQUEST['post_id'] : null;
if ($post_id) {
    $post = $core->blog->getPosts(array('post_id' => $post_id));
    if ($post->isEmpty()) {
        $post_id = null;
    }
    $post_title = $post->post_title;
    unset($post);
}

// Attachement type if any
$link_type = !empty($_REQUEST['link_type']) ? $_REQUEST['link_type'] : null;

$file                  = null;
$popup                 = (integer) !empty($_REQUEST['popup']);
$select                = !empty($_REQUEST['select']) ? (integer) $_REQUEST['select'] : 0; // 0 : none, 1 : single media, >1 : multiple medias
$plugin_id             = isset($_REQUEST['plugin_id']) ? html::sanitizeURL($_REQUEST['plugin_id']) : '';
$page_url_params       = array('popup' => $popup, 'select' => $select, 'post_id' => $post_id);
$media_page_url_params = array('popup' => $popup, 'select' => $select, 'post_id' => $post_id, 'link_type' => $link_type);

if ($plugin_id != '') {
    $page_url_params['plugin_id']       = $plugin_id;
    $media_page_url_params['plugin_id'] = $plugin_id;
}

$id = !empty($_REQUEST['id']) ? (integer) $_REQUEST['id'] : '';

if ($id != '') {
    $page_url_params['id'] = $id;
}

if ($popup) {
    $open_f  = array('dcPage', 'openPopup');
    $close_f = array('dcPage', 'closePopup');
} else {
    $open_f  = array('dcPage', 'open');
    $close_f = function () {
        dcPage::helpBlock('core_media');
        dcPage::close();
    };
}

$core_media_writable = false;
try
{
    $core->media = new dcMedia($core);

    if ($id) {
        $file = $core->media->getFile($id);
    }

    if ($file === null) {
        throw new Exception(__('Not a valid file'));
    }

    $core->media->chdir(dirname($file->relname));
    $core_media_writable = $core->media->writable();

    # Prepare directories combo box
    $dirs_combo = array();
    foreach ($core->media->getDBDirs() as $v) {
        $dirs_combo['/' . $v] = $v;
    }
    # Add parent and direct childs directories if any
    $core->media->getFSDir();
    foreach ($core->media->dir['dirs'] as $k => $v) {
        $dirs_combo['/' . $v->relname] = $v->relname;
    }
    ksort($dirs_combo);
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Upload a new file
if ($file && !empty($_FILES['upfile']) && $file->editable && $core_media_writable) {
    try {
        files::uploadStatus($_FILES['upfile']);
        $core->media->uploadFile($_FILES['upfile']['tmp_name'], $file->basename, null, false, true);

        dcPage::addSuccessNotice(__('File has been successfully updated.'));
        $core->adminurl->redirect('admin.media.item', $page_url_params);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Update file
if ($file && !empty($_POST['media_file']) && $file->editable && $core_media_writable) {
    $newFile = clone $file;

    $newFile->basename = $_POST['media_file'];

    if ($_POST['media_path']) {
        $newFile->dir     = $_POST['media_path'];
        $newFile->relname = $_POST['media_path'] . '/' . $newFile->basename;
    } else {
        $newFile->dir     = '';
        $newFile->relname = $newFile->basename;
    }
    $newFile->media_title = html::escapeHTML($_POST['media_title']);
    $newFile->media_dt    = strtotime($_POST['media_dt']);
    $newFile->media_dtstr = $_POST['media_dt'];
    $newFile->media_priv  = !empty($_POST['media_private']);

    try {
        $core->media->updateFile($file, $newFile);

        dcPage::addSuccessNotice(__('File has been successfully updated.'));
        $page_url_params['tab'] = 'media-details-tab';
        $core->adminurl->redirect('admin.media.item', $page_url_params);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Update thumbnails
if (!empty($_POST['thumbs']) && $file->media_type == 'image' && $file->editable && $core_media_writable) {
    try {
        $foo = null;
        $core->media->mediaFireRecreateEvent($file);

        dcPage::addSuccessNotice(__('Thumbnails have been successfully updated.'));
        $page_url_params['tab'] = 'media-details-tab';
        $core->adminurl->redirect('admin.media.item', $page_url_params);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Unzip file
if (!empty($_POST['unzip']) && $file->type == 'application/zip' && $file->editable && $core_media_writable) {
    try {
        $unzip_dir = $core->media->inflateZipFile($file, $_POST['inflate_mode'] == 'new');

        dcPage::addSuccessNotice(__('Zip file has been successfully extracted.'));
        $media_page_url_params['d'] = $unzip_dir;
        $core->adminurl->redirect('admin.media', $media_page_url_params);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Save media insertion settings for the blog
if (!empty($_POST['save_blog_prefs'])) {
    if (!empty($_POST['pref_src'])) {
        foreach (array_reverse($file->media_thumb) as $s => $v) {
            if ($v == $_POST['pref_src']) {
                $core->blog->settings->system->put('media_img_default_size', $s);
                break;
            }
        }
    }
    if (!empty($_POST['pref_alignment'])) {
        $core->blog->settings->system->put('media_img_default_alignment', $_POST['pref_alignment']);
    }
    if (!empty($_POST['pref_insertion'])) {
        $core->blog->settings->system->put('media_img_default_link', ($_POST['pref_insertion'] == 'link'));
    }
    if (!empty($_POST['pref_legend'])) {
        $core->blog->settings->system->put('media_img_default_legend', $_POST['pref_legend']);
    }

    dcPage::addSuccessNotice(__('Default media insertion settings have been successfully updated.'));
    $core->adminurl->redirect('admin.media.item', $page_url_params);
}

# Function to get image title based on meta
$get_img_title = function ($file, $pattern, $dto_first = false, $no_date_alone = false) {
    $res     = array();
    $pattern = preg_split('/\s*;;\s*/', $pattern);
    $sep     = ', ';
    $dates   = 0;
    $items   = 0;

    foreach ($pattern as $v) {
        if ($v == 'Title') {
            if ($file->media_title != '') {
                $res[] = $file->media_title;
            }
            $items++;
        } elseif ($file->media_meta->{$v}) {
            if ((string) $file->media_meta->{$v} != '') {
                $res[] = (string) $file->media_meta->{$v};
            }
            $items++;
        } elseif (preg_match('/^Date\((.+?)\)$/u', $v, $m)) {
            if ($dto_first && ($file->media_meta->DateTimeOriginal != 0)) {
                $res[] = dt::dt2str($m[1], (string) $file->media_meta->DateTimeOriginal);
            } else {
                $res[] = dt::str($m[1], $file->media_dt);
            }
            $items++;
            $dates++;
        } elseif (preg_match('/^DateTimeOriginal\((.+?)\)$/u', $v, $m) && $file->media_meta->DateTimeOriginal) {
            $res[] = dt::dt2str($m[1], (string) $file->media_meta->DateTimeOriginal);
            $items++;
            $dates++;
        } elseif (preg_match('/^separator\((.*?)\)$/u', $v, $m)) {
            $sep = $m[1];
        }
    }
    if ($no_date_alone && $dates == count($res) && $dates < $items) {
        // On ne laisse pas les dates seules, sauf si ce sont les seuls items du pattern (hors séparateur)
        return '';
    }
    return implode($sep, $res);
};

/* DISPLAY Main page
-------------------------------------------------------- */
$starting_scripts =
'<script type="text/javascript">' . "\n" .
dcPage::jsVar('dotclear.msg.confirm_delete_media', __('Are you sure to delete this media?')) . "\n" .
"</script>" .
dcPage::jsModal() .
dcPage::jsLoad('js/_media_item.js');
if ($popup && !empty($plugin_id)) {
    $starting_scripts .= $core->callBehavior('adminPopupMedia', $plugin_id);
}
$temp_params      = $media_page_url_params;
$temp_params['d'] = '%s';
$breadcrumb       = $core->media->breadCrumb($core->adminurl->get('admin.media', $temp_params, '&amp;', true)) .
    ($file === null ? '' : '<span class="page-title">' . $file->basename . '</span>');
$temp_params['d'] = '';
$home_url         = $core->adminurl->get('admin.media', $temp_params);
call_user_func($open_f, __('Media manager'),
    $starting_scripts .
    dcPage::jsDatePicker() .
    ($popup ? dcPage::jsPageTabs($tab) : ''),
    dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            __('Media manager')                 => $home_url,
            $breadcrumb                         => ''
        ),
        array(
            'home_link' => !$popup,
            'hl'        => false
        )
    )
);

if ($popup) {
    // Display notices
    echo dcPage::notices();
}

if ($file === null) {
    call_user_func($close_f);
    exit;
}

if (!empty($_GET['fupd']) || !empty($_GET['fupl'])) {
    dcPage::success(__('File has been successfully updated.'));
}
if (!empty($_GET['thumbupd'])) {
    dcPage::success(__('Thumbnails have been successfully updated.'));
}
if (!empty($_GET['blogprefupd'])) {
    dcPage::success(__('Default media insertion settings have been successfully updated.'));
}

# Get major file type (first part of mime type)
$file_type = explode('/', $file->type);

# Selection mode
if ($select) {
    // Let user choose thumbnail size if image
    $media_desc = $file->media_title;

    echo
    '<div id="media-select" class="multi-part" title="' . __('Select media item') . '">' .
    '<h3>' . __('Select media item') . '</h3>' .
        '<form id="media-select-form" action="" method="get">';

    $media_img_default_size = $core->blog->settings->system->media_img_default_size;
    if ($media_img_default_size == '') {
        $media_img_default_size = 'm';
    }
    $media_img_default_alignment = $core->blog->settings->system->media_img_default_alignment;
    if ($media_img_default_alignment == '') {
        $media_img_default_alignment = 'none';
    }
    $media_img_default_link   = (boolean) $core->blog->settings->system->media_img_default_link;
    $media_img_default_legend = $core->blog->settings->system->media_img_default_legend;
    if ($media_img_default_legend == '') {
        $media_img_default_legend = 'legend';
    }

    if ($file->media_type == 'image') {
        $media_type = 'image';
        $media_desc = $get_img_title($file,
            $core->blog->settings->system->media_img_title_pattern,
            $core->blog->settings->system->media_img_use_dto_first,
            $core->blog->settings->system->media_img_no_date_alone);
        if ($media_desc == $file->basename) {
            $media_desc = '';
        }

        echo
        '<h3>' . __('Image size:') . '</h3> ';

        $s_checked = false;
        echo '<p>';
        foreach (array_reverse($file->media_thumb) as $s => $v) {
            $s_checked = ($s == $media_img_default_size);
            echo '<label class="classic">' .
            form::radio(array('src'), html::escapeHTML($v), $s_checked) . ' ' .
            $core->media->thumb_sizes[$s][2] . '</label><br /> ';
        }
        $s_checked = (!isset($file->media_thumb[$media_img_default_size]));
        echo '<label class="classic">' .
        form::radio(array('src'), $file->file_url, $s_checked) . ' ' . __('original') . '</label><br /> ';
        echo '</p>';

    } elseif ($file_type[0] == 'audio') {
        $media_type = 'mp3';
    } elseif ($file_type[0] == 'video') {
        $media_type = 'flv';
    } else {
        $media_type = 'default';
    }

    echo
    '<p>' .
    '<button type="button" id="media-select-ok" class="submit">' . __('Select') . '</button> ' .
    '<button type="button" id="media-select-cancel">' . __('Cancel') . '</button>' .
    form::hidden(array('type'), html::escapeHTML($media_type)) .
    form::hidden(array('title'), html::escapeHTML($file->media_title)) .
    form::hidden(array('description'), html::escapeHTML($media_desc)) .
    form::hidden(array('url'), $file->file_url) .
        '</p>';

    echo '</form>';
    echo '</div>';
}

# Insertion popup
if ($popup && !$select) {
    $media_desc = $file->media_title;

    echo
    '<div id="media-insert" class="multi-part" title="' . __('Insert media item') . '">' .
    '<h3>' . __('Insert media item') . '</h3>' .
        '<form id="media-insert-form" action="" method="get">';

    $media_img_default_size = $core->blog->settings->system->media_img_default_size;
    if ($media_img_default_size == '') {
        $media_img_default_size = 'm';
    }
    $media_img_default_alignment = $core->blog->settings->system->media_img_default_alignment;
    if ($media_img_default_alignment == '') {
        $media_img_default_alignment = 'none';
    }
    $media_img_default_link   = (boolean) $core->blog->settings->system->media_img_default_link;
    $media_img_default_legend = $core->blog->settings->system->media_img_default_legend;
    if ($media_img_default_legend == '') {
        $media_img_default_legend = 'legend';
    }

    if ($file->media_type == 'image') {
        $media_type = 'image';
        $media_desc = $get_img_title($file,
            $core->blog->settings->system->media_img_title_pattern,
            $core->blog->settings->system->media_img_use_dto_first,
            $core->blog->settings->system->media_img_no_date_alone);
        if ($media_desc == $file->basename) {
            $media_desc = '';
        }

        echo
        '<div class="two-boxes">' .
        '<h3>' . __('Image size:') . '</h3> ';
        $s_checked = false;
        echo '<p>';
        foreach (array_reverse($file->media_thumb) as $s => $v) {
            $s_checked = ($s == $media_img_default_size);
            echo '<label class="classic">' .
            form::radio(array('src'), html::escapeHTML($v), $s_checked) . ' ' .
            $core->media->thumb_sizes[$s][2] . '</label><br /> ';
        }
        $s_checked = (!isset($file->media_thumb[$media_img_default_size]));
        echo '<label class="classic">' .
        form::radio(array('src'), $file->file_url, $s_checked) . ' ' . __('original') . '</label><br /> ';
        echo '</p>';
        echo '</div>';

        echo
        '<div class="two-boxes">' .
        '<h3>' . __('Image legend and title') . '</h3>' .
        '<p>' .
        '<label for="legend1" class="classic">' . form::radio(array('legend', 'legend1'), 'legend',
            ($media_img_default_legend == 'legend')) .
        __('Legend and title') . '</label><br />' .
        '<label for="legend2" class="classic">' . form::radio(array('legend', 'legend2'), 'title',
            ($media_img_default_legend == 'title')) .
        __('Title') . '</label><br />' .
        '<label for="legend3" class="classic">' . form::radio(array('legend', 'legend3'), 'none',
            ($media_img_default_legend == 'none')) .
        __('None') . '</label>' .
            '</p>' .
            '</div>';

        echo
        '<div class="two-boxes">' .
        '<h3>' . __('Image alignment') . '</h3>';
        $i_align = array(
            'none'   => array(__('None'), ($media_img_default_alignment == 'none' ? 1 : 0)),
            'left'   => array(__('Left'), ($media_img_default_alignment == 'left' ? 1 : 0)),
            'right'  => array(__('Right'), ($media_img_default_alignment == 'right' ? 1 : 0)),
            'center' => array(__('Center'), ($media_img_default_alignment == 'center' ? 1 : 0))
        );

        echo '<p>';
        foreach ($i_align as $k => $v) {
            echo '<label class="classic">' .
            form::radio(array('alignment'), $k, $v[1]) . ' ' . $v[0] . '</label><br /> ';
        }
        echo '</p>';
        echo '</div>';

        echo
        '<div class="two-boxes">' .
        '<h3>' . __('Image insertion') . '</h3>' .
        '<p>' .
        '<label for="insert1" class="classic">' . form::radio(array('insertion', 'insert1'), 'simple', !$media_img_default_link) .
        __('As a single image') . '</label><br />' .
        '<label for="insert2" class="classic">' . form::radio(array('insertion', 'insert2'), 'link', $media_img_default_link) .
        __('As a link to the original image') . '</label>' .
            '</p>' .
            '</div>';
    } elseif ($file_type[0] == 'audio') {
        $media_type = 'mp3';

        echo
        '<div class="two-boxes">' .
        '<h3>' . __('MP3 disposition') . '</h3>';
        dcPage::message(__("Please note that you cannot insert mp3 files with visual editor."), false);

        $i_align = array(
            'none'   => array(__('None'), ($media_img_default_alignment == 'none' ? 1 : 0)),
            'left'   => array(__('Left'), ($media_img_default_alignment == 'left' ? 1 : 0)),
            'right'  => array(__('Right'), ($media_img_default_alignment == 'right' ? 1 : 0)),
            'center' => array(__('Center'), ($media_img_default_alignment == 'center' ? 1 : 0))
        );

        echo '<p>';
        foreach ($i_align as $k => $v) {
            echo '<label class="classic">' .
            form::radio(array('alignment'), $k, $v[1]) . ' ' . $v[0] . '</label><br /> ';
        }

        $public_player_style = unserialize($core->blog->settings->themes->mp3player_style);
        $public_player       = dcMedia::audioPlayer($file->type, $file->file_url, $core->blog->getQmarkURL() . 'pf=player_mp3.swf', $public_player_style, $core->blog->settings->system->media_flash_fallback);
        echo form::hidden('public_player', html::escapeHTML($public_player));
        echo '</p>';
        echo '</div>';
    } elseif ($file_type[0] == 'video') {
        $media_type = 'flv';

        dcPage::message(__("Please note that you cannot insert video files with visual editor."), false);

        echo
        '<div class="two-boxes">' .
        '<h3>' . __('Video size') . '</h3>' .
        '<p><label for="video_w" class="classic">' . __('Width:') . '</label> ' .
        form::number('video_w', 0, 9999, $core->blog->settings->system->media_video_width) . '  ' .
        '<label for="video_h" class="classic">' . __('Height:') . '</label> ' .
        form::number('video_h', 0, 9999, $core->blog->settings->system->media_video_height) .
            '</p>' .
            '</div>';

        echo
        '<div class="two-boxes">' .
        '<h3>' . __('Video disposition') . '</h3>';

        $i_align = array(
            'none'   => array(__('None'), ($media_img_default_alignment == 'none' ? 1 : 0)),
            'left'   => array(__('Left'), ($media_img_default_alignment == 'left' ? 1 : 0)),
            'right'  => array(__('Right'), ($media_img_default_alignment == 'right' ? 1 : 0)),
            'center' => array(__('Center'), ($media_img_default_alignment == 'center' ? 1 : 0))
        );

        echo '<p>';
        foreach ($i_align as $k => $v) {
            echo '<label class="classic">' .
            form::radio(array('alignment'), $k, $v[1]) . ' ' . $v[0] . '</label><br /> ';
        }

        $public_player_style = unserialize($core->blog->settings->themes->flvplayer_style);
        $public_player       = dcMedia::videoPlayer($file->type, $file->file_url, $core->blog->getQmarkURL() . 'pf=player_flv.swf', $public_player_style, $core->blog->settings->system->media_flash_fallback);
        echo form::hidden('public_player', html::escapeHTML($public_player));
        echo '</p>';
        echo '</div>';
    } else {
        $media_type = 'default';
        echo '<p>' . __('Media item will be inserted as a link.') . '</p>';
    }

    echo
    '<p>' .
    '<button type="button" id="media-insert-ok" class="submit">' . __('Insert') . '</button> ' .
    '<button type="button" id="media-insert-cancel">' . __('Cancel') . '</button>' .
    form::hidden(array('type'), html::escapeHTML($media_type)) .
    form::hidden(array('title'), html::escapeHTML($file->media_title)) .
    form::hidden(array('description'), html::escapeHTML($media_desc)) .
    form::hidden(array('url'), $file->file_url) .
        '</p>';

    echo '</form>';

    if ($media_type != 'default') {
        echo
        '<div class="border-top">' .
        '<form id="save_settings" action="' . $core->adminurl->getBase('admin.media.item') . '" method="post">' .
        '<p>' . __('Make current settings as default') . ' ' .
        '<input class="reset" type="submit" name="save_blog_prefs" value="' . __('OK') . '" />' .
        form::hidden(array('pref_src'), '') .
        form::hidden(array('pref_alignment'), '') .
        form::hidden(array('pref_insertion'), '') .
        form::hidden(array('pref_legend'), '') .
        $core->adminurl->getHiddenFormFields('admin.media.item', $page_url_params) .
        $core->formNonce() . '</p>' .
            '</form>' . '</div>';
    }

    echo '</div>';
}

if ($popup || $select) {
    echo
    '<div class="multi-part" title="' . __('Media details') . '" id="media-details-tab">';
} else {
    echo '<h3 class="out-of-screen-if-js">' . __('Media details') . '</h3>';
}
echo
'<p id="media-icon"><img src="' . $file->media_icon . '?' . time() * rand() . '" alt="" /></p>';

echo
    '<div id="media-details">' .
    '<div class="near-icon">';

if ($file->media_image) {
    $thumb_size = !empty($_GET['size']) ? $_GET['size'] : 's';

    if (!isset($core->media->thumb_sizes[$thumb_size]) && $thumb_size != 'o') {
        $thumb_size = 's';
    }

    if (isset($file->media_thumb[$thumb_size])) {
        echo '<p><a class="modal-image" href="' . $file->file_url . '">' .
        '<img src="' . $file->media_thumb[$thumb_size] . '?' . time() * rand() . '" alt="" />' .
            '</a></p>';
    } elseif ($thumb_size == 'o') {
        $S     = getimagesize($file->file);
        $class = ($S[1] > 500) ? ' class="overheight"' : '';
        unset($S);
        echo '<p id="media-original-image"' . $class . '><a class="modal-image" href="' . $file->file_url . '">' .
        '<img src="' . $file->file_url . '?' . time() * rand() . '" alt="" />' .
            '</a></p>';
    }

    echo '<p>' . __('Available sizes:') . ' ';
    foreach (array_reverse($file->media_thumb) as $s => $v) {
        $strong_link = ($s == $thumb_size) ? '<strong>%s</strong>' : '%s';
        printf($strong_link, '<a href="' . $core->adminurl->get('admin.media.item', array_merge($page_url_params,
            array("size" => $s, 'tab' => 'media-details-tab'))) . '">' . $core->media->thumb_sizes[$s][2] . '</a> | ');
    }
    echo '<a href="' . $core->adminurl->get('admin.media.item', array_merge($page_url_params, array("size" => "o", "tab" => "media-details-tab"))) . '">' . __('original') . '</a>';
    echo '</p>';

    if ($thumb_size != 'o' && isset($file->media_thumb[$thumb_size])) {
        $p          = path::info($file->file);
        $alpha      = ($p['extension'] == 'png') || ($p['extension'] == 'PNG');
        $thumb      = sprintf(($alpha ? $core->media->thumb_tp_alpha : $core->media->thumb_tp), $p['dirname'], $p['base'], '%s');
        $thumb_file = sprintf($thumb, $thumb_size);
        $T          = getimagesize($thumb_file);
        $stats      = stat($thumb_file);
        echo
        '<h3>' . __('Thumbnail details') . '</h3>' .
        '<ul>' .
        '<li><strong>' . __('Image width:') . '</strong> ' . $T[0] . ' px</li>' .
        '<li><strong>' . __('Image height:') . '</strong> ' . $T[1] . ' px</li>' .
        '<li><strong>' . __('File size:') . '</strong> ' . files::size($stats[7]) . '</li>' .
        '<li><strong>' . __('File URL:') . '</strong> <a href="' . $file->media_thumb[$thumb_size] . '">' .
        $file->media_thumb[$thumb_size] . '</a></li>' .
            '</ul>';
    }
}

// Show player if relevant
if ($file_type[0] == 'audio') {
    echo dcMedia::audioPlayer($file->type, $file->file_url, $core->adminurl->get("admin.home", array('pf' => 'player_mp3.swf')),
        null, $core->blog->settings->system->media_flash_fallback);
}
if ($file_type[0] == 'video') {
    echo dcMedia::videoPlayer($file->type, $file->file_url, $core->adminurl->get("admin.home", array('pf' => 'player_flv.swf')),
        null, $core->blog->settings->system->media_flash_fallback);
}

echo
'<h3>' . __('Media details') . '</h3>' .
'<ul>' .
'<li><strong>' . __('File owner:') . '</strong> ' . $file->media_user . '</li>' .
'<li><strong>' . __('File type:') . '</strong> ' . $file->type . '</li>';
if ($file->media_image) {
    $S = getimagesize($file->file);
    echo
    '<li><strong>' . __('Image width:') . '</strong> ' . $S[0] . ' px</li>' .
    '<li><strong>' . __('Image height:') . '</strong> ' . $S[1] . ' px</li>';
    unset($S);
}
echo
'<li><strong>' . __('File size:') . '</strong> ' . files::size($file->size) . '</li>' .
'<li><strong>' . __('File URL:') . '</strong> <a href="' . $file->file_url . '">' . $file->file_url . '</a></li>' .
    '</ul>';

if (empty($_GET['find_posts'])) {
    echo
    '<p><a class="button" href="' . $core->adminurl->get('admin.media.item', array_merge($page_url_params, array("find_posts" => 1, "tab" => "media-details-tab"))) . '">' .
    __('Show entries containing this media') . '</a></p>';
} else {
    echo '<h3>' . __('Entries containing this media') . '</h3>';
    $params = array(
        'post_type' => '',
        'from'      => 'LEFT OUTER JOIN ' . $core->prefix . 'post_media PM ON P.post_id = PM.post_id ',
        'sql'       => 'AND (' .
        'PM.media_id = ' . (integer) $id . ' ' .
        "OR post_content_xhtml LIKE '%" . $core->con->escape($file->relname) . "%' " .
        "OR post_excerpt_xhtml LIKE '%" . $core->con->escape($file->relname) . "%' "
    );

    if ($file->media_image) {
        # We look for thumbnails too
        if (preg_match('#^http(s)?://#', $core->blog->settings->system->public_url)) {
            $media_root = $core->blog->settings->system->public_url;
        } else {
            $media_root = $core->blog->host . path::clean($core->blog->settings->system->public_url) . '/';
        }
        foreach ($file->media_thumb as $v) {
            $v = preg_replace('/^' . preg_quote($media_root, '/') . '/', '', $v);
            $params['sql'] .= "OR post_content_xhtml LIKE '%" . $core->con->escape($v) . "%' ";
            $params['sql'] .= "OR post_excerpt_xhtml LIKE '%" . $core->con->escape($v) . "%' ";
        }
    }

    $params['sql'] .= ') ';

    $rs = $core->blog->getPosts($params);

    if ($rs->isEmpty()) {
        echo '<p>' . __('No entry seems contain this media.') . '</p>';
    } else {
        echo '<ul>';
        while ($rs->fetch()) {
            $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
            switch ($rs->post_status) {
                case 1:
                    $img_status = sprintf($img, __('published'), 'check-on.png');
                    break;
                case 0:
                    $img_status = sprintf($img, __('unpublished'), 'check-off.png');
                    break;
                case -1:
                    $img_status = sprintf($img, __('scheduled'), 'scheduled.png');
                    break;
                case -2:
                    $img_status = sprintf($img, __('pending'), 'check-wrn.png');
                    break;
            }
            echo '<li>' . $img_status . ' ' . '<a href="' . $core->getPostAdminURL($rs->post_type, $rs->post_id) . '">' .
            $rs->post_title . '</a>' .
            ($rs->post_type != 'post' ? ' (' . html::escapeHTML($rs->post_type) . ')' : '') .
            ' - ' . dt::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . '</li>';
        }
        echo '</ul>';
    }
}

if ($file->type == 'image/jpeg') {
    echo '<h3>' . __('Image details') . '</h3>';

    $details = '';
    if (count($file->media_meta) > 0) {
        foreach ($file->media_meta as $k => $v) {
            if ((string) $v) {
                $details .= '<li><strong>' . $k . ':</strong> ' . html::escapeHTML($v) . '</li>';
            }
        }
    }
    if ($details) {
        echo '<ul>' . $details . '</ul>';
    } else {
        echo '<p>' . __('No detail') . '</p>';
    }
}

echo '</div>';

echo '<h3>' . __('Updates and modifications') . '</h3>';

if ($file->editable && $core_media_writable) {
    if ($file->media_type == 'image') {
        echo
        '<form class="clear fieldset" action="' . $core->adminurl->get("admin.media.item") . '" method="post">' .
        '<h4>' . __('Update thumbnails') . '</h4>' .
        '<p class="more-info">' . __('This will create or update thumbnails for this image.') . '</p>' .
        '<p><input type="submit" name="thumbs" value="' . __('Update thumbnails') . '" />' .
        $core->adminurl->getHiddenFormFields('admin.media', $page_url_params) .
        $core->formNonce() . '</p>' .
            '</form>';
    }

    if ($file->type == 'application/zip') {
        $inflate_combo = array(
            __('Extract in a new directory')   => 'new',
            __('Extract in current directory') => 'current'
        );

        echo
        '<form class="clear fieldset" id="file-unzip" action="' . $core->adminurl->get("admin.media.item") . '" method="post">' .
        '<h4>' . __('Extract archive') . '</h4>' .
        '<ul>' .
        '<li><strong>' . __('Extract in a new directory') . '</strong> : ' .
        __('This will extract archive in a new directory that should not exist yet.') . '</li>' .
        '<li><strong>' . __('Extract in current directory') . '</strong> : ' .
        __('This will extract archive in current directory and will overwrite existing files or directory.') . '</li>' .
        '</ul>' .
        '<p><label for="inflate_mode" class="classic">' . __('Extract mode:') . '</label> ' .
        form::combo('inflate_mode', $inflate_combo, 'new') .
        '<input type="submit" name="unzip" value="' . __('Extract') . '" />' .
        $core->adminurl->getHiddenFormFields('admin.media', $page_url_params) .
        $core->formNonce() . '</p>' .
            '</form>';
    }

    echo
    '<form class="clear fieldset" action="' . $core->adminurl->get("admin.media.item") . '" method="post">' .
    '<h4>' . __('Change media properties') . '</h4>' .
    '<p><label for="media_file">' . __('File name:') . '</label>' .
    form::field('media_file', 30, 255, html::escapeHTML($file->basename)) . '</p>' .
    '<p><label for="media_title">' . __('File title:') . '</label>' .
    form::field('media_title', 30, 255, html::escapeHTML($file->media_title)) . '</p>' .
    '<p><label for="media_dt">' . __('File date:') . '</label>' .
    form::field('media_dt', 16, 16, html::escapeHTML($file->media_dtstr)) .
    /*
    Previous line will be replaced by this one as soon as every browser will support datetime-local input type
    Dont forget to remove call to datepicker in media_item.js

    form::datetime('media_dt', array('default' => html::escapeHTML(dt::str('%Y-%m-%dT%H:%M', $file->media_dt)))) .
     */
    '</p>' .
    '<p><label for="media_private" class="classic">' . form::checkbox('media_private', 1, $file->media_priv) . ' ' .
    __('Private') . '</label></p>' .
    '<p><label for="media_path">' . __('New directory:') . '</label>' .
    form::combo('media_path', $dirs_combo, dirname($file->relname)) . '</p>' .
    '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
    $core->adminurl->getHiddenFormFields('admin.media.item', $page_url_params) .
    $core->formNonce() . '</p>' .
        '</form>';

    echo
    '<form class="clear fieldset" action="' . $core->adminurl->get("admin.media.item") . '" method="post" enctype="multipart/form-data">' .
    '<h4>' . __('Change file') . '</h4>' .
    '<div>' . form::hidden(array('MAX_FILE_SIZE'), DC_MAX_UPLOAD_SIZE) . '</div>' .
    '<p><label for="upfile">' . __('Choose a file:') .
    ' (' . sprintf(__('Maximum size %s'), files::size(DC_MAX_UPLOAD_SIZE)) . ') ' .
    '<input type="file" id="upfile" name="upfile" size="35" />' .
    '</label></p>' .
    '<p><input type="submit" value="' . __('Send') . '" />' .
    $core->adminurl->getHiddenFormFields('admin.media', $page_url_params) .
    $core->formNonce() . '</p>' .
        '</form>';

    if ($file->del) {
        echo
        '<form id="delete-form" method="post" action="' . $core->adminurl->getBase("admin.media") . '">' .
        '<p><input name="delete" type="submit" class="delete" value="' . __('Delete this media') . '" />' .
        form::hidden('remove', rawurlencode($file->basename)) .
        form::hidden('rmyes', 1) .
        $core->adminurl->getHiddenFormFields('admin.media', $media_page_url_params) .
        $core->formNonce() . '</p>' .
            '</form>';
    }

    # --BEHAVIOR-- adminMediaItemForm
    $core->callBehavior('adminMediaItemForm', $file);
}

echo
    '</div>';
if ($popup || $select) {
    echo
        '</div>';
}

call_user_func($close_f);
