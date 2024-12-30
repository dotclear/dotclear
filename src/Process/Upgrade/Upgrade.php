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
use Dotclear\Core\Upgrade\Update;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Hidden,
    Link,
    Note,
    Para,
    Submit,
    Text
};
use Exception;

/**
 * @brief   Core upgrade process page.
 *
 * @since   2.29
 */
class Upgrade extends Process
{
    private static Update $updater;
    private static bool|string $new_ver = false;
    private static string $zip_file     = '';
    private static string $version_info = '';
    private static bool $update_warning = false;
    private static string $step         = '';

    public static function init(): bool
    {
        Page::checkSuper();

        // Check backup path existence
        if (!is_dir(App::config()->backupRoot())) {
            Page::open(
                __('Dotclear update'),
                '',
                Page::breadcrumb(
                    [
                        __('Dotclear update') => '',
                        __('Update')          => '',
                    ]
                )
            );

            echo (new Para())
                ->items([
                    (new Text('h3', __('Precheck update error'))),
                    (new Text('p', __('It seems that backup directory does not exist, upgrade can not be performed.'))),
                ])
                ->render();

            Page::helpBlock('core_upgrade');
            Page::close();
            exit;
        }

        if (!is_readable(App::config()->digestsRoot())) {
            Page::open(
                __('Dotclear update'),
                '',
                Page::breadcrumb(
                    [
                        __('Dotclear update') => '',
                        __('Update')          => '',
                    ]
                )
            );

            echo (new Para())
                ->items([
                    (new Text('h3', __('Precheck update error'))),
                    (new Text('p', __('It seems that there are no "digests" file on your system, upgrade can not be performed.'))),
                ])
                ->render();

            Page::helpBlock('core_upgrade');
            Page::close();
            exit;
        }

        self::$updater = new Update(App::config()->coreUpdateUrl(), 'dotclear', App::config()->coreUpdateCanal(), App::config()->cacheRoot() . DIRECTORY_SEPARATOR . Update::CACHE_FOLDER);
        self::$new_ver = self::$updater->check(App::config()->dotclearVersion(), !empty($_GET['nocache']));

        if (self::$new_ver) {
            self::$zip_file       = App::config()->backupRoot() . '/' . basename((string) self::$updater->getFileURL());
            self::$version_info   = (string) self::$updater->getInfoURL();
            self::$update_warning = self::$updater->getWarning();
        }

        # Hide "update me" message
        if (!empty($_GET['hide_msg'])) {
            self::$updater->setNotify(false);
            App::upgrade()->url()->redirect('upgrade.home');
        }

        self::$step = $_GET['step'] ?? '';
        self::$step = in_array(self::$step, ['check', 'download', 'backup', 'unzip']) ? self::$step : '';

        return self::status(true);
    }

    public static function process(): bool
    {
        # Upgrade process
        if (self::$new_ver && self::$step) {
            try {
                self::$updater->setForcedFiles('inc/digests');

                switch (self::$step) {
                    case 'check':
                        self::$updater->checkIntegrity(App::config()->dotclearRoot() . '/inc/digests', App::config()->dotclearRoot());
                        App::upgrade()->url()->redirect('upgrade.upgrade', ['step' => 'download']);

                        break;
                    case 'download':
                        self::$updater->download(self::$zip_file);
                        if (!self::$updater->checkDownload(self::$zip_file)) {
                            throw new Exception(
                                sprintf(
                                    __('Downloaded Dotclear archive seems to be corrupted. Try <a %s>download it</a> again.'),
                                    'href="' . App::upgrade()->url()->get('upgrade.upgrade', ['step' => 'download']) . '"'
                                ) .
                                ' ' .
                                __('If this problem persists try to ' .
                                    '<a href="https://dotclear.org/download">update manually</a>.')
                            );
                        }
                        App::upgrade()->url()->redirect('upgrade.upgrade', ['step' => 'backup']);

                        break;
                    case 'backup':
                        self::$updater->backup(
                            self::$zip_file,
                            'dotclear/inc/digests',
                            App::config()->dotclearRoot(),
                            App::config()->dotclearRoot() . '/inc/digests',
                            App::config()->backupRoot() . '/backup-' . App::config()->dotclearVersion() . '.zip'
                        );
                        App::upgrade()->url()->redirect('upgrade.upgrade', ['step' => 'unzip']);

                        break;
                    case 'unzip':
                        self::$updater->performUpgrade(
                            self::$zip_file,
                            'dotclear/inc/digests',
                            'dotclear',
                            App::config()->dotclearRoot(),
                            App::config()->dotclearRoot() . '/inc/digests'
                        );

                        // Disable REST service until next authentication
                        App::rest()->enableRestServer(false);

                        break;
                }
            } catch (Exception $e) {
                $msg = $e->getMessage();

                if ($e->getCode() == Update::ERR_FILES_CHANGED) {
                    $msg = __('The following files of your Dotclear installation have been modified so we won\'t try to update your installation. Please try to <a href="https://dotclear.org/download">update manually</a>.');
                } elseif ($e->getCode() == Update::ERR_FILES_UNREADABLE) {
                    $msg = sprintf(
                        __('The following files of your Dotclear installation are not readable. Please fix this or try to make a backup file named %s manually.'),
                        '<strong>backup-' . App::config()->dotclearVersion() . '.zip</strong>'
                    );
                } elseif ($e->getCode() == Update::ERR_FILES_UNWRITALBE) {
                    $msg = __('The following files of your Dotclear installation cannot be written. Please fix this or try to <a href="https://dotclear.org/download">update manually</a>.');
                }

                if (($bad_files = self::$updater->getBadFiles()) !== []) {
                    $msg .= '<ul><li><strong>' . implode('</strong></li><li><strong>', $bad_files) . '</strong></li></ul>';
                }

                App::error()->add($msg);
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (self::$step === 'unzip' && !App::error()->flag()) {
            // Update done, need to go back to authentication (see below), but we need
            // to kill the admin session before sending any header
            App::upgrade()->killAdminSession();
        }

        $items = [];

        if (self::$step === '') {
            // Warning about PHP version if necessary
            if (version_compare(phpversion(), App::config()->nextRequiredPhp(), '<')) {
                $items[] = (new Text('p', sprintf(
                    __('The next versions of Dotclear will not support PHP version < %s, your\'s is currently %s'),
                    App::config()->nextRequiredPhp(),
                    phpversion()
                )))
                    ->class('info more-info');
            }
            if (self::$new_ver === false || self::$new_ver === '') {
                $items[] = (new Para())
                    ->items([
                        (new Text('strong', __('No newer Dotclear version available.'))),
                    ]);

                if (App::error()->flag() || empty($_GET['nocache'])) {
                    $items[] = (new Form('updcache'))
                        ->method('get')
                        ->action(App::upgrade()->url()->get('upgrade.upgrade'))
                        ->fields([
                            (new Para())
                                ->items([
                                    (new Hidden(['process'], 'Upgrade')),
                                    (new Hidden(['nocache'], '1')),
                                    (new Submit(['submit'], __('Force checking update Dotclear'))),
                                ]),
                        ]);
                }
            } else {
                $items[] = (new Para())
                    ->class('static-msg dc-update updt-info')
                    ->separator(' ')
                    ->items([
                        (new Text('', sprintf(__('Dotclear %s is available.'), self::$new_ver))),
                        self::$version_info !== '' ?
                            (new Link())
                                ->href(self::$version_info)
                                ->title(__('Information about this version'))
                                ->text(__('Information about this version'))
                            : (new Text()),
                    ]);

                if (version_compare(phpversion(), (string) self::$updater->getPHPVersion()) < 0) {
                    $items[] = (new Text('p', sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), self::$updater->getPHPVersion())))
                        ->class('warning-msg');
                } else {
                    $items[] = (new Form('updcheck'))
                        ->class('fieldset')
                        ->method('get')
                        ->action(App::upgrade()->url()->get('upgrade.upgrade'))
                        ->fields([
                            (new Text('p', __('To upgrade your Dotclear installation simply click on the following button. A backup file of your current installation will be created in your root directory.'))),
                            (new Para())
                                ->items([
                                    (new Hidden(['step'], 'check')),
                                    (new Hidden(['process'], 'Upgrade')),
                                    (new Submit(['submit'], __('Update Dotclear'))),
                                ]),
                            self::$update_warning ?
                                (new Text('p', __('This update may potentially require some precautions, you should carefully read the information post associated with this release (see above).')))
                                    ->class('warning') : (new Text()),
                        ]);
                }
            }
        } elseif (self::$step === 'unzip' && !App::error()->flag()) {
            $items[] = (new Div())
                ->class('fieldset')
                ->items([
                    (new Text('p', __("Congratulations, you're one click away from the end of the update."))),
                    (new Para())
                        ->items([
                            (new Link())
                                ->class('button submit')
                                ->href(App::upgrade()->url()->get('upgrade.auth'))
                                ->text(__('Finish the update.')),
                        ]),
                ]);
        }

        Page::open(
            __('Dotclear update'),
            self::$step === '' ? Page::jsLoad('js/_update.js') : '',
            Page::breadcrumb(
                [
                    __('Dotclear update') => '',
                    __('Update')          => '',
                ]
            )
        );

        if (!empty($items)) {
            echo (new Div())
                ->items([
                    (new Note())
                        ->class('static-msg')
                        ->text(__('On this page you can update dotclear to the latest release.')),
                    ...$items,
                ])
                ->render();
        }

        Page::helpBlock('core_upgrade');
        Page::close();
    }
}
