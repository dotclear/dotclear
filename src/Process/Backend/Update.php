<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\Update as CoreUpdate;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/update.php
 */
class Update extends Process
{
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
                        __('System')          => '',
                        __('Dotclear update') => '',
                    ]
                )
            );
            echo
            '<h3>' . __('Precheck update error') . '</h3>' .
            '<p>' . __('Backup directory does not exist') . '</p>';
            Page::close();
            exit;
        }

        if (!is_readable(App::config()->digestsRoot())) {
            Page::open(
                __('Dotclear update'),
                '',
                Page::breadcrumb(
                    [
                        __('System')          => '',
                        __('Dotclear update') => '',
                    ]
                )
            );
            echo
            '<h3>' . __('Precheck update error') . '</h3>' .
            '<p>' . __('Access denied') . '</p>';
            Page::close();
            exit;
        }

        App::backend()->updater = new CoreUpdate(App::config()->coreUpdateUrl(), 'dotclear', App::config()->coreUpdateCanal(), App::config()->cacheRoot() . '/versions');
        App::backend()->new_v   = App::backend()->updater->check(App::config()->dotclearVersion(), !empty($_GET['nocache']));

        App::backend()->zip_file       = '';
        App::backend()->version_info   = '';
        App::backend()->update_warning = false;

        if (App::backend()->new_v) {
            App::backend()->zip_file       = App::config()->backupRoot() . '/' . basename((string) App::backend()->updater->getFileURL());
            App::backend()->version_info   = App::backend()->updater->getInfoURL();
            App::backend()->update_warning = App::backend()->updater->getWarning();
        }

        # Hide "update me" message
        if (!empty($_GET['hide_msg'])) {
            App::backend()->updater->setNotify(false);
            App::backend()->url()->redirect('admin.home');
        }

        App::backend()->step = $_GET['step'] ?? '';
        App::backend()->step = in_array(App::backend()->step, ['check', 'download', 'backup', 'unzip']) ? App::backend()->step : '';

        App::backend()->default_tab = !empty($_GET['tab']) ? Html::escapeHTML($_GET['tab']) : 'update';
        if (!empty($_POST['backup_file'])) {
            App::backend()->default_tab = 'files';
        }

        $archives = [];
        foreach (Files::scanDir(App::config()->backupRoot()) as $v) {
            if (preg_match('/backup-([0-9A-Za-z\.-]+).zip/', $v)) {
                $archives[] = $v;
            }
        }
        if (!empty($archives)) {
            usort($archives, fn ($a, $b) => $a <=> $b);
        } else {
            App::backend()->default_tab = 'update';
        }
        App::backend()->archives = $archives;

        return self::status(true);
    }

    public static function process(): bool
    {
        # Revert or delete backup file
        if (!empty($_POST['backup_file']) && in_array($_POST['backup_file'], App::backend()->archives)) {
            $b_file = $_POST['backup_file'];

            try {
                if (!empty($_POST['b_del'])) {
                    if (!@unlink(App::config()->backupRoot() . '/' . $b_file)) {
                        throw new Exception(sprintf(__('Unable to delete file %s'), Html::escapeHTML($b_file)));
                    }
                    App::backend()->url()->redirect('admin.update', ['tab' => 'files']);
                }

                if (!empty($_POST['b_revert'])) {
                    $zip = new Unzip(App::config()->backupRoot() . '/' . $b_file);
                    $zip->unzipAll(App::config()->backupRoot() . '/');
                    @unlink(App::config()->backupRoot() . '/' . $b_file);
                    App::backend()->url()->redirect('admin.update', ['tab' => 'files']);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Upgrade process
        if (App::backend()->new_v && App::backend()->step) {
            try {
                App::backend()->updater->setForcedFiles('inc/digests');

                switch (App::backend()->step) {
                    case 'check':
                        App::backend()->updater->checkIntegrity(App::config()->dotclearRoot() . '/inc/digests', App::config()->dotclearRoot());
                        App::backend()->url()->redirect('admin.update', ['step' => 'download']);

                        break;
                    case 'download':
                        App::backend()->updater->download(App::backend()->zip_file);
                        if (!App::backend()->updater->checkDownload(App::backend()->zip_file)) {
                            throw new Exception(
                                sprintf(
                                    __('Downloaded Dotclear archive seems to be corrupted. Try <a %s>download it</a> again.'),
                                    'href="' . App::backend()->url()->get('admin.update', ['step' => 'download']) . '"'
                                ) .
                                ' ' .
                                __('If this problem persists try to ' .
                                    '<a href="https://dotclear.org/download">update manually</a>.')
                            );
                        }
                        App::backend()->url()->redirect('admin.update', ['step' => 'backup']);

                        break;
                    case 'backup':
                        App::backend()->updater->backup(
                            App::backend()->zip_file,
                            'dotclear/inc/digests',
                            App::config()->dotclearRoot(),
                            App::config()->dotclearRoot() . '/inc/digests',
                            App::config()->backupRoot() . '/backup-' . App::config()->dotclearVersion() . '.zip'
                        );
                        App::backend()->url()->redirect('admin.update', ['step' => 'unzip']);

                        break;
                    case 'unzip':
                        App::backend()->updater->performUpgrade(
                            App::backend()->zip_file,
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

                if ($e->getCode() == CoreUpdate::ERR_FILES_CHANGED) {
                    $msg = __('The following files of your Dotclear installation have been modified so we won\'t try to update your installation. Please try to <a href="https://dotclear.org/download">update manually</a>.');
                } elseif ($e->getCode() == CoreUpdate::ERR_FILES_UNREADABLE) {
                    $msg = sprintf(
                        __('The following files of your Dotclear installation are not readable. Please fix this or try to make a backup file named %s manually.'),
                        '<strong>backup-' . App::config()->dotclearVersion() . '.zip</strong>'
                    );
                } elseif ($e->getCode() == CoreUpdate::ERR_FILES_UNWRITALBE) {
                    $msg = __('The following files of your Dotclear installation cannot be written. Please fix this or try to <a href="https://dotclear.org/download">update manually</a>.');
                }

                if (count($bad_files = App::backend()->updater->getBadFiles())) {
                    $msg .= '<ul><li><strong>' . implode('</strong></li><li><strong>', $bad_files) . '</strong></li></ul>';
                }

                App::error()->add($msg);

                # --BEHAVIOR-- adminDCUpdateException -- Exception
                App::behavior()->callBehavior('adminDCUpdateException', $e);
            }
        }

        return true;
    }

    public static function render(): void
    {
        $safe_mode = false;

        if (App::backend()->step == 'unzip' && !App::error()->flag()) {
            // Check if safe_mode is ON, will be use below
            $safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

            // Update done, need to go back to authentication (see below), but we need
            // to kill the admin session before sending any header
            App::backend()->killAdminSession();
        }

        Page::open(
            __('Dotclear update'),
            (!App::backend()->step ?
                Page::jsPageTabs(App::backend()->default_tab) .
                Page::jsLoad('js/_update.js')
                : ''),
            Page::breadcrumb(
                [
                    __('System')          => '',
                    __('Dotclear update') => '',
                ]
            )
        );

        if (!App::error()->flag() && !empty($_GET['nocache'])) {
            Notices::success(__('Manual checking of update done successfully.'));
        }

        if (!App::backend()->step) {
            echo
            '<div class="multi-part" id="update" title="' . __('Dotclear update') . '">';

            // Warning about PHP version if necessary
            if (version_compare(phpversion(), App::config()->nextRequiredPhp(), '<')) {
                echo
                '<p class="info more-info">' .
                sprintf(
                    __('The next versions of Dotclear will not support PHP version < %s, your\'s is currently %s'),
                    App::config()->nextRequiredPhp(),
                    phpversion()
                ) .
                '</p>';
            }
            if (empty(App::backend()->new_v)) {
                echo
                '<p><strong>' . __('No newer Dotclear version available.') . '</strong></p>' .
                '<form action="' . App::backend()->url()->get('admin.update') . '" method="get">' .
                '<p><input type="hidden" name="process" value="Update" />' .
                '<p><input type="hidden" name="nocache" value="1" />' .
                '<input type="submit" value="' . __('Force checking update Dotclear') . '" /></p>' .
                '</form>';
            } else {
                echo
                '<p class="static-msg dc-update updt-info">' . sprintf(__('Dotclear %s is available.'), App::backend()->new_v) .
                (App::backend()->version_info ? ' <a href="' . App::backend()->version_info . '" title="' . __('Information about this version') . '">(' .
                __('Information about this version') . ')</a>' : '') .
                '</p>';
                if (version_compare(phpversion(), App::backend()->updater->getPHPVersion()) < 0) {
                    echo
                    '<p class="warning-msg">' . sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), App::backend()->updater->getPHPVersion()) . '</p>';
                } else {
                    if (App::backend()->update_warning) {
                        echo
                        '<p class="warning-msg">' . __('This update may potentially require some precautions, you should carefully read the information post associated with this release (see above).') . '</p>';
                    }
                    echo
                    '<p>' . __('To upgrade your Dotclear installation simply click on the following button. A backup file of your current installation will be created in your root directory.') . '</p>' .
                    '<form action="' . App::backend()->url()->get('admin.update') . '" method="get">' .
                    '<p><input type="hidden" name="step" value="check" />' .
                    '<p><input type="hidden" name="process" value="Update" />' .
                    '<input type="submit" value="' . __('Update Dotclear') . '" /></p>' .
                    '</form>';
                }
            }
            echo
            '</div>';

            if (!empty(App::backend()->archives)) {
                $archives = App::backend()->archives;
                echo
                '<div class="multi-part" id="files" title="' . __('Manage backup files') . '">';

                echo
                '<h3>' . __('Update backup files') . '</h3>' .
                '<p>' . __('The following files are backups of previously updates. You can revert your previous installation or delete theses files.') . '</p>';

                echo
                '<form action="' . App::backend()->url()->get('admin.update') . '" method="post">';
                foreach ($archives as $archive) {
                    echo
                    '<p><label class="classic">' . form::radio(['backup_file'], Html::escapeHTML($archive)) . ' ' .
                    Html::escapeHTML($archive) . '</label></p>';
                }

                echo
                '<p><strong>' . __('Please note that reverting your Dotclear version may have some unwanted side-effects. Consider reverting only if you experience strong issues with this new version.') . '</strong> ' .
                sprintf(__('You should not revert to version prior to last one (%s).'), end($archives)) . '</p>' .
                '<p><input type="submit" class="delete" name="b_del" value="' . __('Delete selected file') . '" /> ' .
                '<input type="submit" name="b_revert" value="' . __('Revert to selected file') . '" />' .
                App::nonce()->getFormNonce() . '</p>' .
                '</form></div>';
            }
        } elseif (App::backend()->step == 'unzip' && !App::error()->flag()) {
            // Keep safe-mode for next authentication
            $params = $safe_mode ? ['safe_mode' => 1] : []; // @phpstan-ignore-line

            echo
            '<p class="message">' .
            __("Congratulations, you're one click away from the end of the update.") .
            ' <strong><a href="' . App::backend()->url()->get('admin.auth', $params) . '" class="button submit">' . __('Finish the update.') . '</a></strong>' .
            '</p>';
        }

        Page::helpBlock('core_update');
        Page::close();
    }
}
