<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Div,
    Img,
    Li,
    Link,
    Para,
    Text,
    Ul
};

/**
 * @brief   Upgrade process home page.
 *
 * @since   2.29
 */
class Home extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        return self::status();
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // System
        $infos   = [];
        $infos[] = (new li())->text(sprintf(__('Installed Dotclear version is %s'), App::config()->dotclearVersion()));
        $infos[] = (new li())->text(sprintf(__('Installed PHP version is %s, next Dotclear release required %s or earlier.'), phpversion(), App::config()->nextRequiredPhp()));

        if (App::config()->adminUrl() == '') {
            $infos[] = (new li())->text(sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'));
        }

        if (App::config()->adminMailfrom() == 'dotclear@local') {
            $infos[] = (new li())->text(sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'));
        }

        if (!is_dir(App::config()->cacheRoot()) || !is_writable(App::config()->cacheRoot())) {
            $infos[] = (new li())->text(__('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.'));
        }

        if (!is_dir(App::config()->backupRoot()) || !is_writable(App::config()->backupRoot())) {
            $infos[] = (new li())->text(sprintf(__('Backup directory "%s" does not exist or is not writable.'), App::config()->backupRoot()));
        }

        if (is_dir(App::config()->dotclearRoot() . DIRECTORY_SEPARATOR . '.git')) {
            $infos[] = (new li())->text(__('Your are using developement release, some features are not available.'));
        }
        if (!is_readable(App::config()->digestsRoot())) {
            $infos[] = (new li())->text(sprintf(__('Dotclear digests file "%s" is not readable.'), App::config()->digestsRoot()));
        }

        if (null == App::blog()->settings()->system->store_plugin_url) {
            $infos[] = (new li())->text(__('Official plugins repository could not be updated as there is no URL set in configuration.'));
        }

        // Help
        $helps = [
            (new li())->text(__("Do you read the official post about this release on Dotclear's blog?")),
            (new li())->text(__('Does your system support this release requirements like PHP version?')),
            (new li())->text(__('Does your plugins and themes are up to date?')),
            (new li())->text(__('Note if some plugins crash your installation, go back here to disable or to remove or to update them.')),
            (new li())->text(__('Once update done, do not forget to check and update blogs themes.')),
        ];

        // Icons
        $icons = (new Div());
        if (!App::auth()->prefs()->dashboard->nofavicons) {
            $icons = [];
            foreach (self::dashboardIcons() as $icon) {
                if (!$icon[4]) {
                    continue;
                }
                $icons[] = (new Para())
                    ->items([
                        (new Link('icon-process-' . $icon[5] . '-fav'))
                            ->href(App::upgrade()->url()->get((string) $icon[1]))
                            ->items([
                                (new Img((string) $icon[2]))
                                    ->class('light-only'),
                                (new Img((string) $icon[3]))
                                    ->class('dark-only'),
                                (new Text('', '<br/>')),
                                (new Text('span', (string) $icon[0]))
                                    ->class('db-icon-title'),
                            ]),
                    ]);
            }
            $icons = (new Div('dashboard-icons'))
                ->items([
                    (new Div('icons'))
                        ->items($icons),
                ]);
        }

        Page::open(
            __('Dashboard'),
            Page::jsLoad('js/_upgrade.js') .
            Page::jsAdsBlockCheck(),
            Page::breadcrumb(
                [
                    __('Dotclear update') => '',
                ],
                ['home_link' => false]
            )
        );

        echo (new Text('p', __("You are on a dashboard dedicated to Dotclear's update.")))
            ->class('message')
            ->render();

        echo (new Div('dashboard-main'))
            ->items([
                $icons,
                (new Div('dashboard-boxes'))
                    ->items([
                        (new Div('db-items'))
                            ->class('db-items')
                            ->items([
                                (new Div())
                                    ->class('box small')
                                    ->items([
                                        (new Text('h3', __('System'))),
                                        (new Ul())
                                            ->items($infos),
                                    ]),
                                (new Div())
                                    ->class('box medium')
                                    ->items([
                                        (new Text('h3', __('Warning'))),
                                        (new Text('p', __('Before performing update you should take into account some informations listed bellow:'))),
                                        (new Ul())
                                            ->items($helps),
                                    ]),
                            ]),
                    ]),
            ])
            ->render();

        Page::close();
    }

    /**
     * @return  array<int, array<int, string|bool>>
     */
    private static function dashboardIcons()
    {
        return [
            [
                __('Update'),
                'upgrade.upgrade',
                'images/menu/update.svg',
                'images/menu/update-dark.svg',
                App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
                'Upgrade',
            ], [
                __('Attic'),
                'upgrade.attic',
                'images/menu/blog-pref.svg',
                'images/menu/blog-pref-dark.svg',
                App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
                'Attic',
            ], [
                __('Backups'),
                'upgrade.backup',
                'images/menu/backup.svg',
                'images/menu/backup-dark.svg',
                App::auth()->isSuperAdmin(),
                'Backup',
            ], [
                __('Languages'),
                'upgrade.langs',
                'images/menu/langs.svg',
                'images/menu/langs-dark.svg',
                App::auth()->isSuperAdmin(),
                'Langs',
            ], [
                __('Plugins'),
                'upgrade.plugins',
                'images/menu/plugins.svg',
                'images/menu/plugins-dark.svg',
                App::auth()->isSuperAdmin(),
                'Plugins',
            ], [
                __('Cache'),
                'upgrade.cache',
                'images/menu/tools.svg',
                'images/menu/tools-dark.svg',
                App::auth()->isSuperAdmin(),
                'Cache',
            ], [
                __('Digests'),
                'upgrade.digests',
                'images/menu/edit.svg',
                'images/menu/edit-dark.svg',
                App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
                'Digests',
            ],
        ];
    }
}
