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
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   Core upgrade process page.
 *
 * @since   2.29
 */
class Upgrade
{
    use TraitProcess;

    private static bool|string $new_ver = false;
    private static string $zip_file     = '';
    private static string $version_info = '';
    private static bool $update_warning = false;
    private static string $step         = '';

    public static function init(): bool
    {
        App::upgrade()->page()->checkSuper();

        // Check backup path existence
        if (!is_dir(App::config()->backupRoot())) {
            App::upgrade()->page()->open(
                __('Dotclear update'),
                '',
                App::upgrade()->page()->breadcrumb(
                    [
                        __('Dotclear update') => '',
                        __('Update')          => '',
                    ]
                )
            );

            echo (new Para())
                ->items([
                    (new Text('h3', __('Precheck update error'))),
                    (new Note())
                        ->text(__('It seems that backup directory does not exist, upgrade can not be performed.')),
                ])
                ->render();

            App::upgrade()->page()->helpBlock('core_upgrade');
            App::upgrade()->page()->close();
            dotclear_exit();
        }

        if (!is_readable(App::config()->digestsRoot())) {
            App::upgrade()->page()->open(
                __('Dotclear update'),
                '',
                App::upgrade()->page()->breadcrumb(
                    [
                        __('Dotclear update') => '',
                        __('Update')          => '',
                    ]
                )
            );

            echo (new Para())
                ->items([
                    (new Text('h3', __('Precheck update error'))),
                    (new Note())
                        ->text(__('It seems that there are no "digests" file on your system, upgrade can not be performed.')),
                ])
                ->render();

            App::upgrade()->page()->helpBlock('core_upgrade');
            App::upgrade()->page()->close();
            dotclear_exit();
        }

        self::$new_ver = App::upgrade()->update()->check(App::config()->dotclearVersion(), !empty($_GET['nocache']));

        if (self::$new_ver) {
            self::$zip_file       = App::config()->backupRoot() . '/' . basename((string) App::upgrade()->update()->getFileURL());
            self::$version_info   = (string) App::upgrade()->update()->getInfoURL();
            self::$update_warning = App::upgrade()->update()->getWarning();
        }

        # Hide "update me" message
        if (!empty($_GET['hide_msg'])) {
            App::upgrade()->update()->setNotify(false);
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
                App::upgrade()->update()->setForcedFiles('inc/digests');

                switch (self::$step) {
                    case 'check':
                        App::upgrade()->update()->checkIntegrity(App::config()->dotclearRoot() . '/inc/digests', App::config()->dotclearRoot());
                        App::upgrade()->url()->redirect('upgrade.upgrade', ['step' => 'download']);

                        break;
                    case 'download':
                        App::upgrade()->update()->download(self::$zip_file);
                        if (!App::upgrade()->update()->checkDownload(self::$zip_file)) {
                            throw new Exception(
                                sprintf(
                                    __('Downloaded Dotclear archive seems to be corrupted. Try <a %s>download it</a> again.'),
                                    'href="' . App::upgrade()->url()->get('upgrade.upgrade', ['step' => 'download']) . '"'
                                ) .
                                ' ' .
                                sprintf(
                                    __('If this problem persists try to <a href="%s">update manually</a>.'),
                                    'https://dotclear.org/download'
                                )
                            );
                        }
                        App::upgrade()->url()->redirect('upgrade.upgrade', ['step' => 'backup']);

                        break;
                    case 'backup':
                        App::upgrade()->update()->backup(
                            self::$zip_file,
                            'dotclear/inc/digests',
                            App::config()->dotclearRoot(),
                            App::config()->dotclearRoot() . '/inc/digests',
                            App::config()->backupRoot() . '/backup-' . App::config()->dotclearVersion() . '.zip'
                        );
                        App::upgrade()->url()->redirect('upgrade.upgrade', ['step' => 'unzip']);

                        break;
                    case 'unzip':
                        App::upgrade()->update()->performUpgrade(
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

                if ($e->getCode() == App::upgrade()->update()::ERR_FILES_CHANGED) {
                    $msg = sprintf(
                        __('The following files of your Dotclear installation have been modified so we won\'t try to update your installation. Please try to <a href="%s">update manually</a>.'),
                        'https://dotclear.org/download'
                    );
                } elseif ($e->getCode() == App::upgrade()->update()::ERR_FILES_UNREADABLE) {
                    $msg = sprintf(
                        __('The following files of your Dotclear installation are not readable. Please fix this or try to make a backup file named %s manually.'),
                        (new Strong('backup-' . App::config()->dotclearVersion() . '.zip'))->render()
                    );
                } elseif ($e->getCode() == App::upgrade()->update()::ERR_FILES_UNWRITALBE) {
                    $msg = sprintf(
                        __('The following files of your Dotclear installation cannot be written. Please fix this or try to <a href="%s">update manually</a>.'),
                        'https://dotclear.org/download'
                    );
                }

                if (($bad_files = App::upgrade()->update()->getBadFiles()) !== []) {
                    $msg .= (new Ul())
                        ->items(array_map(
                            fn (string $bad_file): Li => (new Li())
                                ->items([
                                    (new Strong($bad_file)),
                                ]),
                            $bad_files
                        ))
                    ->render();
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
            // to kill the admin session before
            App::upgrade()->killAdminSession();
            // Redirect to authentication
            App::upgrade()->url()->redirect('upgrade.auth');
        }

        $items = [];

        if (self::$step === '') {
            // Warning about PHP version if necessary
            if (version_compare(phpversion(), App::config()->nextRequiredPhp(), '<')) {
                $items[] = (new Note())
                    ->text(sprintf(
                        __('The next versions of Dotclear will not support PHP version < %1$s, your\'s is currently %2$s'),
                        App::config()->nextRequiredPhp(),
                        phpversion()
                    ))
                    ->class('info more-info');
            }
            if (self::$new_ver === false || self::$new_ver === '') {
                $items[] = (new Para())
                    ->items([
                        (new Strong(__('No newer Dotclear version available.'))),
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
                    ->class(['static-msg', 'dc-update', 'updt-info'])
                    ->separator(' ')
                    ->items([
                        (new Text(null, sprintf(__('Dotclear %s is available.'), self::$new_ver))),
                        self::$version_info !== '' ?
                            (new Link())
                                ->href(self::$version_info)
                                ->title(__('Information about this version'))
                                ->text(__('Information about this version'))
                            : (new Text()),
                    ]);

                if (version_compare(phpversion(), (string) App::upgrade()->update()->getPHPVersion()) < 0) {
                    $items[] = (new Note())
                        ->text(sprintf(__('PHP version is %1$s (%2$s or earlier needed).'), phpversion(), App::upgrade()->update()->getPHPVersion()))
                        ->class('warning-msg');
                } else {
                    $items[] = (new Form('updcheck'))
                        ->class('fieldset')
                        ->method('get')
                        ->action(App::upgrade()->url()->get('upgrade.upgrade'))
                        ->fields([
                            (new Note())
                                ->text(__('To upgrade your Dotclear installation simply click on the following button. A backup file of your current installation will be created in your root directory.')),
                            (new Para())
                                ->items([
                                    (new Hidden(['step'], 'check')),
                                    (new Hidden(['process'], 'Upgrade')),
                                    (new Submit(['submit'], __('Update Dotclear'))),
                                ]),
                            self::$update_warning ?
                                (new Note())
                                    ->text(__('This update may potentially require some precautions, you should carefully read the information post associated with this release (see above).'))
                                    ->class('warning') : (new Text()),
                        ]);
                }
            }
        } elseif (self::$step === 'unzip' && !App::error()->flag()) {
            // We normally should not pass through these block, but who knows?
            $items[] = (new Div())
                ->class('fieldset')
                ->items([
                    (new Note())
                        ->text(__("Congratulations, you're one click away from the end of the update.")),
                    (new Para())
                        ->items([
                            (new Link())
                                ->class('button submit')
                                ->href(App::upgrade()->url()->get('upgrade.auth'))
                                ->text(__('Finish the update.')),
                        ]),
                ]);
        }

        App::upgrade()->page()->open(
            __('Dotclear update'),
            self::$step === '' ? App::upgrade()->page()->jsLoad('js/_update.js') : '',
            App::upgrade()->page()->breadcrumb(
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

        App::upgrade()->page()->helpBlock('core_upgrade');
        App::upgrade()->page()->close();
    }
}
