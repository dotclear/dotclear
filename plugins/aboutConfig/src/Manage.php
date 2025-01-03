<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\aboutConfig;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Decimal;
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Exception;

/**
 * @brief   The module backend manage process.
 * @ingroup aboutConfig
 */
class Manage extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        if (self::status(My::checkContext(My::MANAGE))) {
            App::backend()->part = !empty($_GET['part']) && $_GET['part'] === 'global' ? 'global' : 'local';
        }

        return self::status();
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Local navigation
        if (!empty($_POST['gs_nav'])) {
            My::redirect([], $_POST['gs_nav']);
        }
        if (!empty($_POST['ls_nav'])) {
            My::redirect([], $_POST['ls_nav']);
        }

        // Local settings update
        if (!empty($_POST['s']) && is_array($_POST['s'])) {
            try {
                foreach ($_POST['s'] as $ns => $s) {
                    foreach ($s as $k => $v) {
                        if ($_POST['s_type'][$ns][$k] === App::blogWorkspace()::NS_ARRAY) {
                            $v = json_decode((string) $v, true, 512, JSON_THROW_ON_ERROR);
                        }
                        App::blog()->settings()->$ns->put($k, $v);
                    }
                    App::blog()->triggerBlog();
                }

                Notices::addSuccessNotice(__('Configuration successfully updated'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Global settings update
        if (!empty($_POST['gs']) && is_array($_POST['gs'])) {
            try {
                foreach ($_POST['gs'] as $ns => $s) {
                    foreach ($s as $k => $v) {
                        if ($_POST['gs_type'][$ns][$k] === App::blogWorkspace()::NS_ARRAY) {
                            $v = json_decode((string) $v, true, 512, JSON_THROW_ON_ERROR);
                        }
                        App::blog()->settings()->$ns->put($k, $v, null, null, true, true);
                    }
                    App::blog()->triggerBlog();
                }

                Notices::addSuccessNotice(__('Configuration successfully updated'));
                My::redirect([
                    'part' => 'global',
                ]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(
            My::name(),
            Page::jsPageTabs(App::backend()->part) .
            My::jsLoad('index')
        );

        echo
        Page::breadcrumb(
            [
                __('System')                          => '',
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => '',
            ]
        ) .
        Notices::getNotices();

        echo
        (new Div('local'))
            ->class('multi-part')
            ->title(sprintf(__('Settings for %s'), Html::escapeHTML(App::blog()->name())))
            ->items([
                (new Text('h3', sprintf(__('Settings for %s'), Html::escapeHTML(App::blog()->name()))))->class('out-of-screen-if-js'),
                ... self::settingsTable(false),
            ])
        ->render();

        echo
        (new Div('global'))
            ->class('multi-part')
            ->title(__('Global settings'))
            ->items([
                (new Text('h3', __('Global settings')))->class('out-of-screen-if-js'),
                ... self::settingsTable(true),
            ])
        ->render();

        Page::helpBlock(My::id());

        Page::closeModule();
    }

    /**
     * Return local or global settings forms (menu + settings)
     *
     * @param      bool  $global  The global
     *
     * @return     array<int, Set|Form>
     */
    protected static function settingsTable(bool $global = false): array
    {
        /** @var array<string, BlogWorkspaceInterface> */
        $namespaces = App::blog()->settings()->dumpWorkspaces();
        $settings   = [];
        if ($global) {
            $prefix     = 'g_';
            $prefix_id  = '#' . $prefix;
            $field_name = 'gs';
            $nav_id     = 'gs_nav';
            $submit_id  = 'gs_submit';

            foreach ($namespaces as $ns => $namespace) {
                foreach ($namespace->dumpGlobalSettings() as $k => $v) {
                    $settings[$ns][$k] = $v;
                }
            }
        } else {
            $prefix     = 'l_';
            $prefix_id  = '#' . $prefix;
            $field_name = 's';
            $nav_id     = 'ls_nav';
            $submit_id  = 'ls_submit';

            foreach ($namespaces as $ns => $namespace) {
                foreach ($namespace->dumpSettings() as $k => $v) {
                    $settings[$ns][$k] = $v;
                }
            }
        }

        $elements = [];

        ksort($settings, SORT_FLAG_CASE | SORT_STRING);
        if ($settings !== []) {
            $ns_combo = [];
            foreach (array_keys($settings) as $ns) {
                $ns_combo[$ns] = $prefix_id . $ns;
            }

            $elements[] = (new Form('frm_' . $nav_id))
                ->action(App::backend()->url()->get('admin.plugin'))
                ->method('post')
                ->class('anchor-nav-sticky')
                ->fields([
                    (new Para())
                        ->class('anchor-nav')
                        ->items([
                            (new Select($nav_id))
                                ->label(new Label(__('Goto:'), Label::INSIDE_LABEL_BEFORE))
                                ->class('navigation')
                                ->items($ns_combo),
                            (new Submit($submit_id, __('Ok'))),
                            ...My::hiddenFields(),
                        ]),
                ]);
        }

        $tables = [];
        foreach ($settings as $ns => $s) {
            ksort($s);

            $rows = [];
            foreach ($s as $k => $v) {
                $strong = $global ? false : !$v['global'];
                $rows[] = self::settingLine($k, $v, $ns, $field_name, $strong);
            }
            $table = (new Div())
                ->class('table-outer')
                ->items([
                    (new Table())
                        ->class('settings')
                        ->thead(
                            (new Thead())
                                ->rows([
                                    (new Tr())->cols([
                                        (new Th())->class('nowrap')->scope('col')->text(__('Setting ID')),
                                        (new Th())->scope('col')->text(__('Value')),
                                        (new Th())->scope('col')->text(__('Type')),
                                        (new Th())->scope('col')->text(__('Description')),
                                    ]),
                                ])
                        )
                        ->tbody(
                            (new Tbody())
                                ->rows($rows)
                        ),
                ]);

            $tables[] = (new Form([$submit_id . '_' . $ns . '_form']))
                ->action(App::backend()->url()->get('admin.plugin') . '#' . ($global ? 'global' : 'local') . '.' . $prefix . $ns)
                ->method('post')
                ->fields([
                    (new Details([$prefix . 'setting_details', $prefix . $ns]))
                        ->summary(new Summary($ns))
                        ->items([
                            $table,
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Submit([$submit_id . '_' . $ns . '_post'], __('Save'))),
                                    ...My::hiddenFields(),
                                ]),
                        ]),
                ]);
        }

        $elements[] = (new Set())
            ->items([
                ... $tables,
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        (new Button([$submit_id . '_back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                    ]),
            ]);

        return $elements;
    }

    /**
     * Return table line (tr) for a setting
     *
     * @param      string                   $id            The identifier
     * @param      array<string, mixed>     $s             The setting
     * @param      string                   $ns            The namespace
     * @param      string                   $field_name    The field name
     * @param      bool                     $strong_label  The strong label
     */
    protected static function settingLine(string $id, array $s, string $ns, string $field_name, bool $strong_label): Tr
    {
        $nid = [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id];

        $field = match ((string) $s['type']) {
            // Boolean
            App::blogWorkspace()::NS_BOOL => (new Select($nid))
                ->default($s['value'] ? '1' : '0')
                ->items([__('yes') => '1', __('no') => '0']),

            // Array (JSON encoded)
            App::blogWorkspace()::NS_ARRAY => (new Input($nid))
                ->value(Html::escapeHTML(json_encode($s['value'], JSON_THROW_ON_ERROR)))
                ->size(40),

            // Int
            App::blogWorkspace()::NS_INT => (new Number($nid, null, null, (int) $s['value'])),

            // Float
            App::blogWorkspace()::NS_FLOAT => (new Decimal($nid, null, null, (float) $s['value'])),

            // String, Text
            App::blogWorkspace()::NS_STRING,
            App::blogWorkspace()::NS_TEXT => (new Input($nid))
                ->value(Html::escapeHTML((string) $s['value']))
                ->size(40),

            // Default = String
            default => (new Input($nid))
                ->value(Html::escapeHTML((string) $s['value']))
                ->size(40),
        };

        $type = (new Hidden(
            [$field_name . '_type' . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id . '_type'],
            Html::escapeHTML($s['type'])
        ));

        $label = (new Label(
            sprintf($strong_label ? '<strong>%s</strong>' : '%s', Html::escapeHTML($id)),
            Label::OUTSIDE_LABEL_BEFORE,
            $field_name . '_' . $ns . '_' . $id
        ));

        return (new Tr())
            ->class('line')
            ->items([
                (new Th())->scope('row')->items([$label]),
                (new Td())->items([$field]),
                (new Td())->text($s['type'])->items([$type]),
                (new Td())->text(Html::escapeHTML($s['label'])),
            ]);
    }
}
