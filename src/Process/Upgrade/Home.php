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
 * @brief   Upgrade process home page.
 *
 * @since   2.29
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

        // New version
        if (!empty(self::$new_ver)) {
            echo
            '<p class="static-msg dc-update updt-info">' .
            sprintf(__('Dotclear %s is available.'), self::$new_ver);

            if (self::$updater->getInfoURL()) {
                echo
                ' <a href="' . self::$updater->getInfoURL() . '" title="' . __('Information about this version') . '">' . __('Information about this version') . '</a>';
            }

            echo
            '</p>';

            if (version_compare(phpversion(), (string) self::$updater->getPHPVersion()) < 0) {
                echo
                '<p class="warning-msg">' . sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), self::$updater->getPHPVersion()) . '</p>';
            } else {
                if (self::$updater->getWarning()) {
                    echo
                    '<p class="warning-msg">' . __('This update may potentially require some precautions, you should carefully read the information post associated with this release.') . '</p>';
                }
                echo
                '<p>' . sprintf(__('After reading help bellow, you can perform update from page "%s".'), '<a href="' . App::upgrade()->url()->get('upgrade.upgrade') . '">' . __('Update') . '</a>') . '</p>';
            }
        }

        echo
        '<div id="dashboard-main"><div id="dashboard-boxes"><div class="db-items" id="db-items">';

        // System
        $infos   = [];
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

        if (is_dir(App::config()->dotclearRoot() . DIRECTORY_SEPARATOR . '.git')) {
            $infos[] = __('Your are using developement release, some features are not available.');
        } elseif (!is_readable(App::config()->digestsRoot())) {
            $infos[] = sprintf(__('Dotclear digests file "%s" is not readable.'), App::config()->digestsRoot());
        }

        if (null == App::blog()->settings()->system->store_plugin_url) {
            $infos[] = __('Official plugins repository could not be updated as there is no URL set in configuration.');
        }

        echo
        '<div class="box small">' .
        '<h3>' . __('System') . '</h3>' .
        '<ul><li>' . implode("</li>\n<li>", $infos) . '</li></ul>' .
        '</div>';

        // Help
        $helps = [
            __("Do you read the official post about this release on Dotclear's blog?"),
            __('Does your system support next release requirements like PHP version?'),
            __('Does your plugins and themes are up to date?'),
            __('Note if some plugins crash your installation, go back here to disable or to remove or to update them.'),
        ];

        echo
        '<div class="box medium">' .
        '<h3>' . __('Help') . '</h3>' .
        '<p>' . __('Before performing update you should take into account some inforamtions') . '</p>' .
            '<ul><li>' . implode("</li>\n<li>", $helps) . '</li></ul>' .
        '</div>';

        echo
        '</div></div><div>';

        Page::close();
    }
}
