<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}
dcPage::check('admin');

dcAntispam::initFilters();
$filters = dcAntispam::$filters->getFilters();

$page_name   = __('Antispam');
$filter_gui  = false;
$default_tab = null;

try
{
    # Show filter configuration GUI
    if (!empty($_GET['f'])) {
        if (!isset($filters[$_GET['f']])) {
            throw new Exception(__('Filter does not exist.'));
        }

        if (!$filters[$_GET['f']]->hasGUI()) {
            throw new Exception(__('Filter has no user interface.'));
        }

        $filter     = $filters[$_GET['f']];
        $filter_gui = $filter->gui($filter->guiURL());
    }

    # Remove all spam
    if (!empty($_POST['delete_all'])) {
        $ts = dt::str('%Y-%m-%d %H:%M:%S', $_POST['ts'], $core->blog->settings->system->blog_timezone);

        dcAntispam::delAllSpam($core, $ts);

        dcPage::addSuccessNotice(__('Spam comments have been successfully deleted.'));
        http::redirect($p_url);
    }

    # Update filters
    if (isset($_POST['filters_upd'])) {
        $filters_opt = array();
        $i           = 0;
        foreach ($filters as $fid => $f) {
            $filters_opt[$fid] = array(false, $i);
            $i++;
        }

        # Enable active filters
        if (isset($_POST['filters_active']) && is_array($_POST['filters_active'])) {
            foreach ($_POST['filters_active'] as $v) {
                $filters_opt[$v][0] = true;
            }
        }

        # Order filters
        if (!empty($_POST['f_order']) && empty($_POST['filters_order'])) {
            $order = $_POST['f_order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['filters_order'])) {
            $order = explode(',', trim($_POST['filters_order'], ','));
        }

        if (isset($order)) {
            foreach ($order as $i => $f) {
                $filters_opt[$f][1] = $i;
            }
        }

        # Set auto delete flag
        if (isset($_POST['filters_auto_del']) && is_array($_POST['filters_auto_del'])) {
            foreach ($_POST['filters_auto_del'] as $v) {
                $filters_opt[$v][2] = true;
            }
        }

        dcAntispam::$filters->saveFilterOpts($filters_opt);

        dcPage::addSuccessNotice(__('Filters configuration has been successfully saved.'));
        http::redirect($p_url);
    }
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
?>
<html>
<head>
  <title><?php echo ($filter_gui !== false ? sprintf(__('%s configuration'), $filter->name) . ' - ' : '') . $page_name; ?></title>
  <script type="text/javascript">
  <?php
echo dcPage::jsVar('dotclear.msg.confirm_spam_delete', __('Are you sure you want to delete all spams?'));
?>
  </script>
  <?php
echo dcPage::jsPageTabs($default_tab);
$core->auth->user_prefs->addWorkspace('accessibility');
if (!$core->auth->user_prefs->accessibility->nodragdrop) {
    echo
    dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
    dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
    dcPage::jsLoad(dcPage::getPF('antispam/js/antispam.js'));
}
echo dcPage::cssLoad(dcPage::getPF('antispam/style.css'));
?>
</head>
<body>
<?php

if ($filter_gui !== false) {
    echo dcPage::breadcrumb(
        array(
            __('Plugins')                                         => '',
            $page_name                                            => $p_url,
            sprintf(__('%s filter configuration'), $filter->name) => ''
        )) .
    dcPage::notices();

    echo '<p><a href="' . $p_url . '" class="back">' . __('Back to filters list') . '</a></p>';

    echo $filter_gui;

    if ($filter->help) {
        dcPage::helpBlock($filter->help);
    }
} else {
    echo dcPage::breadcrumb(
        array(
            __('Plugins') => '',
            $page_name    => ''
        )) .
    dcPage::notices();

    # Information
    $spam_count      = dcAntispam::countSpam($core);
    $published_count = dcAntispam::countPublishedComments($core);
    $moderationTTL   = $core->blog->settings->antispam->antispam_moderation_ttl;

    echo
    '<form action="' . $p_url . '" method="post" class="fieldset">' .
    '<h3>' . __('Information') . '</h3>';

    echo
    '<ul class="spaminfo">' .
    '<li class="spamcount"><a href="' . $core->adminurl->get('admin.comments', array('status' => '-2')) . '">' . __('Junk comments:') . '</a> ' .
    '<strong>' . $spam_count . '</strong></li>' .
    '<li class="hamcount"><a href="' . $core->adminurl->get('admin.comments', array('status' => '1')) . '">' . __('Published comments:') . '</a> ' .
        $published_count . '</li>' .
        '</ul>';

    if ($spam_count > 0) {
        echo
        '<p>' . $core->formNonce() .
        form::hidden('ts', time()) .
        '<input name="delete_all" class="delete" type="submit" value="' . __('Delete all spams') . '" /></p>';
    }
    if ($moderationTTL != null && $moderationTTL >= 0) {
        echo '<p>' . sprintf(__('All spam comments older than %s day(s) will be automatically deleted.'), $moderationTTL) . ' ' .
        sprintf(__('You can modify this duration in the %s'), '<a href="' . $core->adminurl->get('admin.blog.pref') .
            '#antispam_moderation_ttl"> ' . __('Blog settings') . '</a>') .
            '.</p>';
    }
    echo '</form>';

    # Filters
    echo
        '<form action="' . $p_url . '" method="post" id="filters-list-form">';

    if (!empty($_GET['upd'])) {
        dcPage::success(__('Filters configuration has been successfully saved.'));
    }

    echo
    '<div class="table-outer">' .
    '<table class="dragable">' .
    '<caption class="as_h3">' . __('Available spam filters') . '</caption>' .
    '<thead><tr>' .
    '<th>' . __('Order') . '</th>' .
    '<th>' . __('Active') . '</th>' .
    '<th>' . __('Auto Del.') . '</th>' .
    '<th class="nowrap">' . __('Filter name') . '</th>' .
    '<th colspan="2">' . __('Description') . '</th>' .
        '</tr></thead>' .
        '<tbody id="filters-list" >';

    $i = 0;
    foreach ($filters as $fid => $f) {
        $gui_link = '&nbsp;';
        if ($f->hasGUI()) {
            $gui_link =
            '<a href="' . html::escapeHTML($f->guiURL()) . '">' .
            '<img src="images/edit-mini.png" alt="' . __('Filter configuration') . '" ' .
            'title="' . __('Filter configuration') . '" /></a>';
        }

        echo
        '<tr class="line' . ($f->active ? '' : ' offline') . '" id="f_' . $fid . '">' .
        '<td class="handle">' . form::number(array('f_order[' . $fid . ']'), array(
            'min'        => 0,
            'default'    => $i,
            'class'      => 'position',
            'extra_html' => 'title="' . __('position') . '"'
        )) .
        '</td>' .
        '<td class="nowrap">' . form::checkbox(array('filters_active[]'), $fid,
            array(
                'checked'    => $f->active,
                'extra_html' => 'title="' . __('Active') . '"'
            )
        ) . '</td>' .
        '<td class="nowrap">' . form::checkbox(array('filters_auto_del[]'), $fid,
            array(
                'checked'    => $f->auto_delete,
                'extra_html' => 'title="' . __('Auto Del.') . '"'
            )
        ) . '</td>' .
        '<td class="nowrap" scope="row">' . $f->name . '</td>' .
        '<td class="maximal">' . $f->description . '</td>' .
            '<td class="status">' . $gui_link . '</td>' .
            '</tr>';
        $i++;
    }
    echo
    '</tbody></table></div>' .
    '<p>' . form::hidden('filters_order', '') .
    $core->formNonce() .
    '<input type="submit" name="filters_upd" value="' . __('Save') . '" /></p>' .
        '</form>';

    # Syndication
    if (DC_ADMIN_URL) {
        $ham_feed = $core->blog->url . $core->url->getURLFor(
            'hamfeed',
            $code = dcAntispam::getUserCode($core)
        );
        $spam_feed = $core->blog->url . $core->url->getURLFor(
            'spamfeed',
            $code = dcAntispam::getUserCode($core)
        );

        echo
        '<h3>' . __('Syndication') . '</h3>' .
        '<ul class="spaminfo">' .
        '<li class="feed"><a href="' . $spam_feed . '">' . __('Junk comments RSS feed') . '</a></li>' .
        '<li class="feed"><a href="' . $ham_feed . '">' . __('Published comments RSS feed') . '</a></li>' .
            '</ul>';
    }

    dcPage::helpBlock('antispam', 'antispam-filters');
}

?>

</body>
</html>
