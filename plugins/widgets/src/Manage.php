<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Dd;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Dl;
use Dotclear\Helper\Html\Form\Dt;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Image;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Exception;
use stdClass;
use UnhandledMatchError;

/**
 * @brief   The module backend manage process.
 * @ingroup widgets
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Init default widgets
        Widgets::init();

        // Loading navigation, extra widgets and custom widgets
        App::backend()->widgets_nav = null;
        if (is_array(My::settings()->widgets_nav)) {
            App::backend()->widgets_nav = WidgetsStack::load(My::settings()->widgets_nav);
        }
        App::backend()->widgets_extra = null;
        if (is_array(My::settings()->widgets_extra)) {
            App::backend()->widgets_extra = WidgetsStack::load(My::settings()->widgets_extra);
        }
        App::backend()->widgets_custom = null;
        if (is_array(My::settings()->widgets_custom)) {
            App::backend()->widgets_custom = WidgetsStack::load(My::settings()->widgets_custom);
        }

        App::backend()->append_combo = [
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
                if (($v == Widgets::WIDGETS_EXTRA || $v == Widgets::WIDGETS_NAV || $v == Widgets::WIDGETS_CUSTOM) && Widgets::$widgets->{$k} !== null) {
                    $addw[$k] = $v;
                }
            }

            # Append 1 widget
            $wid = false;
            if (gettype($_POST['append']) === 'array' && count($_POST['append']) === 1) {
                $wid = array_keys($_POST['append']);
                $wid = $wid[0];
            }

            # Append widgets
            if ($addw !== []) {
                if (!(App::backend()->widgets_nav instanceof WidgetsStack)) {
                    App::backend()->widgets_nav = new WidgetsStack();
                }
                if (!(App::backend()->widgets_extra instanceof WidgetsStack)) {
                    App::backend()->widgets_extra = new WidgetsStack();
                }
                if (!(App::backend()->widgets_custom instanceof WidgetsStack)) {
                    App::backend()->widgets_custom = new WidgetsStack();
                }

                foreach ($addw as $k => $v) {
                    if (!$wid || $wid == $k) {
                        try {
                            match ($v) {
                                Widgets::WIDGETS_NAV    => App::backend()->widgets_nav->append(Widgets::$widgets->{$k}),
                                Widgets::WIDGETS_EXTRA  => App::backend()->widgets_extra->append(Widgets::$widgets->{$k}),
                                Widgets::WIDGETS_CUSTOM => App::backend()->widgets_custom->append(Widgets::$widgets->{$k}),
                            };
                        } catch (UnhandledMatchError) {
                        }
                    }
                }

                try {
                    My::settings()->put('widgets_nav', App::backend()->widgets_nav->store(), App::blogWorkspace()::NS_ARRAY);
                    My::settings()->put('widgets_extra', App::backend()->widgets_extra->store(), App::blogWorkspace()::NS_ARRAY);
                    My::settings()->put('widgets_custom', App::backend()->widgets_custom->store(), App::blogWorkspace()::NS_ARRAY);
                    App::blog()->triggerBlog();
                    My::redirect();
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        # Removing ?
        $removing = false;
        if (isset($_POST['w']) && is_array($_POST['w'])) {
            foreach ($_POST['w'] as $nsw) {
                foreach ($nsw as $v) {
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

                App::backend()->widgets_nav    = WidgetsStack::loadArray($_POST['w'][Widgets::WIDGETS_NAV], Widgets::$widgets);
                App::backend()->widgets_extra  = WidgetsStack::loadArray($_POST['w'][Widgets::WIDGETS_EXTRA], Widgets::$widgets);
                App::backend()->widgets_custom = WidgetsStack::loadArray($_POST['w'][Widgets::WIDGETS_CUSTOM], Widgets::$widgets);

                My::settings()->put('widgets_nav', App::backend()->widgets_nav->store(), App::blogWorkspace()::NS_ARRAY);
                My::settings()->put('widgets_extra', App::backend()->widgets_extra->store(), App::blogWorkspace()::NS_ARRAY);
                My::settings()->put('widgets_custom', App::backend()->widgets_custom->store(), App::blogWorkspace()::NS_ARRAY);

                App::blog()->triggerBlog();

                Notices::addSuccessNotice(__('Sidebars and their widgets have been saved.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        } elseif (!empty($_POST['wreset'])) {
            # Reset widgets list
            try {
                My::settings()->drop('widgets_nav');
                My::settings()->drop('widgets_extra');
                My::settings()->drop('widgets_custom');

                App::blog()->triggerBlog();

                Notices::addSuccessNotice(__('Sidebars have been resetting.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $widget_editor = App::auth()->getOption('editor');
        $rte_flag      = true;
        $rte_flags     = @App::auth()->prefs()->interface->rte_flags;
        if (is_array($rte_flags) && in_array('widgets_text', $rte_flags)) {
            $rte_flag = $rte_flags['widgets_text'];
        }

        $head = My::cssLoad('style') .
            Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            Page::jsJson('widgets', [
                'widget_noeditor' => ($rte_flag ? 0 : 1),
                'msg'             => [
                    'confirm_widgets_reset' => __('Are you sure you want to reset sidebars?'),
                    'dragdrop_show'         => __('Temporarily display the action buttons for each widget'),
                    'dragdrop_hide'         => __('Hide the action buttons for each widget'),
                ],
            ]) .
            My::jsLoad('widgets');

        $user_dm_nodragdrop = App::auth()->prefs()->accessibility->nodragdrop;
        if (!$user_dm_nodragdrop) {
            $head .= My::jsLoad('dragdrop');
        }
        if ($rte_flag) {
            # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
            $head .= App::behavior()->callBehavior(
                'adminPostEditor',
                $widget_editor['xhtml'],
                'widget',
                ['#sidebarsWidgets textarea:not(.noeditor)'],
                'xhtml'
            );
        }
        $head .= Page::jsConfirmClose('sidebarsWidgets');

        Page::openModule(My::name(), $head);

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => '',
            ]
        );

        echo
        Notices::getNotices();

        // All widgets (chooser)
        $j     = 0;
        $lines = [];
        foreach (Widgets::$widgets->elements(true) as $w) {
            $lines[] = (new Li())
                ->items([
                    (new Hidden(['w[void][0][id]'], Html::escapeHTML($w->id()))),
                    (new Para())
                        ->class('widget-name')
                        ->items([
                            (new Number(['w[void][0][order]']))
                                ->value(0)
                                ->class('hide')
                                ->title(__('order')),
                            (new Text(null, $w->name())),
                            ($w->desc() !== '' ?
                                (new Text('span', __($w->desc())))
                                    ->class('form-note') :
                                (new None())),
                        ]),
                    (new Para())
                        ->class(['form-buttons', 'manual-move', 'hidden-if-drag'])
                        ->items([
                            (new Select(['addw[' . $w->id() . ']']))
                                ->items(App::backend()->append_combo)
                                ->label(new Label(__('Append to:'), Label::IL_TF)),
                            (new Submit(['append[' . $w->id() . ']'], __('Add'))),
                        ]),
                    (new Div())
                        ->class(['widgetSettings', 'hidden-if-drag'])
                        ->items([
                            (new Text(null, $w->formSettings('w[void][0]', $j))),
                        ]),
                ]);

            $j++;
        }

        echo (new Form())
            ->method('post')
            ->action(App::backend()->getPageURL())
            ->id('listWidgets')
            ->class('widgets')
            ->fields([
                (new Text('h3', __('Available widgets'))),
                (new Note())
                    ->text(__('Drag widgets from this list to one of the sidebars, for add.')),
                (new Ul())
                    ->id('widgets-ref')
                    ->items($lines),
                (new Para())
                    ->class(['form-buttons', 'hidden-if-drag'])
                    ->items([
                        ...My::hiddenFields(),
                        (new Submit(['append'], __('Add widgets to sidebars'))),
                    ]),
            ])
        ->render();

        echo (new Form())
            ->method('post')
            ->action(App::backend()->getPageURL())
            ->id('sidebarsWidgets')
            ->fields([
                // Nav sidebar
                (new Div())
                    ->id('sidebarNav')
                    ->class(['widgets', 'fieldset'])
                    ->items([
                        self::sidebarWidgets('dndnav', __('Navigation sidebar'), App::backend()->widgets_nav, Widgets::WIDGETS_NAV, Widgets::$default_widgets[Widgets::WIDGETS_NAV], $j),
                    ]),
                // Extra sidebar
                (new Div())
                    ->id('sidebarExtra')
                    ->class(['widgets', 'fieldset'])
                    ->items([
                        self::sidebarWidgets('dndextra', __('Extra sidebar'), App::backend()->widgets_extra, Widgets::WIDGETS_EXTRA, Widgets::$default_widgets[Widgets::WIDGETS_EXTRA], $j),
                    ]),
                // Custom sidebar
                (new Div())
                    ->id('sidebarCustom')
                    ->class(['widgets', 'fieldset'])
                    ->items([
                        self::sidebarWidgets('dndcustom', __('Custom sidebar'), App::backend()->widgets_custom, Widgets::WIDGETS_CUSTOM, Widgets::$default_widgets[Widgets::WIDGETS_CUSTOM], $j),
                    ]),
                (new Para())
                    ->class('form-buttons')
                    ->id('sidebarsControl')
                    ->items([
                        ...My::hiddenFields(),
                        (new Submit(['wup'], __('Update sidebars'))),
                        (new Button(['_back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                        (new Submit(['wreset'], __('Reset sidebars')))->class('reset'),
                        $user_dm_nodragdrop ?
                            new None() :
                            (new Button(null, __('Temporarily display the action buttons for each widget')))->id('switch-dragndrop'),
                    ]),
            ])
        ->render();

        $elements = [];
        foreach (Widgets::$widgets->elements() as $w) {
            $w_settings = $w->settings();
            if (!count($w_settings)) {
                $definition = (new Note())
                    ->text(__('No setting for this widget'));
            } else {
                $attributes = [];
                foreach ($w_settings as $n => $s) {
                    switch ($s['type']) {
                        case 'check':
                            $s_type = __('boolean') . ', ' . __('possible values:') . ' <code>0</code> ' . __('or') . ' <code>1</code>';

                            break;
                        case 'combo':
                            $s['options'] = array_map(fn ($v): mixed => ($v == '' ? '&lt;' . __('empty string') . '&gt;' : $v), $s['options']);
                            $s_type       = __('listitem') . ', ' . __('possible values:') . ' <code>' . implode('</code>, <code>', $s['options']) . '</code>';

                            break;
                        case 'text':
                        case 'textarea':
                        default:
                            $s_type = __('string');

                            break;
                    }

                    $attributes[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', Html::escapeHTML($n))),
                            (new Text(null, '(' . $s_type . ')')),
                        ]);
                }
                $definition = (new Ul())->items($attributes);
            }

            $elements[] = (new Set())->items([
                (new Dt())
                    ->separator(' ')
                    ->items([
                        (new Text('strong', Html::escapeHTML($w->name()))),
                        (new Text(null, '(' . __('Widget ID:') . ' <code>' . Html::escapeHTML($w->id()) . '</code>)')),
                    ]),
                (new Dd())
                    ->items([
                        $definition,
                    ]),
            ]);
        }

        $widget_elements          = new stdClass();
        $widget_elements->content = (new Dl())
            ->items($elements)
        ->render();

        Page::helpBlock(My::id(), $widget_elements);

        Page::closeModule();
    }

    /**
     * Return HTML code for a list of widgets
     *
     * @param      string           $id               The identifier
     * @param      string           $title            The title
     * @param      WidgetsStack     $widgets          The widgets
     * @param      string           $pr               The widget group id
     * @param      WidgetsStack     $default_widgets  The default widgets
     * @param      int              $j                Current widget counter
     */
    protected static function sidebarWidgets(string $id, string $title, ?WidgetsStack $widgets, string $pr, WidgetsStack $default_widgets, int &$j): Set
    {
        if (!($widgets instanceof WidgetsStack)) {
            $widgets = $default_widgets;
        }

        $i     = 0;
        $lines = [];
        foreach ($widgets->elements() as $w) {
            $upDisabled   = $i === 0;
            $downDisabled = $i == count($widgets->elements()) - 1;

            $iname   = 'w[' . $pr . '][' . $i . ']';
            $offline = $w->isOffline() ? ' offline' : '';

            $lines[] = (new Li())
                ->items([
                    (new Hidden([$iname . '[id]'], Html::escapeHTML($w->id()))),
                    (new Para())->class(['widget-name', 'clear', $offline])
                        ->items([
                            (new Number([$iname . '[order]']))
                                ->value($i)
                                ->class('hidden')
                                ->title(__('order')),
                            (new Text(null, $w->name())),
                            ($w->desc() !== '' ?
                                (new Text('span', __($w->desc())))
                                    ->class('form-note') :
                                (new None())),
                            (new Para(null, 'span'))
                                ->class(['toolsWidget', 'hidden-if-drag'])
                                ->items([
                                    (new Image('images/' . ($upDisabled ? 'disabled_' : '') . 'up.svg', [$iname . '[_up]']))
                                        ->class('upWidget')
                                        ->disabled($upDisabled)
                                        ->alt(__('Up the widget'))
                                        ->title(__('Up the widget')),
                                    (new Image('images/' . ($downDisabled ? 'disabled_' : '') . 'down.svg', [$iname . '[_down]']))
                                        ->class('downWidget')
                                        ->disabled($downDisabled)
                                        ->alt(__('Down the widget'))
                                        ->title(__('Down the widget')),
                                    (new Image('images/trash.svg', [$iname . '[_rem]']))
                                        ->class('removeWidget')
                                        ->alt(__('Remove the widget'))
                                        ->title(__('Remove the widget')),
                                ]),
                        ]),
                    (new Div())
                        ->class(['widgetSettings', 'hidden-if-drag'])
                        ->items([
                            (new Text(null, $w->formSettings($iname, $j))),
                        ]),
                ]);

            $i++;
            $j++;
        }

        return (new Set())
            ->items([
                (new Text('h3', $title)),
                (new Ul())
                    ->id($id)
                    ->class('connected')
                    ->items([
                        (new Li())
                            ->class('empty-widgets')
                            ->extra($widgets->isEmpty() ? '' : 'style="display: none;"')
                            ->text(__('No widget as far.')),
                        ...$lines,
                    ]),
                (new Ul())
                    ->class('sortable-delete')
                    ->extra($i > 0 ? '' : 'style="display: none;"')
                    ->items([
                        (new Li())
                            ->class('sortable-delete-placeholder')
                            ->text(__('Drag widgets here to remove.')),
                    ]),
            ]);
    }
}
