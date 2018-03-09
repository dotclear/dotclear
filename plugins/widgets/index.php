<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

include dirname(__FILE__) . '/_default_widgets.php';

# Loading navigation, extra widgets and custom widgets
$widgets_nav = null;
if ($core->blog->settings->widgets->widgets_nav) {
    $widgets_nav = dcWidgets::load($core->blog->settings->widgets->widgets_nav);
}
$widgets_extra = null;
if ($core->blog->settings->widgets->widgets_extra) {
    $widgets_extra = dcWidgets::load($core->blog->settings->widgets->widgets_extra);
}
$widgets_custom = null;
if ($core->blog->settings->widgets->widgets_custom) {
    $widgets_custom = dcWidgets::load($core->blog->settings->widgets->widgets_custom);
}

$append_combo = array(
    '-'              => 0,
    __('navigation') => 'nav',
    __('extra')      => 'extra',
    __('custom')     => 'custom'
);

function literalNullString($v)
{
    if ($v == '') {
        return '&lt;' . __('empty string') . '&gt;';
    }
    return $v;
}

# Adding widgets to sidebars
if (!empty($_POST['append']) && is_array($_POST['addw'])) {
    # Filter selection
    $addw = array();
    foreach ($_POST['addw'] as $k => $v) {
        if (($v == 'extra' || $v == 'nav' || $v == 'custom') && $__widgets->{$k} !== null) {
            $addw[$k] = $v;
        }
    }

    # Append 1 widget
    $wid = false;
    if (gettype($_POST['append']) == 'array' && count($_POST['append']) == 1) {
        $wid = array_keys($_POST['append']);
        $wid = $wid[0];
    }

    # Append widgets
    if (!empty($addw)) {
        if (!($widgets_nav instanceof dcWidgets)) {
            $widgets_nav = new dcWidgets;
        }
        if (!($widgets_extra instanceof dcWidgets)) {
            $widgets_extra = new dcWidgets();
        }
        if (!($widgets_custom instanceof dcWidgets)) {
            $widgets_custom = new dcWidgets();
        }

        foreach ($addw as $k => $v) {
            if (!$wid || $wid == $k) {
                switch ($v) {
                    case 'nav':
                        $widgets_nav->append($__widgets->{$k});
                        break;
                    case 'extra':
                        $widgets_extra->append($__widgets->{$k});
                        break;
                    case 'custom':
                        $widgets_custom->append($__widgets->{$k});
                        break;
                }
            }
        }

        try {
            $core->blog->settings->addNamespace('widgets');
            $core->blog->settings->widgets->put('widgets_nav', $widgets_nav->store());
            $core->blog->settings->widgets->put('widgets_extra', $widgets_extra->store());
            $core->blog->settings->widgets->put('widgets_custom', $widgets_custom->store());
            $core->blog->triggerBlog();
            http::redirect($p_url);
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

# Removing ?
$removing = false;
if (isset($_POST['w']) && is_array($_POST['w'])) {
    foreach ($_POST['w'] as $nsid => $nsw) {
        foreach ($nsw as $i => $v) {
            if (!empty($v['_rem'])) {
                $removing = true;
                break 2;
            }
        }
    }
}

# Move ?
$move = false;
if (isset($_POST['w']) && is_array($_POST['w'])) {
    foreach ($_POST['w'] as $nsid => $nsw) {
        foreach ($nsw as $i => $v) {
            if (!empty($v['_down'])) {
                $oldorder = $_POST['w'][$nsid][$i]['order'];
                $neworder = $oldorder + 1;
                if (isset($_POST['w'][$nsid][$neworder])) {
                    $_POST['w'][$nsid][$i]['order']        = $neworder;
                    $_POST['w'][$nsid][$neworder]['order'] = $oldorder;
                    $move                                  = true;
                }
            }
            if (!empty($v['_up'])) {
                $oldorder = $_POST['w'][$nsid][$i]['order'];
                $neworder = $oldorder - 1;
                if (isset($_POST['w'][$nsid][$neworder])) {
                    $_POST['w'][$nsid][$i]['order']        = $neworder;
                    $_POST['w'][$nsid][$neworder]['order'] = $oldorder;
                    $move                                  = true;
                }
            }
        }
    }
}

# Update sidebars
if (!empty($_POST['wup']) || $removing || $move) {
    if (!isset($_POST['w']) || !is_array($_POST['w'])) {
        $_POST['w'] = array();
    }

    try
    {
        # Removing mark as _rem widgets
        foreach ($_POST['w'] as $nsid => $nsw) {
            foreach ($nsw as $i => $v) {
                if (!empty($v['_rem'])) {
                    unset($_POST['w'][$nsid][$i]);
                    continue;
                }
            }
        }

        if (!isset($_POST['w']['nav'])) {
            $_POST['w']['nav'] = array();
        }
        if (!isset($_POST['w']['extra'])) {
            $_POST['w']['extra'] = array();
        }
        if (!isset($_POST['w']['custom'])) {
            $_POST['w']['custom'] = array();
        }

        $widgets_nav    = dcWidgets::loadArray($_POST['w']['nav'], $__widgets);
        $widgets_extra  = dcWidgets::loadArray($_POST['w']['extra'], $__widgets);
        $widgets_custom = dcWidgets::loadArray($_POST['w']['custom'], $__widgets);

        $core->blog->settings->addNamespace('widgets');
        $core->blog->settings->widgets->put('widgets_nav', $widgets_nav->store());
        $core->blog->settings->widgets->put('widgets_extra', $widgets_extra->store());
        $core->blog->settings->widgets->put('widgets_custom', $widgets_custom->store());
        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(__('Sidebars and their widgets have been saved.'));
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
} elseif (!empty($_POST['wreset'])) {
    try
    {
        $core->blog->settings->addNamespace('widgets');
        $core->blog->settings->widgets->put('widgets_nav', '');
        $core->blog->settings->widgets->put('widgets_extra', '');
        $core->blog->settings->widgets->put('widgets_custom', '');
        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(__('Sidebars have been resetting.'));
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}
?>
<html>
<head>
  <title><?php echo __('Widgets'); ?></title>
<?php
echo dcPage::cssLoad(dcPage::getPF('widgets/style.css')) .
dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
dcPage::jsLoad(dcPage::getPF('widgets/js/widgets.js'));

$core->auth->user_prefs->addWorkspace('accessibility');
$user_dm_nodragdrop = $core->auth->user_prefs->accessibility->nodragdrop;
if (!$user_dm_nodragdrop) {
    echo dcPage::jsLoad(dcPage::getPF('widgets/js/dragdrop.js'));
}
echo dcPage::jsVars(array('dotclear.msg.confirm_widgets_reset' => __('Are you sure you want to reset sidebars?')));

$widget_editor = $core->auth->getOption('editor');
$rte_flag      = true;
$rte_flags     = @$core->auth->user_prefs->interface->rte_flags;
if (is_array($rte_flags) && in_array('widgets_text', $rte_flags)) {
    $rte_flag = $rte_flags['widgets_text'];
}
if ($rte_flag) {
    echo $core->callBehavior('adminPostEditor', $widget_editor['xhtml'], 'widget', array('#sidebarsWidgets textarea'), 'xhtml');
}
echo (dcPage::jsConfirmClose('sidebarsWidgets'));
?>
</head>
<body>
<?php
echo dcPage::breadcrumb(
    array(
        html::escapeHTML($core->blog->name) => '',
        __('Widgets')                       => ''
    )) .
dcPage::notices();

# All widgets
echo
'<form id="listWidgets" action="' . $p_url . '" method="post"  class="widgets">' .
'<h3>' . __('Available widgets') . '</h3>' .
'<p>' . __('Drag widgets from this list to one of the sidebars, for add.') . '</p>' .
    '<ul id="widgets-ref">';

$j = 0;
foreach ($__widgets->elements(true) as $w) {
    echo
    '<li>' . form::hidden(array('w[void][0][id]'), html::escapeHTML($w->id())) .
    '<p class="widget-name">' . form::number(array('w[void][0][order]'), array(
        'default'    => 0,
        'class'      => 'hide',
        'extra_html' => 'title="' . __('order') . '"'
    )) .
    ' ' . $w->name() .
    ($w->desc() != '' ? ' <span class="form-note">' . __($w->desc()) . '</span>' : '') . '</p>' .
    '<p class="manual-move remove-if-drag"><label class="classic">' . __('Append to:') . '</label> ' .
    form::combo(array('addw[' . $w->id() . ']'), $append_combo) .
    '<input type="submit" name="append[' . $w->id() . ']" value="' . __('Add') . '" /></p>' .
    '<div class="widgetSettings hidden-if-drag">' . $w->formSettings('w[void][0]', $j) . '</div>' .
        '</li>';
    $j++;
}

echo
'</ul>' .
'<p>' . $core->formNonce() . '</p>' .
'<p class="remove-if-drag"><input type="submit" name="append" value="' . __('Add widgets to sidebars') . '" /></p>' .
    '</form>';

echo '<form id="sidebarsWidgets" action="' . $p_url . '" method="post">';
# Nav sidebar
echo
'<div id="sidebarNav" class="widgets fieldset">' .
sidebarWidgets('dndnav', __('Navigation sidebar'), $widgets_nav, 'nav', $__default_widgets['nav'], $j);
echo '</div>';

# Extra sidebar
echo
'<div id="sidebarExtra" class="widgets fieldset">' .
sidebarWidgets('dndextra', __('Extra sidebar'), $widgets_extra, 'extra', $__default_widgets['extra'], $j);
echo '</div>';

# Custom sidebar
echo
'<div id="sidebarCustom" class="widgets fieldset">' .
sidebarWidgets('dndcustom', __('Custom sidebar'), $widgets_custom, 'custom', $__default_widgets['custom'], $j);
echo '</div>';

echo
'<p id="sidebarsControl">' .
$core->formNonce() .
'<input type="submit" name="wup" value="' . __('Update sidebars') . '" /> ' .
'<input type="submit" class="reset" name="wreset" value="' . __('Reset sidebars') . '" /></p>' .
    '</form>';

$widget_elements          = new stdClass;
$widget_elements->content = '<dl>';
foreach ($__widgets->elements() as $w) {
    $widget_elements->content .=
    '<dt><strong>' . html::escapeHTML($w->name()) . '</strong> (' .
    __('Widget ID:') . ' <strong>' . html::escapeHTML($w->id()) . '</strong>)' .
        ($w->desc() != '' ? ' <span class="form-note">' . __($w->desc()) . '</span>' : '') . '</dt>' .
        '<dd>';

    $w_settings = $w->settings();
    if (empty($w_settings)) {
        $widget_elements->content .= '<p>' . __('No setting for this widget') . '</p>';
    } else {
        $widget_elements->content .= '<ul>';
        foreach ($w->settings() as $n => $s) {
            switch ($s['type']) {
                case 'check':
                    $s_type = __('boolean') . ", " . __('possible values:') . " <code>0</code> " . __('or') . " <code>1</code>";
                    break;
                case 'combo':
                    $s['options'] = array_map("literalNullString", $s['options']);
                    $s_type       = __('listitem') . ", " . __('possible values:') . " <code>" . implode('</code>, <code>', $s['options']) . "</code>";
                    break;
                case 'text':
                case 'textarea':
                default:
                    $s_type = __('string');
                    break;
            }

            $widget_elements->content .=
            '<li>' .
            __('Setting name:') . ' <strong>' . html::escapeHTML($n) . '</strong>' .
                ' (' . $s_type . ')' .
                '</li>';
        }
        $widget_elements->content .= '</ul>';
    }
    $widget_elements->content .= '</dd>';
}
$widget_elements->content .= '</dl></div>';

dcPage::helpBlock('widgets', $widget_elements);

function sidebarWidgets($id, $title, $widgets, $pr, $default_widgets, &$j)
{
    $res = '<h3>' . $title . '</h3>';

    if (!($widgets instanceof dcWidgets)) {
        $widgets = $default_widgets;
    }

    $res .= '<ul id="' . $id . '" class="connected">';

    $res .= '<li class="empty-widgets" ' . (!$widgets->isEmpty() ? 'style="display: none;"' : '') . '>' . __('No widget as far.') . '</li>';

    $i = 0;
    foreach ($widgets->elements() as $w) {
        $upDisabled   = $i == 0 ? ' disabled" src="images/disabled_' : '" src="images/';
        $downDisabled = $i == count($widgets->elements()) - 1 ? ' disabled" src="images/disabled_' : '" src="images/';
        $altUp        = $i == 0 ? ' alt=""' : ' alt="' . __('Up the widget') . '"';
        $altDown      = $i == count($widgets->elements()) - 1 ? ' alt=""' : ' alt="' . __('Down the widget') . '"';

        $iname = 'w[' . $pr . '][' . $i . ']';

        $res .=
        '<li>' . form::hidden(array($iname . '[id]'), html::escapeHTML($w->id())) .
        '<p class="widget-name">' . form::number(array($iname . '[order]'), array(
            'default'    => $i,
            'class'      => 'hidden',
            'extra_html' => 'title="' . __('order') . '"'
        )) .
        ' ' . $w->name() .
        ($w->desc() != '' ? ' <span class="form-note">' . __($w->desc()) . '</span>' : '') .
        '<span class="toolsWidget remove-if-drag">' .
        '<input type="image" class="upWidget' . $upDisabled . 'up.png" name="' . $iname . '[_up]" value="' . __('Up the widget') . '"' . $altUp . ' /> ' .
        '<input type="image" class="downWidget' . $downDisabled . 'down.png" name="' . $iname . '[_down]" value="' . __('Down the widget') . '"' . $altDown . ' /> ' . ' ' .
        '<input type="image" class="removeWidget" src="images/trash.png" name="' . $iname . '[_rem]" value="' . __('Remove widget') . '" alt="' . __('Remove the widget') . '" />' .
        '</span>' .
        '<br class="clear"/></p>' .
        '<div class="widgetSettings hidden-if-drag">' . $w->formSettings($iname, $j) . '</div>' .
            '</li>';

        $i++;
        $j++;
    }

    $res .= '</ul>';

    $res .= '<ul class="sortable-delete"' . ($i > 0 ? '' : ' style="display: none;"') . '><li class="sortable-delete-placeholder">' .
    __('Drag widgets here to remove.') . '</li></ul>';

    return $res;
}
?>
</body>
</html>
