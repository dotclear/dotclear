<?php
/**
 * @brief aboutConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\aboutConfig;

use Exception;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\BlogWorkspace;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use form;

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
                        if ($_POST['s_type'][$ns][$k] === BlogWorkspace::NS_ARRAY) {
                            $v = json_decode($v, true, 512, JSON_THROW_ON_ERROR);
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
                        if ($_POST['gs_type'][$ns][$k] === BlogWorkspace::NS_ARRAY) {
                            $v = json_decode($v, true, 512, JSON_THROW_ON_ERROR);
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
            My::jsLoad('index.js')
        );

        echo
        Page::breadcrumb(
            [
                __('System')                        => '',
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                          => '',
            ]
        ) .
        Notices::getNotices() .
        '<div id="local" class="multi-part" title="' . sprintf(__('Settings for %s'), Html::escapeHTML(App::blog()->name())) . '">' .
        '<h3 class="out-of-screen-if-js">' . sprintf(__('Settings for %s'), Html::escapeHTML(App::blog()->name())) . '</h3>';

        self::settingsTable(false);

        echo
        '</div>' .

        '<div id="global" class="multi-part" title="' . __('Global settings') . '">' .
        '<h3 class="out-of-screen-if-js">' . __('Global settings') . '</h3>';

        self::settingsTable(true);

        echo
        '</div>';

        Page::helpBlock(My::id());

        Page::closeModule();
    }

    /**
     * Display local or global settings
     *
     * @param      bool  $global  The global
     */
    protected static function settingsTable(bool $global = false): void
    {
        $table_header = '<div class="table-outer">' .
            '<table class="settings" id="%s"><caption class="as_h3">%s</caption>' .
            '<thead>' .
            '<tr>' . "\n" .
            '  <th class="nowrap">' . __('Setting ID') . '</th>' . "\n" .
            '  <th>' . __('Value') . '</th>' . "\n" .
            '  <th>' . __('Type') . '</th>' . "\n" .
            '  <th>' . __('Description') . '</th>' . "\n" .
                '</tr>' . "\n" .
                '</thead>' . "\n" .
                '<tbody>';
        $table_footer = '</tbody></table></div>';

        /** @var array<string|BlogWorkspace> */
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

        ksort($settings, SORT_FLAG_CASE | SORT_STRING);
        if (count($settings)) {
            $ns_combo = [];
            foreach ($settings as $ns => $s) {
                $ns_combo[$ns] = $prefix_id . $ns;
            }
            echo
            '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post" class="anchor-nav-sticky">' .
            '<p class="anchor-nav">' .
            '<label for="' . $nav_id . '" class="classic">' . __('Goto:') . '</label> ' .
            form::combo($nav_id, $ns_combo, ['class' => 'navigation']) .
            ' <input type="submit" value="' . __('Ok') . '" id="' . $submit_id . '" />' .
            '<input type="hidden" name="p" value="' . My::id() . '" />' .
            App::nonce()->getFormNonce() .
            '</p></form>';
        }

        echo
        '<form action="' . App::backend()->url->get('admin.plugin') . '" method="post">';
        foreach ($settings as $ns => $s) {
            ksort($s);
            echo sprintf($table_header, $prefix . $ns, $ns);
            foreach ($s as $k => $v) {
                $strong = $global ? false : !$v['global'];
                echo self::settingLine($k, $v, $ns, $field_name, $strong);
            }
            echo $table_footer;
        }

        echo
        '<p><input type="submit" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '<input type="hidden" name="p" value="' . My::id() . '" />' .
        App::nonce()->getFormNonce() .
        '</p>' .
        '</form>';
    }

    /**
     * Return table line (td) to display a setting
     *
     * @param      string  $id            The identifier
     * @param      array   $s             The setting
     * @param      string  $ns            The namespace
     * @param      string  $field_name    The field name
     * @param      bool    $strong_label  The strong label
     *
     * @return     string
     */
    protected static function settingLine(string $id, array $s, string $ns, string $field_name, bool $strong_label): string
    {
        $field = match ((string) $s['type']) {
            BlogWorkspace::NS_BOOL, BlogWorkspace::NS_BOOLEAN => form::combo(
                [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                [__('yes') => 1, __('no') => 0],
                $s['value'] ? 1 : 0
            ),

            BlogWorkspace::NS_ARRAY => form::field(
                [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                40,
                null,
                Html::escapeHTML(json_encode($s['value'], JSON_THROW_ON_ERROR))
            ),

            BlogWorkspace::NS_INTEGER, BlogWorkspace::NS_INT, BlogWorkspace::NS_FLOAT => form::number(
                [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                null,
                null,
                Html::escapeHTML((string) $s['value'])
            ),

            //BlogWorkspace::NS_STRING, BlogWorkspace::NS_TEXT,
            default => form::field(
                [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                40,
                null,
                Html::escapeHTML((string) $s['value'])
            ),
        };

        $type = form::hidden(
            [$field_name . '_type' . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id . '_type'],
            Html::escapeHTML($s['type'])
        );

        $slabel = $strong_label ? '<strong>%s</strong>' : '%s';

        return
            '<tr class="line">' .
            '<td scope="row"><label for="' . $field_name . '_' . $ns . '_' . $id . '">' . sprintf($slabel, Html::escapeHTML($id)) . '</label></td>' .
            '<td>' . $field . '</td>' .
            '<td>' . $s['type'] . $type . '</td>' .
            '<td>' . Html::escapeHTML($s['label']) . '</td>' .
            '</tr>';
    }
}
