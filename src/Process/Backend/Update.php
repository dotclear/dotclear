<?php
/**
 * @since 2.27 Before as admin/update.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use dcUpdate;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

class Update extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        if (!defined('DC_BACKUP_PATH')) {
            define('DC_BACKUP_PATH', DC_ROOT);
        } else {
            // Check backup path existence
            if (!is_dir(DC_BACKUP_PATH)) {
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
        }

        if (!is_readable(DC_DIGESTS)) {
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

        Core::backend()->updater = new dcUpdate(DC_UPDATE_URL, 'dotclear', DC_UPDATE_VERSION, DC_TPL_CACHE . '/versions');
        Core::backend()->new_v   = Core::backend()->updater->check(DC_VERSION, !empty($_GET['nocache']));

        Core::backend()->zip_file       = '';
        Core::backend()->version_info   = '';
        Core::backend()->update_warning = false;

        if (Core::backend()->new_v) {
            Core::backend()->zip_file       = DC_BACKUP_PATH . '/' . basename(Core::backend()->updater->getFileURL());
            Core::backend()->version_info   = Core::backend()->updater->getInfoURL();
            Core::backend()->update_warning = Core::backend()->updater->getWarning();
        }

        # Hide "update me" message
        if (!empty($_GET['hide_msg'])) {
            Core::backend()->updater->setNotify(false);
            Http::redirect('index.php');
        }

        Core::backend()->step = $_GET['step'] ?? '';
        Core::backend()->step = in_array(Core::backend()->step, ['check', 'download', 'backup', 'unzip']) ? Core::backend()->step : '';

        Core::backend()->default_tab = !empty($_GET['tab']) ? Html::escapeHTML($_GET['tab']) : 'update';
        if (!empty($_POST['backup_file'])) {
            Core::backend()->default_tab = 'files';
        }

        $archives = [];
        foreach (Files::scanDir(DC_BACKUP_PATH) as $v) {
            if (preg_match('/backup-([0-9A-Za-z\.-]+).zip/', $v)) {
                $archives[] = $v;
            }
        }
        if (!empty($archives)) {
            usort($archives, fn ($a, $b) => $a <=> $b);
        } else {
            Core::backend()->default_tab = 'update';
        }
        Core::backend()->archives = $archives;

        return self::status(true);
    }

    public static function process(): bool
    {
        # Revert or delete backup file
        if (!empty($_POST['backup_file']) && in_array($_POST['backup_file'], Core::backend()->archives)) {
            $b_file = $_POST['backup_file'];

            try {
                if (!empty($_POST['b_del'])) {
                    if (!@unlink(DC_BACKUP_PATH . '/' . $b_file)) {
                        throw new Exception(sprintf(__('Unable to delete file %s'), Html::escapeHTML($b_file)));
                    }
                    Core::backend()->url->redirect('admin.update', ['tab' => 'files']);
                }

                if (!empty($_POST['b_revert'])) {
                    $zip = new Unzip(DC_BACKUP_PATH . '/' . $b_file);
                    $zip->unzipAll(DC_BACKUP_PATH . '/');
                    @unlink(DC_BACKUP_PATH . '/' . $b_file);
                    Core::backend()->url->redirect('admin.update', ['tab' => 'files']);
                }
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        # Upgrade process
        if (Core::backend()->new_v && Core::backend()->step) {
            try {
                Core::backend()->updater->setForcedFiles('inc/digests');

                switch (Core::backend()->step) {
                    case 'check':
                        Core::backend()->updater->checkIntegrity(DC_ROOT . '/inc/digests', DC_ROOT);
                        Core::backend()->url->redirect('admin.update', ['step' => 'download']);

                        break;
                    case 'download':
                        Core::backend()->updater->download(Core::backend()->zip_file);
                        if (!Core::backend()->updater->checkDownload(Core::backend()->zip_file)) {
                            throw new Exception(
                                sprintf(
                                    __('Downloaded Dotclear archive seems to be corrupted. Try <a %s>download it</a> again.'),
                                    'href="' . Core::backend()->url->get('admin.update', ['step' => 'download']) . '"'
                                ) .
                                ' ' .
                                __('If this problem persists try to ' .
                                    '<a href="https://dotclear.org/download">update manually</a>.')
                            );
                        }
                        Core::backend()->url->redirect('admin.update', ['step' => 'backup']);

                        break;
                    case 'backup':
                        Core::backend()->updater->backup(
                            Core::backend()->zip_file,
                            'dotclear/inc/digests',
                            DC_ROOT,
                            DC_ROOT . '/inc/digests',
                            DC_BACKUP_PATH . '/backup-' . DC_VERSION . '.zip'
                        );
                        Core::backend()->url->redirect('admin.update', ['step' => 'unzip']);

                        break;
                    case 'unzip':
                        Core::backend()->updater->performUpgrade(
                            Core::backend()->zip_file,
                            'dotclear/inc/digests',
                            'dotclear',
                            DC_ROOT,
                            DC_ROOT . '/inc/digests'
                        );

                        // Disable REST service until next authentication
                        Core::rest()->enableRestServer(false);

                        break;
                }
            } catch (Exception $e) {
                $msg = $e->getMessage();

                if ($e->getCode() == dcUpdate::ERR_FILES_CHANGED) {
                    $msg = __('The following files of your Dotclear installation have been modified so we won\'t try to update your installation. Please try to <a href="https://dotclear.org/download">update manually</a>.');
                } elseif ($e->getCode() == dcUpdate::ERR_FILES_UNREADABLE) {
                    $msg = sprintf(
                        __('The following files of your Dotclear installation are not readable. Please fix this or try to make a backup file named %s manually.'),
                        '<strong>backup-' . DC_VERSION . '.zip</strong>'
                    );
                } elseif ($e->getCode() == dcUpdate::ERR_FILES_UNWRITALBE) {
                    $msg = __('The following files of your Dotclear installation cannot be written. Please fix this or try to <a href="https://dotclear.org/download">update manually</a>.');
                }

                if (count($bad_files = Core::backend()->updater->getBadFiles())) {
                    $msg .= '<ul><li><strong>' . implode('</strong></li><li><strong>', $bad_files) . '</strong></li></ul>';
                }

                Core::error()->add($msg);

                # --BEHAVIOR-- adminDCUpdateException -- Exception
                Core::behavior()->callBehavior('adminDCUpdateException', $e);
            }
        }

        return true;
    }

    public static function render(): void
    {
        $safe_mode = false;

        if (Core::backend()->step == 'unzip' && !Core::error()->flag()) {
            // Check if safe_mode is ON, will be use below
            $safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

            // Update done, need to go back to authentication (see below), but we need
            // to kill the admin session before sending any header
            Core::backend()->killAdminSession();
        }

        Page::open(
            __('Dotclear update'),
            (!Core::backend()->step ?
                Page::jsPageTabs(Core::backend()->default_tab) .
                Page::jsLoad('js/_update.js')
                : ''),
            Page::breadcrumb(
                [
                    __('System')          => '',
                    __('Dotclear update') => '',
                ]
            )
        );

        if (!Core::error()->flag() && !empty($_GET['nocache'])) {
            Notices::success(__('Manual checking of update done successfully.'));
        }

        if (!Core::backend()->step) {
            echo
            '<div class="multi-part" id="update" title="' . __('Dotclear update') . '">';

            // Warning about PHP version if necessary
            if (version_compare(phpversion(), DC_NEXT_REQUIRED_PHP, '<')) {
                echo
                '<p class="info more-info">' .
                sprintf(
                    __('The next versions of Dotclear will not support PHP version < %s, your\'s is currently %s'),
                    DC_NEXT_REQUIRED_PHP,
                    phpversion()
                ) .
                '</p>';
            }
            if (empty(Core::backend()->new_v)) {
                echo
                '<p><strong>' . __('No newer Dotclear version available.') . '</strong></p>' .
                '<form action="' . Core::backend()->url->get('admin.update') . '" method="get">' .
                '<p><input type="hidden" name="process" value="Update" />' .
                '<p><input type="hidden" name="nocache" value="1" />' .
                '<input type="submit" value="' . __('Force checking update Dotclear') . '" /></p>' .
                '</form>';
            } else {
                echo
                '<p class="static-msg dc-update updt-info">' . sprintf(__('Dotclear %s is available.'), Core::backend()->new_v) .
                (Core::backend()->version_info ? ' <a href="' . Core::backend()->version_info . '" title="' . __('Information about this version') . '">(' .
                __('Information about this version') . ')</a>' : '') .
                '</p>';
                if (version_compare(phpversion(), Core::backend()->updater->getPHPVersion()) < 0) {
                    echo
                    '<p class="warning-msg">' . sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), Core::backend()->updater->getPHPVersion()) . '</p>';
                } else {
                    if (Core::backend()->update_warning) {
                        echo
                        '<p class="warning-msg">' . __('This update may potentially require some precautions, you should carefully read the information post associated with this release (see above).') . '</p>';
                    }
                    echo
                    '<p>' . __('To upgrade your Dotclear installation simply click on the following button. A backup file of your current installation will be created in your root directory.') . '</p>' .
                    '<form action="' . Core::backend()->url->get('admin.update') . '" method="get">' .
                    '<p><input type="hidden" name="step" value="check" />' .
                    '<p><input type="hidden" name="process" value="Update" />' .
                    '<input type="submit" value="' . __('Update Dotclear') . '" /></p>' .
                    '</form>';
                }
            }
            echo
            '</div>';

            if (!empty(Core::backend()->archives)) {
                $archives = Core::backend()->archives;
                echo
                '<div class="multi-part" id="files" title="' . __('Manage backup files') . '">';

                echo
                '<h3>' . __('Update backup files') . '</h3>' .
                '<p>' . __('The following files are backups of previously updates. You can revert your previous installation or delete theses files.') . '</p>';

                echo
                '<form action="' . Core::backend()->url->get('admin.update') . '" method="post">';
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
                Core::nonce()->getFormNonce() . '</p>' .
                '</form></div>';
            }
        } elseif (Core::backend()->step == 'unzip' && !Core::error()->flag()) {
            // Keep safe-mode for next authentication
            $params = $safe_mode ? ['safe_mode' => 1] : []; // @phpstan-ignore-line

            echo
            '<p class="message">' .
            __("Congratulations, you're one click away from the end of the update.") .
            ' <strong><a href="' . Core::backend()->url->get('admin.auth', $params) . '" class="button submit">' . __('Finish the update.') . '</a></strong>' .
            '</p>';
        }

        Page::helpBlock('core_update');
        Page::close();
    }
}
