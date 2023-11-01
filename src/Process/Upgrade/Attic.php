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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Upgrade\UpdateAttic;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

/**
 * @brief   Core incremental upgrade process page.
 *
 * @since   2.29
 */
class Attic extends Process
{
    private static string $step     = '';
    private static string $zip_file = '';
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
                        __('Incremental')     => '',
                    ]
                )
            );
            echo
            '<h3>' . __('Precheck update error') . '</h3>' .
            '<p>' . __('It seems that backup directory does not exist, upgrade can not be performed.') . '</p>';

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
                        __('Incremental')     => '',
                    ]
                )
            );
            echo
            '<h3>' . __('Precheck update error') . '</h3>' .
            '<p>' . __('It seems that there are no "digests" file on your system, upgrade can not be performed.') . '</p>';

            Page::helpBlock('core_upgrade');
            Page::close();
            exit;
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        self::$step    = !empty($_REQUEST['step']) && in_array($_REQUEST['step'], ['confirm', 'check', 'download', 'backup', 'unzip']) ? $_REQUEST['step'] : '';
        self::$updater = new UpdateAttic(App::config()->coreAtticUrl(), App::config()->cacheRoot() . DIRECTORY_SEPARATOR . 'versions');
        self::$updater->check(App::config()->dotclearVersion(), !empty($_GET['nocache']));
        if (!empty($_POST['version'])) {
            self::$zip_file = self::$updater->selectVersion($_POST['version']);
        }

        if (!self::$updater->getVersion() || empty(self::$step)) {
            self::$step = '';

            return true;
        }

        try {
            self::$updater->setForcedFiles('inc/digests');

            // No redirect avec each step as we need selected version in a POST form
            switch (self::$step) {
                case 'check':
                    self::$updater->checkIntegrity(App::config()->dotclearRoot() . '/inc/digests', App::config()->dotclearRoot());

                    Notices::addSuccessNotice(__('Dotclear integrity checked successfully'));

                    break;
                case 'download':
                    self::$updater->download(self::$zip_file);
                    if (!self::$updater->checkDownload(self::$zip_file)) {
                        throw new Exception(
                            sprintf(
                                __('Downloaded Dotclear archive seems to be corrupted. Try <a %s>download it</a> again.'),
                                'href="' . App::upgrade()->url()->get('upgrade.attic', ['step' => 'download']) . '"'
                            ) .
                            ' ' .
                            __('If this problem persists try to ' .
                                '<a href="https://dotclear.org/download">update manually</a>.')
                        );
                    }

                    Notices::addSuccessNotice(sprintf(__('File %s downloaded successfully'), Html::escapeHTML((string) self::$updater->getVersion())));

                    break;
                case 'backup':
                    self::$updater->backup(
                        self::$zip_file,
                        'dotclear/inc/digests',
                        App::config()->dotclearRoot(),
                        App::config()->dotclearRoot() . '/inc/digests',
                        App::config()->backupRoot() . '/backup-' . App::config()->dotclearVersion() . '.zip'
                    );

                    Notices::addSuccessNotice(__('Dotclear backuped successfully'));

                    break;
                case 'unzip':
                    /*                    self::$updater->performUpgrade(
                                            self::$zip_file,
                                            'dotclear/inc/digests',
                                            'dotclear',
                                            App::config()->dotclearRoot(),
                                            App::config()->dotclearRoot() . '/inc/digests'
                                        );
                    */
                    // Disable REST service until next authentication
                    App::rest()->enableRestServer(false);

                    Notices::addSuccessNotice(__('Dotclear overwrited successfully'));

                    break;
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();

            if ($e->getCode() == self::$updater::ERR_FILES_CHANGED) {
                $msg = __('The following files of your Dotclear installation have been modified so we won\'t try to update your installation. Please try to <a href="https://dotclear.org/download">update manually</a>.');
                $msg .= ' ' . '(<a href="' . App::upgrade()->url()->get('upgrade.digests') . '">' . __('You can bypass this warning by updating installation disgets file.') . '</a>)';
            } elseif ($e->getCode() == self::$updater::ERR_FILES_UNREADABLE) {
                $msg = sprintf(
                    __('The following files of your Dotclear installation are not readable. Please fix this or try to make a backup file named %s manually.'),
                    '<strong>backup-' . App::config()->dotclearVersion() . '.zip</strong>'
                );
            } elseif ($e->getCode() == self::$updater::ERR_FILES_UNWRITALBE) {
                $msg = __('The following files of your Dotclear installation cannot be written. Please fix this or try to <a href="https://dotclear.org/download">update manually</a>.');
            }

            if (count($bad_files = self::$updater->getBadFiles())) {
                $msg .= '<ul><li><strong>' . implode('</strong></li><li><strong>', $bad_files) . '</strong></li></ul>';
            }

            App::error()->add($msg);
        }

        return true;
    }

    public static function render(): void
    {
        if (self::$step == 'unzip' && !App::error()->flag()) {
            // Update done, need to go back to authentication (see below), but we need
            // to kill the admin session before sending any header
            App::upgrade()->killAdminSession();
        }

        Page::open(
            __('Dotclear update'),
            '',
            Page::breadcrumb(
                [
                    __('Dotclear update') => '',
                    __('Incremental')     => '',
                ]
            )
        );

        if (empty(self::$step)) {
            $releases = self::$updater->getReleases(App::config()->dotclearVersion());
            // No redirect avec each step as we need selected version in a POST form
            if (empty($releases)) {
                echo
                '<p><strong>' . __('No newer Dotclear version available.') . '</strong></p>';

                if (App::error()->flag() || empty($_GET['nocache'])) {
                    echo
                    '<form action="' . App::upgrade()->url()->get('upgrade.attic') . '" method="get">' .
                    '<p><input type="hidden" name="process" value="Upgrade" />' .
                    '<p><input type="hidden" name="nocache" value="1" />' .
                    '<input type="submit" value="' . __('Force checking update Dotclear') . '" /></p>' .
                    '</form>';
                }
            } else {
                echo
                '<form action="' . App::upgrade()->url()->get('upgrade.attic') . '" method="post">' .
                '<h3>' . sprintf(__('Step %s of %s: %s'), '1', '5', __('Select')) . '</h3>' .
                '<p class="warning-msg">' . __('There are no additionnal informations about incremental release here, you should carefully read the information post associated with selected release on Dotclear\'s blog.') . '</p>' .
                '<p>' . __('Select intermediate version to update to:') . '</p>';

                foreach ($releases as $version => $release) {
                    echo
                    '<p><label class="classic">' . form::radio(['version'], Html::escapeHTML($version)) . ' ' .
                    Html::escapeHTML($version) . '</label></p>';
                }

                echo
                '<p><input type="hidden" name="step" value="check" />' .
                App::nonce()->getFormNonce() .
                '<input type="submit" value="' . __('Continue to check') . '" /></p>' .
                '</form>';
            }
        } elseif (self::$step == 'check' && !App::error()->flag()) {
            echo
            '<form action="' . App::upgrade()->url()->get('upgrade.attic') . '" method="post">' .
                '<h3>' . sprintf(__('Step %s of %s: %s'), '2', '5', __('Check')) . '</h3>' .
            '<p>' . sprintf(__('Are you sure to update to version %s?'), Html::escapeHTML((string) self::$updater->getVersion())) . '</p>' .
            '<p><input type="hidden" name="version" value="' . Html::escapeHTML((string) self::$updater->getVersion()) . '" />' .
            '<p><input type="hidden" name="step" value="download" />' .
            App::nonce()->getFormNonce() .
            '<input type="submit" value="' . __('Continue to download') . '" /></p>' .
            '</form>';
        } elseif (self::$step == 'download' && !App::error()->flag()) {
            echo
            '<form action="' . App::upgrade()->url()->get('upgrade.attic') . '" method="post">' .
            '<h3>' . sprintf(__('Step %s of %s: %s'), '3', '5', __('Download')) . '</h3>' .
            '<p><input type="hidden" name="version" value="' . Html::escapeHTML((string) self::$updater->getVersion()) . '" />' .
            '<p><input type="hidden" name="step" value="backup" />' .
            App::nonce()->getFormNonce() .
            '<input type="submit" value="' . __('Continue to backup') . '" /></p>' .
            '</form>';
        } elseif (self::$step == 'backup' && !App::error()->flag()) {
            echo
            '<form action="' . App::upgrade()->url()->get('upgrade.attic') . '" method="post">' .
            '<h3>' . sprintf(__('Step %s of %s: %s'), '4', '5', __('Backup')) . '</h3>' .
            '<p><input type="hidden" name="version" value="' . Html::escapeHTML((string) self::$updater->getVersion()) . '" />' .
            '<p><input type="hidden" name="step" value="unzip" />' .
            App::nonce()->getFormNonce() .
            '<input type="submit" value="' . __('Continue to overwrite') . '" /></p>' .
            '</form>';
        } elseif (self::$step == 'unzip' && !App::error()->flag()) {
            echo
            '<h3>' . sprintf(__('Step %s of %s: %s'), '5', '5', __('Overwrite')) . '</h3>' .
            '<p class="message">' .
            __("Congratulations, you're one click away from the end of the update.") .
            ' <strong><a href="' . App::upgrade()->url()->get('upgrade.auth') . '" class="button submit">' . __('Finish the update.') . '</a></strong>' .
            '</p>';
        }

        Page::close();
    }
}
