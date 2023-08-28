<?php
/**
 * @since 2.27 Before as admin/plugin.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcModules;
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Network\Http;
use Exception;

class Plugin extends Process
{
    public static function init(): bool
    {
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        return self::status(true);
    }

    public static function render(): void
    {
        $p_file = '';
        $plugin = !empty($_REQUEST['p']) ? $_REQUEST['p'] : '';
        $popup  = (int) !empty($_REQUEST['popup']);

        if ($popup) {
            $open_function  = [Page::class, 'openPopup'];
            $close_function = [Page::class, 'closePopup'];
        } else {
            $open_function  = [Page::class, 'open'];
            $close_function = [Page::class, 'close'];
        }

        $res = '';
        if (!empty($plugin)) {
            try {
                Core::backend()->setPageURL(Core::backend()->url->get('admin.plugin.' . $plugin));
            } catch (Exception $e) {
                // Unknown URL handler for plugin, back to dashboard
                Http::redirect(DC_ADMIN_URL);
            }

            // by class name
            $class = Core::plugins()->loadNsClass($plugin, dcModules::MODULE_CLASS_MANAGE);
            if (!empty($class)) {
                ob_start();
                $class::render();
                $res = (string) ob_get_contents();
                ob_end_clean();
                // by file name
            } elseif (Core::plugins()->moduleExists($plugin)) {
                $p_file = Core::plugins()->moduleInfo($plugin, 'root') . DIRECTORY_SEPARATOR . dcModules::MODULE_FILE_MANAGE;
                if (file_exists($p_file)) {
                    ob_start();
                    include $p_file;
                    $res = (string) ob_get_contents();
                    ob_end_clean();
                }
            }
        }

        if (!empty($res)) {
            $p_title   = 'no content - plugin';
            $p_head    = '';
            $p_content = '<p>' . __('No content found on this plugin.') . '</p>';

            if (preg_match('|<head>(.*?)</head|ms', $res, $m)) {
                // <head> present

                if (preg_match('|<title>(.*?)</title>|ms', $m[1], $mt)) {
                    // Extract plugin title
                    $p_title = $mt[1];
                }

                if (preg_match_all('|(<script.*?>.*?</script>)|ms', $m[1], $ms)) {
                    // Extract plugin scripts
                    foreach ($ms[1] as $v) {
                        $p_head .= $v . "\n";
                    }
                }

                if (preg_match_all('|(<style.*?>.*?</style>)|ms', $m[1], $ms)) {
                    // Extract plugin styles
                    foreach ($ms[1] as $v) {
                        $p_head .= $v . "\n";
                    }
                }

                if (preg_match_all('|(<link.*?/>)|ms', $m[1], $ms)) {
                    // Extract plugin links
                    foreach ($ms[1] as $v) {
                        $p_head .= $v . "\n";
                    }
                }
            }

            if (preg_match('|<body.*?>(.+)</body>|ms', $res, $m)) {
                // Extract plugin body
                $p_content = $m[1];
            }

            $open_function($p_title, $p_head);
            echo $p_content;
            if (!$popup) {
                // Add direct links to plugin settings if any
                $settings = ModulesList::getSettingsUrls((string) $plugin, true, false);
                if (!empty($settings)) {
                    echo '<hr class="clear"/><p class="right modules">' . implode(' - ', $settings) . '</p>';
                }
            }
            $close_function();
        } else {
            // Plugin not found
            $open_function(
                __('Plugin not found'),
                '',
                Page::breadcrumb(
                    [
                        __('System')           => '',
                        __('Plugin not found') => '',
                    ]
                )
            );
            echo '<p>' . __('The plugin you reached does not exist or does not have an admin page.') . '</p>';
            $close_function();
        }
    }
}
