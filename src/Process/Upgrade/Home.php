<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
    Note,
    Para,
    Span,
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
        $infos[] = (new li())->text(sprintf(__('The installed PHP version is %s, the next version of Dotclear requires %s or higher.'), phpversion(), App::config()->nextRequiredPhp()));

        if (App::config()->adminUrl() === '') {
            $infos[] = (new li())->text(sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') . ' ' .
                sprintf(
                    __('See <a href="%s">documentation</a> for more information.'),
                    'https://dotclear.org/documentation/2.0/admin/config'
                ));
        }

        if (App::config()->adminMailfrom() === 'dotclear@local') {
            $infos[] = (new li())->text(sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') . ' ' .
                sprintf(
                    __('See <a href="%s">documentation</a> for more information.'),
                    'https://dotclear.org/documentation/2.0/admin/config'
                ));
        }

        if (!is_dir(App::config()->cacheRoot()) || !is_writable(App::config()->cacheRoot())) {
            $infos[] = (new li())->text(__('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.'));
        }

        if (!is_dir(App::config()->backupRoot()) || !is_writable(App::config()->backupRoot())) {
            $infos[] = (new li())->text(sprintf(__('Backup directory "%s" does not exist or is not writable.'), App::config()->backupRoot()));
        }

        if (App::con()->driver() === 'sqlite') {
            $infos[] = (new li())->text(__('Your are using Sqlite database driver, Database structure upgrade will NOT be performed.'));
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
            foreach (App::upgrade()->getIcons() as $icon) {
                if (!$icon->perm) {
                    continue;
                }
                $icons[] = (new Para())
                    ->items([
                        (new Link('icon-process-' . $icon->id . '-fav'))
                            ->href(App::upgrade()->url()->get($icon->url))
                            ->items([
                                (new Img($icon->icon))
                                    ->alt($icon->id)
                                    ->class('light-only'),
                                (new Img($icon->dark))
                                    ->alt($icon->id)
                                    ->class('dark-only'),
                                (new Span($icon->name))
                                    ->class('db-icon-title'),
                                (new Span($icon->descr))
                                    ->class('db-icon-descr'),
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

        echo (new Div('dashboard-main'))
            ->items([
                $icons,
                (new Div('dashboard-boxes'))
                    ->items([
                        (new Div('db-items'))
                            ->class('db-items')
                            ->items([
                                (new Div())
                                    ->class('box medium')
                                    ->items([
                                        (new Text('h3', __('Warning'))),
                                        (new Note())
                                            ->text(__('Before performing update you should take into account some informations listed bellow:')),
                                        (new Ul())
                                            ->items($helps),
                                        (new Text('h3', __('System'))),
                                        (new Ul())
                                            ->items($infos),
                                    ]),
                            ]),
                    ]),
            ])
            ->render();

        Page::close();
    }
}
