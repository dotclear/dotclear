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
use Dotclear\Core\Upgrade\UpdateAttic;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Hidden,
    Label,
    Link,
    Note,
    Para,
    Radio,
    Submit,
    Table,
    Td,
    Text,
    Tr
};
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   Core incremental upgrade process page.
 *
 * @since   2.29
 */
class Attic extends Process
{
    /**
     * Step in update process.
     */
    private static string $step = '';

    /**
     * The downloaded release zip file name.
     */
    private static string $zip_file = '';

    /**
     * The releases stack.
     *
     * @var     array<string, array<string, string>>
     */
    private static array $releases = [];

    /**
     * The attic updater instance.
     */
    private static UpdateAttic $updater;

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
                        __('Attic')           => '',
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
                        __('Attic')           => '',
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

        return self::status(true);
    }

    public static function process(): bool
    {
        self::$step    = !empty($_REQUEST['step']) && in_array($_REQUEST['step'], ['confirm', 'check', 'download', 'backup', 'unzip']) ? $_REQUEST['step'] : '';
        self::$updater = new UpdateAttic(App::config()->coreAtticUrl(), App::config()->cacheRoot() . DIRECTORY_SEPARATOR . UpdateAttic::CACHE_FOLDER);
        self::$updater->check(App::config()->dotclearVersion(), !empty($_GET['nocache']));
        if (!empty($_REQUEST['version'])) {
            self::$zip_file = self::$updater->selectVersion($_REQUEST['version']);
        }

        if (!self::$updater->getVersion() || empty(self::$step)) {
            self::$step     = '';
            self::$releases = self::$updater->getReleases(App::config()->dotclearVersion());

            return true;
        }

        try {
            self::$updater->setForcedFiles('inc/digests');

            // No redirect avec each step as we need selected version in a POST form
            switch (self::$step) {
                case 'check':
                    self::$updater->checkIntegrity(App::config()->dotclearRoot() . '/inc/digests', App::config()->dotclearRoot());

                    break;
                case 'download':
                    self::$updater->download(self::$zip_file);
                    if (!self::$updater->checkDownload(self::$zip_file)) {
                        throw new Exception(
                            sprintf(
                                __('Downloaded Dotclear archive seems to be corrupted. Try <a %s>download it</a> again.'),
                                'href="' . App::upgrade()->url()->get('upgrade.attic', ['step' => 'download', 'version' => (string) self::$updater->getVersion()]) . '"'
                            ) .
                            ' ' .
                            __('If this problem persists try to ' .
                                '<a href="https://dotclear.org/download">update manually</a>.')
                        );
                    }

                    App::upgrade()->url()->redirect('upgrade.attic', ['step' => 'backup', 'version' => (string) self::$updater->getVersion()]);

                    break;
                case 'backup':
                    self::$updater->backup(
                        self::$zip_file,
                        'dotclear/inc/digests',
                        App::config()->dotclearRoot(),
                        App::config()->dotclearRoot() . '/inc/digests',
                        App::config()->backupRoot() . '/backup-' . App::config()->dotclearVersion() . '.zip'
                    );

                    App::upgrade()->url()->redirect('upgrade.attic', ['step' => 'unzip', 'version' => (string) self::$updater->getVersion()]);

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

            if ($e->getCode() == self::$updater::ERR_FILES_CHANGED) {
                $msg = __('The following files of your Dotclear installation have been modified so we won\'t try to update your installation. Please try to <a href="https://dotclear.org/download">update manually</a>.');
                $msg .= ' (<a href="' . App::upgrade()->url()->get('upgrade.digests') . '">' . __('You can bypass this warning by updating installation disgets file.') . '</a>)';
            } elseif ($e->getCode() == self::$updater::ERR_FILES_UNREADABLE) {
                $msg = sprintf(
                    __('The following files of your Dotclear installation are not readable. Please fix this or try to make a backup file named %s manually.'),
                    '<strong>backup-' . App::config()->dotclearVersion() . '.zip</strong>'
                );
            } elseif ($e->getCode() == self::$updater::ERR_FILES_UNWRITALBE) {
                $msg = __('The following files of your Dotclear installation cannot be written. Please fix this or try to <a href="https://dotclear.org/download">update manually</a>.');
            }

            if (($bad_files = self::$updater->getBadFiles()) !== []) {
                $msg .= '<ul><li><strong>' . implode('</strong></li><li><strong>', $bad_files) . '</strong></li></ul>';
            }

            App::error()->add($msg);
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
            // No redirect with no step as we need selected version in a POST form
            if (self::$releases === []) {
                $items[] = (new Para())
                    ->items([
                        (new Text('strong', __('No newer Dotclear version available.'))),
                    ]);

                if (App::error()->flag() || empty($_GET['nocache'])) {
                    $items[] = (new Form('atticcache'))
                        ->method('get')
                        ->action(App::upgrade()->url()->get('upgrade.attic'))
                        ->fields([
                            (new Para())
                                ->items([
                                    (new Hidden(['process'], 'Attic')),
                                    (new Hidden(['nocache'], '1')),
                                    (new Submit(['submit'], __('Force checking update Dotclear'))),
                                ]),
                        ]);
                }
            } else {
                $options = [];
                $i       = 0;
                foreach (self::$releases as $version => $release) {
                    $i++;
                    $options[] = (new Tr())
                        ->class('line')
                        ->items([
                            (new Td())
                                ->class('minimal')
                                ->items([
                                    (new Radio(['version', 'version' . $i]))
                                        ->value(Html::escapeHTML($version)),
                                ]),
                            (new Td())
                                ->class('nowrap')
                                ->items([
                                    (new Label(Html::escapeHTML($version), Label::OUTSIDE_LABEL_AFTER, 'version' . $i))
                                        ->class('classic'),
                                ]),
                            (new Td())
                                ->class('nowrap')
                                ->items([
                                    (new Text('', sprintf(__('Required PHP version %s or higher'), $release['php']))),
                                ]),
                            (new Td())
                                ->class('maximal')
                                ->items([
                                    (new Link())
                                        ->href($release['info'])
                                        ->title(__('Release note'))
                                        ->text(__('Release note')),
                                ]),
                        ]);
                }

                $items[] = (new Form('atticstep1'))
                    ->class('fieldset')
                    ->method('post')
                    ->action(App::upgrade()->url()->get('upgrade.attic'))
                    ->fields([
                        (new Text('p', __('Select intermediate stable release to update to:'))),
                        (new Div())
                            ->class('table-outer')
                            ->items([
                                (new Table())
                                    ->items($options),
                            ]),
                        (new Para())
                            ->items([
                                (new Hidden(['step'], 'check')),
                                App::nonce()->formNonce(),
                                (new Submit(['submit'], __('Select'))),
                            ]),
                        (new Text('p', __('There are no additionnal informations about releases listed here, you should carefully read the information post associated with selected release on Dotclear\'s blog.')))
                            ->class('warning'),
                    ]);
            }
        } elseif (self::$step === 'check' && !App::error()->flag()) {
            $items[] = (new Form('atticstep2'))
                ->class('fieldset')
                ->method('post')
                ->action(App::upgrade()->url()->get('upgrade.attic'))
                ->fields([
                    (new Text('p', sprintf(__('Are you sure to update to version %s?'), Html::escapeHTML((string) self::$updater->getVersion())))),
                    (new Para())
                        ->items([
                            (new Hidden(['version'], Html::escapeHTML((string) self::$updater->getVersion()))),
                            (new Hidden(['step'], 'download')),
                            App::nonce()->formNonce(),
                            (new Submit(['submit'], __('Update Dotclear'))),
                        ]),
                ]);
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
            '',
            Page::breadcrumb(
                [
                    __('Dotclear update') => '',
                    __('Attic')           => '',
                ]
            )
        );

        if (!empty($items)) {
            echo (new Div())
                ->items([
                    (new Note())
                        ->class('static-msg')
                        ->text(__('On this page you can update dotclear to a release between yours and latest.')),
                    ...$items,
                ])
                ->render();
        }

        Page::close();
    }
}
