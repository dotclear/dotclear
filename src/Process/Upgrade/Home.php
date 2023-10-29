<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Upgrade\Update;
use Dotclear\Core\Process;
use Exception;

/**
 * @since 2.27 Before as admin/index.php
 */
class Home extends Process
{
    private static Update $updater;
    private static bool|string $new_ver = false;

    public static function init(): bool
    {
        Page::checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        try {
            self::$updater = new Update(App::config()->coreUpdateUrl(), 'dotclear', App::config()->coreUpdateCanal(), App::config()->cacheRoot() . '/versions');
            self::$new_ver = self::$updater->check(App::config()->dotclearVersion(), false);
        } catch(Exception) {
        }

        return self::status();
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::open(
            __('Dashboard'),
            Page::jsLoad('js/_index.js') .
            Page::jsAdsBlockCheck(),
            Page::breadcrumb(
                [
                    __('Dotclear update') => '',
                ],
                ['home_link' => false]
            )
        );

        $updt = $infos = $helps = [];

        if (!empty(self::$new_ver)) {
            if (self::$updater->getInfoURL()) {
                $updt[] = '<a href="' . self::$updater->getInfoURL() . '" title="' . __('Information about this version') . '">' . __('Information about this version') . '</a>';
            }
            if (version_compare(phpversion(), (string) self::$updater->getPHPVersion()) < 0) {
                $updt[] = sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), self::$updater->getPHPVersion());
            }
            if (self::$updater->getWarning()) {
                $updt[] = __('This update may potentially require some precautions, you should carefully read the information post associated with this release.');
            }
            $updt[] = sprintf(__('After reading help bellow, you can perform update from sidebar menu item "%s".'), __('Update'));
        }

        $infos[] = sprintf(__('Installed Dotclear version is %s'), App::config()->dotclearVersion());
        $infos[] = sprintf(__('Installed PHP version is %s, next Dotclear release required %s or earlier.'), phpversion(), App::config()->nextRequiredPhp());

        if (App::config()->adminUrl() == '') {
            $infos[] = sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.');
        }

        if (App::config()->adminMailfrom() == 'dotclear@local') {
            $infos[] = sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.');
        }

        if (!is_dir(App::config()->cacheRoot()) || !is_writable(App::config()->cacheRoot())) {
            $infos[] = __('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.');
        }

        if (!is_dir(App::config()->backupRoot()) || !is_writable(App::config()->backupRoot())) {
            $infos[] = sprintf(__('Backup directory "%s" does not exist or is not writable.'), App::config()->backupRoot());
        }

        if (str_contains(App::config()->dotclearversion(), '-dev')) {
            $infos[] = __('Your are using developement release, some features are not available.');
        } elseif (!is_readable(App::config()->digestsRoot())) {
            $infos[] = sprintf(__('Dotclear digests file "%s" is not readable.'), App::config()->digestsRoot());
        }

        if (null == App::blog()->settings()->system->store_plugin_url) {
            $infos[] = __('Official plugins repository could not be updated as there is no URL set in configuration.');
        }

        $helps[] = __('Check your system support next relase requirements like PHP version.');
        $helps[] = __('Check your plugins and themes are up to date.');
        $helps[] = __('Note if some plugins crash your installation, go back here to disable or remove or update them.');

        echo
        '<div id="dashboard-main"><div id="dashboard-boxes"><div class="db-contents" id="db-contents">';

        // Update
        if (count($updt)) {
            echo
            '<div class="box large">' .
            '<h3>' . sprintf(__('Dotclear %s is available.'), self::$new_ver) . '</h3>' .
            '<ul><li>' . implode("</li>\n<li>", $updt) . '</li></ul>' .
            '</div>';
        }

        // System
        echo
        '<div class="box large">' .
        '<h3>' . __('System') . '</h3>' .
        '<ul><li>' . implode("</li>\n<li>", $infos) . '</li></ul>' .
        '</div>';

        // Help
        echo
        '<div class="box large">' .
        '<h3>' . __('Help') . '</h3>' .
        '<p>' . __('Before performing update you should take into account some inforamtions') . '</p>' .
            '<ul><li>' . implode("</li>\n<li>", $helps) . '</li></ul>' .
        '</div>';

        echo '</div></div><div>';

        Page::close();
    }
}
