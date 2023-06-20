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
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Html;
use Exception;
use form;
use stdClass;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // Init default widgets
        Widgets::init();

        // Loading navigation, extra widgets and custom widgets
        dcCore::app()->admin->widgets_nav = null;
        if (dcCore::app()->blog->settings->widgets->widgets_nav) {
            dcCore::app()->admin->widgets_nav = WidgetsStack::load(dcCore::app()->blog->settings->widgets->widgets_nav);
        }
        dcCore::app()->admin->widgets_extra = null;
        if (dcCore::app()->blog->settings->widgets->widgets_extra) {
            dcCore::app()->admin->widgets_extra = WidgetsStack::load(dcCore::app()->blog->settings->widgets->widgets_extra);
        }
        dcCore::app()->admin->widgets_custom = null;
        if (dcCore::app()->blog->settings->widgets->widgets_custom) {
            dcCore::app()->admin->widgets_custom = WidgetsStack::load(dcCore::app()->blog->settings->widgets->widgets_custom);
        }

        dcCore::app()->admin->append_combo = [
            '-'              => 0,
            __('navigation') => Widgets::WIDGETS_NAV,
            __('extra')      => Widgets::WIDGETS_EXTRA,
            __('custom')     => Widgets::WIDGETS_CUSTOM,
        ];

        # Adding widgets to sidebars
        if (!empty($_POST['append']) && is_array($_POST['addw'])) {
            # Filter selection
            $addw = [];
            foreach ($_POST['addw'] as $k => $v) {
                if (($v == Widgets::WIDGETS_EXTRA || $v == Widgets::WIDGETS_NAV || $v == Widgets::WIDGETS_CUSTOM) && dcCore::app()->widgets->{$k} !== null) {
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
                if (!(dcCore::app()->admin->widgets_nav instanceof WidgetsStack)) {
                    dcCore::app()->admin->widgets_nav = new WidgetsStack();
                }
                if (!(dcCore::app()->admin->widgets_extra instanceof WidgetsStack)) {
                    dcCore::app()->admin->widgets_extra = new WidgetsStack();
                }
                if (!(dcCore::app()->admin->widgets_custom instanceof WidgetsStack)) {
                    dcCore::app()->admin->widgets_custom = new WidgetsStack();
                }

                foreach ($addw as $k => $v) {
                    if (!$wid || $wid == $k) {
                        switch ($v) {
                            case Widgets::WIDGETS_NAV:
                                dcCore::app()->admin->widgets_nav->append(dcCore::app()->widgets->{$k});

                                break;
                            case Widgets::WIDGETS_EXTRA:
                                dcCore::app()->admin->widgets_extra->append(dcCore::app()->widgets->{$k});

                                break;
                            case Widgets::WIDGETS_CUSTOM:
                                dcCore::app()->admin->widgets_custom->append(dcCore::app()->widgets->{$k});

                                break;
                        }
                    }
                }

                try {
                    dcCore::app()->blog->settings->widgets->put('widgets_nav', dcCore::app()->admin->widgets_nav->store());
                    dcCore::app()->blog->settings->widgets->put('widgets_extra', dcCore::app()->admin->widgets_extra->store());
                    dcCore::app()->blog->settings->widgets->put('widgets_custom', dcCore::app()->admin->widgets_custom->store());
                    dcCore::app()->blog->triggerBlog();
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
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
                $_POST['w'] = [];
            }

            try {
                # Removing mark as _rem widgets
                foreach ($_POST['w'] as $nsid => $nsw) {
                    foreach ($nsw as $i => $v) {
                        if (!empty($v['_rem'])) {
                            unset($_POST['w'][$nsid][$i]);

                            continue;
                        }
                    }
                }

                if (!isset($_POST['w'][Widgets::WIDGETS_NAV])) {
                    $_POST['w'][Widgets::WIDGETS_NAV] = [];
                }
                if (!isset($_POST['w'][Widgets::WIDGETS_EXTRA])) {
                    $_POST['w'][Widgets::WIDGETS_EXTRA] = [];
                }
                if (!isset($_POST['w'][Widgets::WIDGETS_CUSTOM])) {
                    $_POST['w'][Widgets::WIDGETS_CUSTOM] = [];
                }

                dcCore::app()->admin->widgets_nav    = WidgetsStack::loadArray($_POST['w'][Widgets::WIDGETS_NAV], dcCore::app()->widgets);
                dcCore::app()->admin->widgets_extra  = WidgetsStack::loadArray($_POST['w'][Widgets::WIDGETS_EXTRA], dcCore::app()->widgets);
                dcCore::app()->admin->widgets_custom = WidgetsStack::loadArray($_POST['w'][Widgets::WIDGETS_CUSTOM], dcCore::app()->widgets);

                dcCore::app()->blog->settings->widgets->put('widgets_nav', dcCore::app()->admin->widgets_nav->store());
                dcCore::app()->blog->settings->widgets->put('widgets_extra', dcCore::app()->admin->widgets_extra->store());
                dcCore::app()->blog->settings->widgets->put('widgets_custom', dcCore::app()->admin->widgets_custom->store());
                dcCore::app()->blog->triggerBlog();

                dcPage::addSuccessNotice(__('Sidebars and their widgets have been saved.'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        } elseif (!empty($_POST['wreset'])) {
            try {
                dcCore::app()->blog->settings->widgets->put('widgets_nav', '');
                dcCore::app()->blog->settings->widgets->put('widgets_extra', '');
                dcCore::app()->blog->settings->widgets->put('widgets_custom', '');
                dcCore::app()->blog->triggerBlog();

                dcPage::addSuccessNotice(__('Sidebars have been resetting.'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $widget_editor = dcCore::app()->auth->getOption('editor');
        $rte_flag      = true;
        $rte_flags     = @dcCore::app()->auth->user_prefs->interface->rte_flags;
        if (is_array($rte_flags) && in_array('widgets_text', $rte_flags)) {
            $rte_flag = $rte_flags['widgets_text'];
        }

        $head = My::cssLoad('style.css') .
            dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
            dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            dcPage::jsJson('widgets', [
                'widget_noeditor' => ($rte_flag ? 0 : 1),
                'msg'             => ['confirm_widgets_reset' => __('Are you sure you want to reset sidebars?')],
            ]) .
            My::jsLoad('widgets.js');

        $user_dm_nodragdrop = dcCore::app()->auth->user_prefs->accessibility->nodragdrop;
        if (!$user_dm_nodragdrop) {
            $head .= My::jsLoad('dragdrop.js');
        }
        if ($rte_flag) {
            # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
            $head .= dcCore::app()->callBehavior(
                'adminPostEditor',
                $widget_editor['xhtml'],
                'widget',
                ['#sidebarsWidgets textarea:not(.noeditor)'],
                'xhtml'
            );
        }
        $head .= dcPage::jsConfirmClose('sidebarsWidgets');

        dcPage::openModule(My::name(), $head);

        echo
        dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                My::name()                                  => '',
            ]
        ) .
        dcPage::notices() .

        # All widgets
        '<form id="listWidgets" action="' . dcCore::app()->admin->getPageURL() . '" method="post"  class="widgets">' .
        '<h3>' . __('Available widgets') . '</h3>' .
        '<p>' . __('Drag widgets from this list to one of the sidebars, for add.') . '</p>' .
        '<ul id="widgets-ref">';

        $j = 0;
        foreach (dcCore::app()->widgets->elements(true) as $w) {
            echo
            '<li>' . form::hidden(['w[void][0][id]'], Html::escapeHTML($w->id())) .
            '<p class="widget-name">' . form::number(['w[void][0][order]'], [
                'default'    => 0,
                'class'      => 'hide',
                'extra_html' => 'title="' . __('order') . '"',
            ]) .
            ' ' . $w->name() .
            ($w->desc() != '' ? ' <span class="form-note">' . __($w->desc()) . '</span>' : '') . '</p>' .
            '<p class="manual-move remove-if-drag"><label class="classic">' . __('Append to:') . '</label> ' .
            form::combo(['addw[' . $w->id() . ']'], dcCore::app()->admin->append_combo) .
            '<input type="submit" name="append[' . $w->id() . ']" value="' . __('Add') . '" /></p>' .
            '<div class="widgetSettings hidden-if-drag">' . $w->formSettings('w[void][0]', $j) . '</div>' .
            '</li>';
            $j++;
        }

        echo
        '</ul>' .
        '<p>' . dcCore::app()->formNonce() . '</p>' .
        '<p class="remove-if-drag"><input type="submit" name="append" value="' . __('Add widgets to sidebars') . '" /></p>' .
        '</form>' .

        '<form id="sidebarsWidgets" action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .

        // Nav sidebar
        '<div id="sidebarNav" class="widgets fieldset">' .
        self::sidebarWidgets('dndnav', __('Navigation sidebar'), dcCore::app()->admin->widgets_nav, Widgets::WIDGETS_NAV, dcCore::app()->default_widgets[Widgets::WIDGETS_NAV], $j) .
        '</div>' .

        // Extra sidebar
        '<div id="sidebarExtra" class="widgets fieldset">' .
        self::sidebarWidgets('dndextra', __('Extra sidebar'), dcCore::app()->admin->widgets_extra, Widgets::WIDGETS_EXTRA, dcCore::app()->default_widgets[Widgets::WIDGETS_EXTRA], $j) .
        '</div>' .

        // Custom sidebar
        '<div id="sidebarCustom" class="widgets fieldset">' .
        self::sidebarWidgets('dndcustom', __('Custom sidebar'), dcCore::app()->admin->widgets_custom, Widgets::WIDGETS_CUSTOM, dcCore::app()->default_widgets[Widgets::WIDGETS_CUSTOM], $j) .
        '</div>' .

        '<p id="sidebarsControl">' .
        dcCore::app()->formNonce() .
        '<input type="submit" name="wup" value="' . __('Update sidebars') . '" /> ' .
        '<input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" /> ' .
        '<input type="submit" class="reset" name="wreset" value="' . __('Reset sidebars') . '" />' .
        '</p>' .
        '</form>';

        $widget_elements          = new stdClass();
        $widget_elements->content = '<dl>';
        foreach (dcCore::app()->widgets->elements() as $w) {
            $widget_elements->content .= '<dt><strong>' . Html::escapeHTML($w->name()) . '</strong> (' .
            __('Widget ID:') . ' <strong>' . Html::escapeHTML($w->id()) . '</strong>)' .
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
                            $s_type = __('boolean') . ', ' . __('possible values:') . ' <code>0</code> ' . __('or') . ' <code>1</code>';

                            break;
                        case 'combo':
                            $s['options'] = array_map(fn ($v) => ($v == '' ? '&lt;' . __('empty string') . '&gt;' : $v), $s['options']);
                            $s_type       = __('listitem') . ', ' . __('possible values:') . ' <code>' . implode('</code>, <code>', $s['options']) . '</code>';

                            break;
                        case 'text':
                        case 'textarea':
                        default:
                            $s_type = __('string');

                            break;
                    }

                    $widget_elements->content .= '<li>' .
                    __('Setting name:') . ' <strong>' . Html::escapeHTML($n) . '</strong>' .
                        ' (' . $s_type . ')' .
                        '</li>';
                }
                $widget_elements->content .= '</ul>';
            }
            $widget_elements->content .= '</dd>';
        }
        $widget_elements->content .= '</dl></div>';

        dcPage::helpBlock(My::id(), $widget_elements);

        dcPage::closeModule();
    }

    protected static function sidebarWidgets($id, $title, $widgets, $pr, $default_widgets, &$j)
    {
        $res = '<h3>' . $title . '</h3>';

        if (!($widgets instanceof WidgetsStack)) {
            $widgets = $default_widgets;
        }

        $res .= '<ul id="' . $id . '" class="connected">' .
        '<li class="empty-widgets" ' . (!$widgets->isEmpty() ? 'style="display: none;"' : '') . '>' .
        __('No widget as far.') .
        '</li>';

        $i = 0;
        foreach ($widgets->elements() as $w) {
            $upDisabled   = $i == 0 ? ' disabled" src="images/disabled_' : '" src="images/';
            $downDisabled = $i == (is_countable($widgets->elements()) ? count($widgets->elements()) : 0) - 1 ? ' disabled" src="images/disabled_' : '" src="images/';
            $altUp        = $i == 0 ? ' alt=""' : ' alt="' . __('Up the widget') . '"';
            $altDown      = $i == (is_countable($widgets->elements()) ? count($widgets->elements()) : 0) - 1 ? ' alt=""' : ' alt="' . __('Down the widget') . '"';

            $iname   = 'w[' . $pr . '][' . $i . ']';
            $offline = $w->isOffline() ? ' offline' : '';

            $res .= '<li>' . form::hidden([$iname . '[id]'], Html::escapeHTML($w->id())) .
            '<p class="widget-name' . $offline . '">' . form::number([$iname . '[order]'], [
                'default'    => $i,
                'class'      => 'hidden',
                'extra_html' => 'title="' . __('order') . '"',
            ]) .
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

        $res .= '</ul>' .
        '<ul class="sortable-delete"' . ($i > 0 ? '' : ' style="display: none;"') . '>' .
        '<li class="sortable-delete-placeholder">' . __('Drag widgets here to remove.') . '</li>' .
        '</ul>';

        return $res;
    }
}
