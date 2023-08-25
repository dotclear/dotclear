<?php
/**
 * @brief userPref, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\userPref;

use Exception;
use dcCore;
use dcWorkspace;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
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
        if (My::checkContext(My::MANAGE)) {
            dcCore::app()->admin->part = !empty($_GET['part']) && $_GET['part'] == 'global' ? 'global' : 'local';
            self::status(true);
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
        if (!empty($_POST['gp_nav'])) {
            My::redirect([], $_POST['gp_nav']);
            exit;
        }
        if (!empty($_POST['lp_nav'])) {
            My::redirect([], $_POST['lp_nav']);
            exit;
        }

        // Local prefs update
        if (!empty($_POST['s']) && is_array($_POST['s'])) {
            try {
                foreach ($_POST['s'] as $ws => $s) {
                    foreach ($s as $k => $v) {
                        if ($_POST['s_type'][$ws][$k] === dcWorkspace::WS_ARRAY) {
                            $v = json_decode($v, true, 512, JSON_THROW_ON_ERROR);
                        }
                        dcCore::app()->auth->user_prefs->$ws->put($k, $v);
                    }
                }

                Notices::addSuccessNotice(__('Preferences successfully updated'));
                My::redirect();
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Global prefs update
        if (!empty($_POST['gs']) && is_array($_POST['gs'])) {
            try {
                foreach ($_POST['gs'] as $ws => $s) {
                    foreach ($s as $k => $v) {
                        if ($_POST['gs_type'][$ws][$k] === dcWorkspace::WS_ARRAY) {
                            $v = json_decode($v, true, 512, JSON_THROW_ON_ERROR);
                        }
                        dcCore::app()->auth->user_prefs->$ws->put($k, $v, null, null, true, true);
                    }
                }

                Notices::addSuccessNotice(__('Preferences successfully updated'));
                My::redirect(['part' => 'global']);
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
        Page::openModule(
            My::name(),
            Page::jsPageTabs(dcCore::app()->admin->part) .
            My::jsLoad('index')
        );

        echo
        Page::breadcrumb(
            [
                __('System')                                    => '',
                Html::escapeHTML(dcCore::app()->auth->userID()) => '',
                My::name()                                      => '',
            ]
        ) .
        Notices::getNotices() .
        '<div id="local" class="multi-part" title="' . __('User preferences') . '">' .
        '<h3 class="out-of-screen-if-js">' . __('User preferences') . '</h3>';

        self::prefsTable(false);

        echo
        '</div>' .

        '<div id="global" class="multi-part" title="' . __('Global preferences') . '">' .
        '<h3 class="out-of-screen-if-js">' . __('Global preferences') . '</h3>';

        self::prefsTable(true);

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
    protected static function prefsTable(bool $global = false): void
    {
        $table_header = '<div class="table-outer"><table class="prefs" id="%s"><caption class="as_h3">%s</caption>' .
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

        /** @var array<string|dcWorkspace> */
        $workspaces = dcCore::app()->auth->user_prefs->dumpWorkspaces();
        $prefs      = [];
        if ($global) {
            $prefix     = 'g_';
            $prefix_id  = '#' . $prefix;
            $field_name = 'gs';
            $nav_id     = 'gp_nav';
            $submit_id  = 'gp_submit';

            foreach ($workspaces as $ws => $workspace) {
                foreach ($workspace->dumpGlobalPrefs() as $k => $v) {
                    $prefs[$ws][$k] = $v;
                }
            }
        } else {
            $prefix     = 'l_';
            $prefix_id  = '#' . $prefix;
            $field_name = 's';
            $nav_id     = 'lp_nav';
            $submit_id  = 'lp_submit';

            foreach ($workspaces as $ws => $workspace) {
                foreach ($workspace->dumpPrefs() as $k => $v) {
                    $prefs[$ws][$k] = $v;
                }
            }
        }

        ksort($prefs, SORT_FLAG_CASE | SORT_STRING);
        if (count($prefs)) {
            $ws_combo = [];
            foreach ($prefs as $ws => $s) {
                $ws_combo[$ws] = $prefix_id . $ws;
            }
            echo
            '<form action="' . dcCore::app()->admin->url->get('admin.plugin') . '" method="post" class="anchor-nav-sticky">' .
            '<p class="anchor-nav">' .
            '<label for="' . $nav_id . '" class="classic">' . __('Goto:') . '</label> ' .
            form::combo($nav_id, $ws_combo, ['class' => 'navigation']) .
            ' <input type="submit" value="' . __('Ok') . '" id="' . $submit_id . '" />' .
            '<input type="hidden" name="p" value="' . My::id() . '" />' .
            dcCore::app()->formNonce() .
            '</p></form>';
        }

        echo
        '<form action="' . dcCore::app()->admin->url->get('admin.plugin') . '" method="post">';
        foreach ($prefs as $ws => $s) {
            ksort($s);
            echo sprintf($table_header, $prefix . $ws, $ws);
            foreach ($s as $k => $v) {
                $strong = $global ? false : !$v['global'];
                echo self::prefLine($k, $v, $ws, $field_name, $strong);
            }
            echo $table_footer;
        }

        echo
        '<p><input type="submit" value="' . __('Save') . '" />' .
        '<input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '<input type="hidden" name="p" value="' . My::id() . '" />' .
        dcCore::app()->formNonce() .
        '</p></form>';
    }

    /**
     * Return table line (td) to display a setting
     *
     * @param      string  $id            The identifier
     * @param      array   $s             The setting
     * @param      string  $ws            The workspace
     * @param      string  $field_name    The field name
     * @param      bool    $strong_label  The strong label
     *
     * @return     string
     */
    protected static function prefLine(string $id, array $s, string $ws, string $field_name, bool $strong_label): string
    {
        $field = match ($s['type']) {
            dcWorkspace::WS_BOOLEAN, dcWorkspace::WS_BOOL => form::combo(
                [$field_name . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id],
                [__('yes') => 1, __('no') => 0],
                $s['value'] ? 1 : 0
            ),

            dcWorkspace::WS_ARRAY => form::field(
                [$field_name . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id],
                40,
                null,
                Html::escapeHTML(json_encode($s['value'], JSON_THROW_ON_ERROR))
            ),

            dcWorkspace::WS_INTEGER, dcWorkspace::WS_INT, dcWorkspace::WS_FLOAT, 'integer', 'float' => form::number(
                [$field_name . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id],
                null,
                null,
                Html::escapeHTML((string) $s['value'])
            ),

            //dcWorkspace::WS_STRING, dcWorkspace::WS_TEXT,
            default => form::field(
                [$field_name . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id],
                40,
                null,
                Html::escapeHTML($s['value'])
            ),
        };

        $type = form::hidden(
            [$field_name . '_type' . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id . '_type'],
            Html::escapeHTML($s['type'])
        );

        $slabel = $strong_label ? '<strong>%s</strong>' : '%s';

        return
            '<tr class="line">' .
            '<td scope="row"><label for="' . $field_name . '_' . $ws . '_' . $id . '">' . sprintf($slabel, Html::escapeHTML($id)) . '</label></td>' .
            '<td>' . $field . '</td>' .
            '<td>' . $s['type'] . $type . '</td>' .
            '<td>' . Html::escapeHTML($s['label']) . '</td>' .
            '</tr>';
    }
}
