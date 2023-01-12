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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class adminAboutConfig
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcCore::app()->admin->part = !empty($_GET['part']) && $_GET['part'] === 'global' ? 'global' : 'local';
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        // Local navigation
        if (!empty($_POST['gs_nav'])) {
            http::redirect(dcCore::app()->admin->getPageURL() . $_POST['gs_nav']);
            exit;
        }
        if (!empty($_POST['ls_nav'])) {
            http::redirect(dcCore::app()->admin->getPageURL() . $_POST['ls_nav']);
            exit;
        }

        // Local settings update
        if (!empty($_POST['s']) && is_array($_POST['s'])) {
            try {
                foreach ($_POST['s'] as $ns => $s) {
                    foreach ($s as $k => $v) {
                        if ($_POST['s_type'][$ns][$k] == 'array') {
                            $v = json_decode($v, true, 512, JSON_THROW_ON_ERROR);
                        }
                        dcCore::app()->blog->settings->$ns->put($k, $v);
                    }
                    dcCore::app()->blog->triggerBlog();
                }

                dcPage::addSuccessNotice(__('Configuration successfully updated'));
                http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Global settings update
        if (!empty($_POST['gs']) && is_array($_POST['gs'])) {
            try {
                foreach ($_POST['gs'] as $ns => $s) {
                    foreach ($s as $k => $v) {
                        if ($_POST['gs_type'][$ns][$k] == 'array') {
                            $v = json_decode($v, true, 512, JSON_THROW_ON_ERROR);
                        }
                        dcCore::app()->blog->settings->$ns->put($k, $v, null, null, true, true);
                    }
                    dcCore::app()->blog->triggerBlog();
                }

                dcPage::addSuccessNotice(__('Configuration successfully updated'));
                http::redirect(dcCore::app()->admin->getPageURL() . '&part=global');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        echo
        '<html>' .
        '<head>' .
        '<title>about:config</title>' .
        dcPage::jsPageTabs(dcCore::app()->admin->part) .
        dcPage::jsModuleLoad('aboutConfig/js/index.js') .
        '</head>' .
        '<body>' .
        dcPage::breadcrumb(
            [
                __('System')                                => '',
                html::escapeHTML(dcCore::app()->blog->name) => '',
                __('about:config')                          => '',
            ]
        ) .
        dcPage::notices() .
        '<div id="local" class="multi-part" title="' . sprintf(__('Settings for %s'), html::escapeHTML(dcCore::app()->blog->name)) . '">' .
        '<h3 class="out-of-screen-if-js">' . sprintf(__('Settings for %s'), html::escapeHTML(dcCore::app()->blog->name)) . '</h3>';

        self::settingsTable(false);

        echo
        '</div>' .

        '<div id="global" class="multi-part" title="' . __('Global settings') . '">' .
        '<h3 class="out-of-screen-if-js">' . __('Global settings') . '</h3>';

        self::settingsTable(true);

        echo
        '</div>';

        dcPage::helpBlock('aboutConfig');

        echo
        '</body>' .
        '</html>';
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

        $settings = [];
        if ($global) {
            $prefix     = 'g_';
            $prefix_id  = '#' . $prefix;
            $field_name = 'gs';
            $nav_id     = 'gs_nav';
            $submit_id  = 'gs_submit';

            foreach (dcCore::app()->blog->settings->dumpNamespaces() as $ns => $namespace) {
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

            foreach (dcCore::app()->blog->settings->dumpNamespaces() as $ns => $namespace) {
                foreach ($namespace->dumpSettings() as $k => $v) {
                    $settings[$ns][$k] = $v;
                }
            }
        }

        ksort($settings);
        if (count($settings)) {
            $ns_combo = [];
            foreach ($settings as $ns => $s) {
                $ns_combo[$ns] = $prefix_id . $ns;
            }
            echo
            '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" class="anchor-nav-sticky">' .
            '<p class="anchor-nav">' .
            '<label for="' . $nav_id . '" class="classic">' . __('Goto:') . '</label> ' .
            form::combo($nav_id, $ns_combo, ['class' => 'navigation']) .
            ' <input type="submit" value="' . __('Ok') . '" id="' . $submit_id . '" />' .
            '<input type="hidden" name="p" value="aboutConfig" />' .
            dcCore::app()->formNonce() .
            '</p></form>';
        }

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post">';
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
        '<input type="hidden" name="p" value="aboutConfig" />' .
        dcCore::app()->formNonce() .
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
        switch ($s['type']) {
            case 'boolean':
                $field = form::combo(
                    [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                    [__('yes') => 1, __('no') => 0],
                    $s['value'] ? 1 : 0
                );

                break;

            case 'array':
                $field = form::field(
                    [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                    40,
                    null,
                    html::escapeHTML(json_encode($s['value'], JSON_THROW_ON_ERROR))
                );

                break;

            case 'integer':
                $field = form::number(
                    [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                    null,
                    null,
                    html::escapeHTML($s['value'])
                );

                break;

            default:
                $field = form::field(
                    [$field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id],
                    40,
                    null,
                    html::escapeHTML($s['value'])
                );

                break;
        }

        $type = form::hidden(
            [$field_name . '_type' . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id . '_type'],
            html::escapeHTML($s['type'])
        );

        $slabel = $strong_label ? '<strong>%s</strong>' : '%s';

        return
            '<tr class="line">' .
            '<td scope="row"><label for="' . $field_name . '_' . $ns . '_' . $id . '">' . sprintf($slabel, html::escapeHTML($id)) . '</label></td>' .
            '<td>' . $field . '</td>' .
            '<td>' . $s['type'] . $type . '</td>' .
            '<td>' . html::escapeHTML($s['label']) . '</td>' .
            '</tr>';
    }
}

adminAboutConfig::init();
adminAboutConfig::process();
adminAboutConfig::render();
