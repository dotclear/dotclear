<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}
dcPage::check('pages,contentadmin');

$redir_url = $p_url . '&act=page';

$post_id            = '';
$post_dt            = '';
$post_format        = $core->auth->getOption('post_format');
$post_editor        = $core->auth->getOption('editor');
$post_password      = '';
$post_url           = '';
$post_lang          = $core->auth->getInfo('user_lang');
$post_title         = '';
$post_excerpt       = '';
$post_excerpt_xhtml = '';
$post_content       = '';
$post_content_xhtml = '';
$post_notes         = '';
$post_status        = $core->auth->getInfo('user_post_status');
$post_position      = 0;
$post_open_comment  = false;
$post_open_tb       = false;
$post_selected      = false;

$post_media = array();

$page_title = __('New page');

$can_view_page = true;
$can_edit_page = $core->auth->check('pages,usage', $core->blog->id);
$can_publish   = $core->auth->check('pages,publish,contentadmin', $core->blog->id);
$can_delete    = false;

$post_headlink = '<link rel="%s" title="%s" href="' . html::escapeURL($redir_url) . '&amp;id=%s" />';
$post_link     = '<a href="' . html::escapeURL($redir_url) . '&amp;id=%s" title="%s">%s</a>';

$next_link = $prev_link = $next_headlink = $prev_headlink = null;

# If user can't publish
if (!$can_publish) {
    $post_status = -2;
}

# Status combo
$status_combo = dcAdminCombos::getPostStatusesCombo();

$img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';

# Formaters combo
$core_formaters    = $core->getFormaters();
$available_formats = array('' => '');
foreach ($core_formaters as $editor => $formats) {
    foreach ($formats as $format) {
        $available_formats[$format] = $format;
    }
}

# Languages combo
$rs         = $core->blog->getLangs(array('order' => 'asc'));
$lang_combo = dcAdminCombos::getLangsCombo($rs, true);

# Validation flag
$bad_dt = false;

# Get page informations
if (!empty($_REQUEST['id'])) {
    $params['post_type'] = 'page';
    $params['post_id']   = $_REQUEST['id'];

    $post = $core->blog->getPosts($params);

    if ($post->isEmpty()) {
        $core->error->add(__('This page does not exist.'));
        $can_view_page = false;
    } else {
        $post_id            = $post->post_id;
        $post_dt            = date('Y-m-d H:i', strtotime($post->post_dt));
        $post_format        = $post->post_format;
        $post_password      = $post->post_password;
        $post_url           = $post->post_url;
        $post_lang          = $post->post_lang;
        $post_title         = $post->post_title;
        $post_excerpt       = $post->post_excerpt;
        $post_excerpt_xhtml = $post->post_excerpt_xhtml;
        $post_content       = $post->post_content;
        $post_content_xhtml = $post->post_content_xhtml;
        $post_notes         = $post->post_notes;
        $post_status        = $post->post_status;
        $post_position      = (integer) $post->post_position;
        $post_open_comment  = (boolean) $post->post_open_comment;
        $post_open_tb       = (boolean) $post->post_open_tb;
        $post_selected      = (boolean) $post->post_selected;

        $page_title = __('Edit page');

        $can_edit_page = $post->isEditable();
        $can_delete    = $post->isDeletable();

        $next_rs = $core->blog->getNextPost($post, 1);
        $prev_rs = $core->blog->getNextPost($post, -1);

        if ($next_rs !== null) {
            $next_link = sprintf($post_link, $next_rs->post_id,
                html::escapeHTML($next_rs->post_title), __('Next page') . '&nbsp;&#187;');
            $next_headlink = sprintf($post_headlink, 'next',
                html::escapeHTML($next_rs->post_title), $next_rs->post_id);
        }

        if ($prev_rs !== null) {
            $prev_link = sprintf($post_link, $prev_rs->post_id,
                html::escapeHTML($prev_rs->post_title), '&#171;&nbsp;' . __('Previous page'));
            $prev_headlink = sprintf($post_headlink, 'previous',
                html::escapeHTML($prev_rs->post_title), $prev_rs->post_id);
        }

        try {
            $core->media = new dcMedia($core);
            $post_media  = $core->media->getPostMedia($post_id);
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

# Format content
if (!empty($_POST) && $can_edit_page) {
    $post_format  = $_POST['post_format'];
    $post_excerpt = $_POST['post_excerpt'];
    $post_content = $_POST['post_content'];

    $post_title = $_POST['post_title'];

    if (isset($_POST['post_status'])) {
        $post_status = (integer) $_POST['post_status'];
    }

    if (empty($_POST['post_dt'])) {
        $post_dt = '';
    } else {
        try
        {
            $post_dt = strtotime($_POST['post_dt']);
            if ($post_dt == false || $post_dt == -1) {
                $bad_dt = true;
                throw new Exception(__('Invalid publication date'));
            }
            $post_dt = date('Y-m-d H:i', $post_dt);
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    $post_open_comment = !empty($_POST['post_open_comment']);
    $post_open_tb      = !empty($_POST['post_open_tb']);
    $post_selected     = !empty($_POST['post_selected']);
    $post_lang         = $_POST['post_lang'];
    $post_password     = !empty($_POST['post_password']) ? $_POST['post_password'] : null;
    $post_position     = (integer) $_POST['post_position'];

    $post_notes = $_POST['post_notes'];

    if (isset($_POST['post_url'])) {
        $post_url = $_POST['post_url'];
    }

    $core->blog->setPostContent(
        $post_id, $post_format, $post_lang,
        $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml
    );
}

# Delete page
if (!empty($_POST['delete']) && $can_delete) {
    try {
        # --BEHAVIOR-- adminBeforePageDelete
        $core->callBehavior('adminBeforePageDelete', $post_id);
        $core->blog->delPost($post_id);
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Create or update page
if (!empty($_POST) && !empty($_POST['save']) && $can_edit_page && !$bad_dt) {
    $cur = $core->con->openCursor($core->prefix . 'post');

    # Magic tweak :)
    $core->blog->settings->system->post_url_format = $page_url_format;

    $cur->post_type          = 'page';
    $cur->post_title         = $post_title;
    $cur->post_dt            = $post_dt ? date('Y-m-d H:i:00', strtotime($post_dt)) : '';
    $cur->post_format        = $post_format;
    $cur->post_password      = $post_password;
    $cur->post_lang          = $post_lang;
    $cur->post_title         = $post_title;
    $cur->post_excerpt       = $post_excerpt;
    $cur->post_excerpt_xhtml = $post_excerpt_xhtml;
    $cur->post_content       = $post_content;
    $cur->post_content_xhtml = $post_content_xhtml;
    $cur->post_notes         = $post_notes;
    $cur->post_status        = $post_status;
    $cur->post_position      = $post_position;
    $cur->post_open_comment  = (integer) $post_open_comment;
    $cur->post_open_tb       = (integer) $post_open_tb;
    $cur->post_selected      = (integer) $post_selected;

    if (isset($_POST['post_url'])) {
        $cur->post_url = $post_url;
    }

    # Update post
    if ($post_id) {
        try
        {
            # --BEHAVIOR-- adminBeforePageUpdate
            $core->callBehavior('adminBeforePageUpdate', $cur, $post_id);

            $core->blog->updPost($post_id, $cur);

            # --BEHAVIOR-- adminAfterPageUpdate
            $core->callBehavior('adminAfterPageUpdate', $cur, $post_id);

            http::redirect($redir_url . '&id=' . $post_id . '&upd=1');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    } else {
        $cur->user_id = $core->auth->userID();

        try
        {
            # --BEHAVIOR-- adminBeforePageCreate
            $core->callBehavior('adminBeforePageCreate', $cur);

            $return_id = $core->blog->addPost($cur);

            # --BEHAVIOR-- adminAfterPageCreate
            $core->callBehavior('adminAfterPageCreate', $cur, $return_id);

            http::redirect($redir_url . '&id=' . $return_id . '&crea=1');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

/* DISPLAY
-------------------------------------------------------- */
$default_tab = 'edit-entry';
if (!$can_edit_page) {
    $default_tab = '';
}
if (!empty($_GET['co'])) {
    $default_tab = 'comments';
}

$admin_post_behavior = '';
if ($post_editor) {
    $p_edit = $c_edit = '';
    if (!empty($post_editor[$post_format])) {
        $p_edit = $post_editor[$post_format];
    }
    if (!empty($post_editor['xhtml'])) {
        $c_edit = $post_editor['xhtml'];
    }
    if ($p_edit == $c_edit) {
        $admin_post_behavior .= $core->callBehavior('adminPostEditor',
            $p_edit, 'page', array('#post_excerpt', '#post_content', '#comment_content'), $post_format);
    } else {
        $admin_post_behavior .= $core->callBehavior('adminPostEditor',
            $p_edit, 'page', array('#post_excerpt', '#post_content'), $post_format);
        $admin_post_behavior .= $core->callBehavior('adminPostEditor',
            $c_edit, 'comment', array('#comment_content'), 'xhtml');
    }
}

?>
<html>
<head>
  <title><?php echo $page_title . ' - ' . __('Pages'); ?></title>
  <script type="text/javascript">
  <?php echo dcPage::jsVar('dotclear.msg.confirm_delete_post', __("Are you sure you want to delete this page?")); ?>
  </script>
  <?php echo
dcPage::jsDatePicker() .
dcPage::jsModal() .
dcPage::jsLoad('js/_post.js') .
$admin_post_behavior .
dcPage::jsConfirmClose('entry-form', 'comment-form') .
# --BEHAVIOR-- adminPageHeaders
$core->callBehavior('adminPageHeaders') .
dcPage::jsPageTabs($default_tab) .
    $next_headlink . "\n" . $prev_headlink;
?>
</head>

<body>

<?php

if ($post_id) {
    switch ($post_status) {
        case 1:
            $img_status = sprintf($img_status_pattern, __('Published'), 'check-on.png');
            break;
        case 0:
            $img_status = sprintf($img_status_pattern, __('Unpublished'), 'check-off.png');
            break;
        case -1:
            $img_status = sprintf($img_status_pattern, __('Scheduled'), 'scheduled.png');
            break;
        case -2:
            $img_status = sprintf($img_status_pattern, __('Pending'), 'check-wrn.png');
            break;
        default:
            $img_status = '';
    }
    $edit_entry_title = '&ldquo;' . html::escapeHTML($post_title) . '&rdquo;' . ' ' . $img_status;
} else {
    $edit_entry_title = $page_title;
}
echo dcPage::breadcrumb(
    array(
        html::escapeHTML($core->blog->name) => '',
        __('Pages')                         => $p_url,
        $edit_entry_title                   => ''
    ));

if (!empty($_GET['upd'])) {
    dcPage::success(__('Page has been successfully updated.'));
} elseif (!empty($_GET['crea'])) {
    dcPage::success(__('Page has been successfully created.'));
} elseif (!empty($_GET['attached'])) {
    dcPage::success(__('File has been successfully attached.'));
} elseif (!empty($_GET['rmattach'])) {
    dcPage::success(__('Attachment has been successfully removed.'));
}

# XHTML conversion
if (!empty($_GET['xconv'])) {
    $post_excerpt = $post_excerpt_xhtml;
    $post_content = $post_content_xhtml;
    $post_format  = 'xhtml';

    dcPage::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
}

if ($post_id && $post->post_status == 1) {
    echo '<p><a class="onblog_link outgoing" href="' . $post->getURL() . '" title="' . html::escapeHTML($post_title) . '">' . __('Go to this page on the site') . ' <img src="images/outgoing-link.svg" alt="" /></a></p>';
}

echo '';

if ($post_id) {
    echo '<p class="nav_prevnext">';
    if ($prev_link) {echo $prev_link;}
    if ($next_link && $prev_link) {echo ' | ';}
    if ($next_link) {echo $next_link;}

    # --BEHAVIOR-- adminPageNavLinks
    $core->callBehavior('adminPageNavLinks', isset($post) ? $post : null);

    echo '</p>';
}

# Exit if we cannot view page
if (!$can_view_page) {
    echo '</body></html>';
    return;
}

/* Post form if we can edit page
-------------------------------------------------------- */
if ($can_edit_page) {
    $sidebar_items = new ArrayObject(array(
        'status-box'  => array(
            'title' => __('Status'),
            'items' => array(
                'post_status' =>
                '<p><label for="post_status">' . __('Page status') . '</label> ' .
                form::combo('post_status', $status_combo,
                    array('default' => $post_status, 'disabled' => !$can_publish)) .
                '</p>',
                'post_dt'     =>
                '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                form::field('post_dt', 16, 16, $post_dt, ($bad_dt ? 'invalid' : '')) .
                /*
                Previous line will be replaced by this one as soon as every browser will support datetime-local input type
                Dont forget to remove call to datepicker in post.js

                form::datetime('post_dt', array(
                'default' => html::escapeHTML(dt::str('%Y-%m-%dT%H:%M', strtotime($post_dt))),
                'class' => ($bad_dt ? 'invalid' : '')
                )) .
                 */
                '</p>',
                'post_lang'   =>
                '<p><label for="post_lang">' . __('Page language') . '</label>' .
                form::combo('post_lang', $lang_combo, $post_lang) .
                '</p>',
                'post_format' =>
                '<div>' .
                '<h5 id="label_format"><label for="post_format" class="classic">' . __('Text formatting') . '</label></h5>' .
                '<p>' . form::combo('post_format', $available_formats, $post_format, 'maximal') . '</p>' .
                '<p class="format_control control_wiki">' .
                '<a id="convert-xhtml" class="button' . ($post_id && $post_format != 'wiki' ? ' hide' : '') .
                '" href="' . html::escapeURL($redir_url) . '&amp;id=' . $post_id . '&amp;xconv=1">' .
                __('Convert to XHTML') . '</a></p></div>')),
        'metas-box'   => array(
            'title' => __('Filing'),
            'items' => array(
                'post_position' =>
                '<p><label for="post_position" class="classic">' . __('Page position') . '</label> ' .
                form::number('post_position', array(
                    'default' => $post_position
                )) .
                '</p>')),
        'options-box' => array(
            'title' => __('Options'),
            'items' => array(
                'post_open_comment_tb' =>
                '<div>' .
                '<h5 id="label_comment_tb">' . __('Comments and trackbacks list') . '</h5>' .
                '<p><label for="post_open_comment" class="classic">' .
                form::checkbox('post_open_comment', 1, $post_open_comment) . ' ' .
                __('Accept comments') . '</label></p>' .
                ($core->blog->settings->system->allow_comments ?
                    (isContributionAllowed($post_id, strtotime($post_dt), true) ?
                        '' :
                        '<p class="form-note warn">' .
                        __('Warning: Comments are not more accepted for this entry.') . '</p>') :
                    '<p class="form-note warn">' .
                    __('Comments are not accepted on this blog so far.') . '</p>') .
                '<p><label for="post_open_tb" class="classic">' .
                form::checkbox('post_open_tb', 1, $post_open_tb) . ' ' .
                __('Accept trackbacks') . '</label></p>' .
                ($core->blog->settings->system->allow_trackbacks ?
                    (isContributionAllowed($post_id, strtotime($post_dt), false) ?
                        '' :
                        '<p class="form-note warn">' .
                        __('Warning: Trackbacks are not more accepted for this entry.') . '</p>') :
                    '<p class="form-note warn">' . __('Trackbacks are not accepted on this blog so far.') . '</p>') .
                '</div>',
                'post_hide'            =>
                '<p><label for="post_selected" class="classic">' . form::checkbox('post_selected', 1, $post_selected) . ' ' .
                __('Hide in widget Pages') . '</label>' .
                '</p>',
                'post_password'        =>
                '<p><label for="post_password">' . __('Password') . '</label>' .
                form::field('post_password', 10, 32, html::escapeHTML($post_password), 'maximal') .
                '</p>',
                'post_url'             =>
                '<div class="lockable">' .
                '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                form::field('post_url', 10, 255, html::escapeHTML($post_url), 'maximal') .
                '</p>' .
                '<p class="form-note warn">' .
                __('Warning: If you set the URL manually, it may conflict with another page.') .
                '</p></div>'
            ))));
    $main_items = new ArrayObject(array(
        "post_title"   =>
        '<p class="col">' .
        '<label class="required no-margin bold" for="post_title"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label>' .
        form::field('post_title', 20, 255, array(
            'default'    => html::escapeHTML($post_title),
            'class'      => 'maximal',
            'extra_html' => 'required placeholder="' . __('Title') . '"'
        )) .
        '</p>',

        "post_excerpt" =>
        '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">' . __('Excerpt:') . ' <span class="form-note">' .
        __('Introduction to the page.') . '</span></label> ' .
        form::textarea('post_excerpt', 50, 5, html::escapeHTML($post_excerpt)) .
        '</p>',

        "post_content" =>
        '<p class="area" id="content-area"><label class="required bold" ' .
        'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
        form::textarea('post_content', 50, $core->auth->getOption('edit_size'),
            array(
                'default'    => html::escapeHTML($post_content),
                'extra_html' => 'required placeholder="' . __('Content') . '"'
            )) .
        '</p>',

        "post_notes"   =>
        '<p class="area" id="notes-area"><label for="post_notes" class="bold">' . __('Personal notes:') . ' <span class="form-note">' .
        __('Unpublished notes.') . '</span></label>' .
        form::textarea('post_notes', 50, 5, html::escapeHTML($post_notes)) .
        '</p>'
    )
    );

    # --BEHAVIOR-- adminPostFormItems
    $core->callBehavior('adminPageFormItems', $main_items, $sidebar_items, isset($post) ? $post : null);

    echo '<div class="multi-part" title="' . ($post_id ? __('Edit page') : __('New page')) .
    sprintf(' &rsaquo; %s', $post_format) . '" id="edit-entry">';
    echo '<form action="' . html::escapeURL($redir_url) . '" method="post" id="entry-form">';

    echo '<div id="entry-wrapper">';
    echo '<div id="entry-content"><div class="constrained">';
    echo '<h3 class="out-of-screen-if-js">' . __('Edit page') . '</h3>';

    foreach ($main_items as $id => $item) {
        echo $item;
    }

    # --BEHAVIOR-- adminPageForm
    $core->callBehavior('adminPageForm', isset($post) ? $post : null);

    echo
    '<p class="border-top">' .
    ($post_id ? form::hidden('id', $post_id) : '') .
    '<input type="submit" value="' . __('Save') . ' (s)" ' .
        'accesskey="s" name="save" /> ';

    if ($post_id) {
        $preview_url = $core->blog->url .
        $core->url->getURLFor('pagespreview',
            $core->auth->userID() . '/' .
            http::browserUID(DC_MASTER_KEY . $core->auth->userID() . $core->auth->cryptLegacy($core->auth->userID())) .
            '/' . $post->post_url);
        echo '<a id="post-preview" href="' . $preview_url . '" class="button" accesskey="p">' . __('Preview') . ' (p)' . '</a>';
    } else {
        echo
        '<a id="post-cancel" href="' . $core->adminurl->get('admin.home') . '" class="button" accesskey="c">' . __('Cancel') . ' (c)</a>';
    }

    echo
    ($can_delete ? ' <input type="submit" class="delete" value="' . __('Delete') . '" name="delete" />' : '') .
    $core->formNonce() .
        '</p>';

    echo '</div></div>'; // End #entry-content
    echo '</div>';       // End #entry-wrapper

    echo '<div id="entry-sidebar" role="complementary">';

    foreach ($sidebar_items as $id => $c) {
        echo '<div id="' . $id . '" class="sb-box">' .
            '<h4>' . $c['title'] . '</h4>';
        foreach ($c['items'] as $e_name => $e_content) {
            echo $e_content;
        }
        echo '</div>';
    }

    # --BEHAVIOR-- adminPageFormSidebar
    $core->callBehavior('adminPageFormSidebar', isset($post) ? $post : null);

    echo '</div>'; // End #entry-sidebar

    echo '</form>';

    # --BEHAVIOR-- adminPostForm
    $core->callBehavior('adminPageAfterForm', isset($post) ? $post : null);

    echo '</div>'; // End

    if ($post_id && !empty($post_media)) {
        echo
        '<form action="' . $core->adminurl->get('admin.post.media') . '" id="attachment-remove-hide" method="post">' .
        '<div>' . form::hidden(array('post_id'), $post_id) .
        form::hidden(array('media_id'), '') .
        form::hidden(array('remove'), 1) .
        $core->formNonce() . '</div></form>';
    }
}

/* Comments and trackbacks
-------------------------------------------------------- */
if ($post_id) {
    $params = array('post_id' => $post_id, 'order' => 'comment_dt ASC');

    $comments   = $core->blog->getComments(array_merge($params, array('comment_trackback' => 0)));
    $trackbacks = $core->blog->getComments(array_merge($params, array('comment_trackback' => 1)));

    # Actions combo box
    $combo_action = array();
    if ($can_edit_page && $core->auth->check('publish,contentadmin', $core->blog->id)) {
        $combo_action[__('Publish')]         = 'publish';
        $combo_action[__('Unpublish')]       = 'unpublish';
        $combo_action[__('Mark as pending')] = 'pending';
        $combo_action[__('Mark as junk')]    = 'junk';
    }

    if ($can_edit_page && $core->auth->check('delete,contentadmin', $core->blog->id)) {
        $combo_action[__('Delete')] = 'delete';
    }

    $has_action = !empty($combo_action) && (!$trackbacks->isEmpty() || !$comments->isEmpty());

    echo
    '<div id="comments" class="multi-part" title="' . __('Comments') . '">';

    echo
    '<p class="top-add"><a class="button add" href="#comment-form">' . __('Add a comment') . '</a></p>';

    if ($has_action) {
        echo '<form action="comments_actions.php" method="post">';
    }

    echo '<h3>' . __('Trackbacks') . '</h3>';

    if (!$trackbacks->isEmpty()) {
        showComments($trackbacks, $has_action);
    } else {
        echo '<p>' . __('No trackback') . '</p>';
    }

    echo '<h3>' . __('Comments') . '</h3>';
    if (!$comments->isEmpty()) {
        showComments($comments, $has_action);
    } else {
        echo '<p>' . __('No comments') . '</p>';
    }

    if ($has_action) {
        echo
        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
        form::combo('action', $combo_action) .
        form::hidden('redir', html::escapeURL($redir_url) . '&amp;id=' . $post_id . '&amp;co=1') .
        $core->formNonce() .
        '<input type="submit" value="' . __('ok') . '" /></p>' .
            '</div>' .
            '</form>';
    }
    /* Add a comment
    -------------------------------------------------------- */

    echo
    '<div class="fieldset clear">' .
    '<h3>' . __('Add a comment') . '</h3>' .

    '<form action="' . $core->adminurl->get('admin.comment') . '" method="post" id="comment-form">' .
    '<div class="constrained">' .
    '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Name:') . '</label>' .
    form::field('comment_author', 30, 255, array(
        'default'    => html::escapeHTML($core->auth->getInfo('user_cn')),
        'extra_html' => 'required placeholder="' . __('Author') . '"'
    )) .
    '</p>' .

    '<p><label for="comment_email">' . __('Email:') . '</label>' .
    form::email('comment_email', array(
        'size'         => 30,
        'default'      => html::escapeHTML($core->auth->getInfo('user_email')),
        'autocomplete' => 'email'
    )) .
    '</p>' .

    '<p><label for="comment_site">' . __('Web site:') . '</label>' .
    form::url('comment_site', array(
        'size'         => 30,
        'default'      => html::escapeHTML($core->auth->getInfo('user_url')),
        'autocomplete' => 'url'
    )) .
    '</p>' .

    '<p class="area"><label for="comment_content" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' .
    __('Comment:') . '</label> ' .
    form::textarea('comment_content', 50, 8, array('extra_html' => 'required placeholder="' . __('Comment') . '"')) .
    '</p>' .

    '<p>' . form::hidden('post_id', $post_id) .
    $core->formNonce() .
    '<input type="submit" name="add" value="' . __('Save') . '" /></p>' .
    '</div>' . #constrained

    '</form>' .
    '</div>' . #add comment
    '</div>'; #comments
}

# Controls comments or trakbacks capabilities
function isContributionAllowed($id, $dt, $com = true)
{
    global $core;

    if (!$id) {
        return true;
    }
    if ($com) {
        if (($core->blog->settings->system->comments_ttl == 0) ||
            (time() - $core->blog->settings->system->comments_ttl * 86400 < $dt)) {
            return true;
        }
    } else {
        if (($core->blog->settings->system->trackbacks_ttl == 0) ||
            (time() - $core->blog->settings->system->trackbacks_ttl * 86400 < $dt)) {
            return true;
        }
    }
    return false;
}

# Show comments or trackbacks
function showComments($rs, $has_action)
{
    global $core;

    echo
    '<table class="comments-list"><tr>' .
    '<th colspan="2" class="nowrap first">' . __('Author') . '</th>' .
    '<th>' . __('Date') . '</th>' .
    '<th class="nowrap">' . __('IP address') . '</th>' .
    '<th>' . __('Status') . '</th>' .
    '<th>' . __('Edit') . '</th>' .
        '</tr>';

    while ($rs->fetch()) {
        $comment_url = $core->adminurl->get('admin.comment', array('id' => $rs->comment_id));

        $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        switch ($rs->comment_status) {
            case 1:
                $img_status = sprintf($img, __('Published'), 'check-on.png');
                break;
            case 0:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.png');
                break;
            case -1:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.png');
                break;
            case -2:
                $img_status = sprintf($img, __('Junk'), 'junk.png');
                break;
        }

        echo
        '<tr class="line' . ($rs->comment_status != 1 ? ' offline' : '') . '"' .
        ' id="c' . $rs->comment_id . '">' .

        '<td class="nowrap">' .
        ($has_action ? form::checkbox(array('comments[]'), $rs->comment_id,
            array(
                'extra_html' => 'title="' . __('Select this comment') . '"'
            )
        ) : '') . '</td>' .
        '<td class="maximal">' . $rs->comment_author . '</td>' .
        '<td class="nowrap">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $rs->comment_dt) . '</td>' .
        '<td class="nowrap"><a href="' . $core->adminurl->get('admin.comment', array('ip' => $rs->comment_ip)) . '">' . $rs->comment_ip . '</a></td>' .
        '<td class="nowrap status">' . $img_status . '</td>' .
        '<td class="nowrap status"><a href="' . $comment_url . '">' .
        '<img src="images/edit-mini.png" alt="" title="' . __('Edit this comment') . '" /> ' . __('Edit') . '</a></td>' .

            '</tr>';
    }

    echo '</table>';
}
dcPage::helpBlock('page', 'core_wiki');
?>
</body>
</html>
