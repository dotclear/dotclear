<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

require __DIR__ . '/../inc/admin/prepend.php';

class adminUpdate
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::checkSuper();

        if (!defined('DC_BACKUP_PATH')) {
            define('DC_BACKUP_PATH', DC_ROOT);
        } else {
            // Check backup path existence
            if (!is_dir(DC_BACKUP_PATH)) {
                dcPage::open(
                    __('Dotclear update'),
                    '',
                    dcPage::breadcrumb(
                        [
                            __('System')          => '',
                            __('Dotclear update') => '',
                        ]
                    )
                );
                echo
                '<h3>' . __('Precheck update error') . '</h3>' .
                '<p>' . __('Backup directory does not exist') . '</p>';
                dcPage::close();
                exit;
            }
        }

        if (!is_readable(DC_DIGESTS)) {
            dcPage::open(
                __('Dotclear update'),
                '',
                dcPage::breadcrumb(
                    [
                        __('System')          => '',
                        __('Dotclear update') => '',
                    ]
                )
            );
            echo
            '<h3>' . __('Precheck update error') . '</h3>' .
            '<p>' . __('Access denied') . '</p>';
            dcPage::close();
            exit;
        }

        dcCore::app()->admin->updater = new dcUpdate(DC_UPDATE_URL, 'dotclear', DC_UPDATE_VERSION, DC_TPL_CACHE . '/versions');
        dcCore::app()->admin->new_v   = dcCore::app()->admin->updater->check(DC_VERSION, !empty($_GET['nocache']));

        dcCore::app()->admin->zip_file       = '';
        dcCore::app()->admin->version_info   = '';
        dcCore::app()->admin->update_warning = false;

        if (dcCore::app()->admin->new_v) {
            dcCore::app()->admin->zip_file       = DC_BACKUP_PATH . '/' . basename(dcCore::app()->admin->updater->getFileURL());
            dcCore::app()->admin->version_info   = dcCore::app()->admin->updater->getInfoURL();
            dcCore::app()->admin->update_warning = dcCore::app()->admin->updater->getWarning();
        }

        # Hide "update me" message
        if (!empty($_GET['hide_msg'])) {
            dcCore::app()->admin->updater->setNotify(false);
            Http::redirect('index.php');
        }

        dcCore::app()->admin->setPageURL(dcCore::app()->adminurl->get('admin.update'));

        dcCore::app()->admin->step = $_GET['step'] ?? '';
        dcCore::app()->admin->step = in_array(dcCore::app()->admin->step, ['check', 'download', 'backup', 'unzip']) ? dcCore::app()->admin->step : '';

        dcCore::app()->admin->default_tab = !empty($_GET['tab']) ? Html::escapeHTML($_GET['tab']) : 'update';
        if (!empty($_POST['backup_file'])) {
            dcCore::app()->admin->default_tab = 'files';
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
            dcCore::app()->admin->default_tab = 'update';
        }
        dcCore::app()->admin->archives = $archives;
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        # Revert or delete backup file
        if (!empty($_POST['backup_file']) && in_array($_POST['backup_file'], dcCore::app()->admin->archives)) {
            $b_file = $_POST['backup_file'];

            try {
                if (!empty($_POST['b_del'])) {
                    if (!@unlink(DC_BACKUP_PATH . '/' . $b_file)) {
                        throw new Exception(sprintf(__('Unable to delete file %s'), Html::escapeHTML($b_file)));
                    }
                    Http::redirect(dcCore::app()->admin->getPageURL() . '?tab=files');
                }

                if (!empty($_POST['b_revert'])) {
                    $zip = new Unzip(DC_BACKUP_PATH . '/' . $b_file);
                    $zip->unzipAll(DC_BACKUP_PATH . '/');
                    @unlink(DC_BACKUP_PATH . '/' . $b_file);
                    Http::redirect(dcCore::app()->admin->getPageURL() . '?tab=files');
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # Upgrade process
        if (dcCore::app()->admin->new_v && dcCore::app()->admin->step) {
            try {
                dcCore::app()->admin->updater->setForcedFiles('inc/digests');

                switch (dcCore::app()->admin->step) {
                    case 'check':
                        dcCore::app()->admin->updater->checkIntegrity(DC_ROOT . '/inc/digests', DC_ROOT);
                        Http::redirect(dcCore::app()->admin->getPageURL() . '?step=download');

                        break;
                    case 'download':
                        dcCore::app()->admin->updater->download(dcCore::app()->admin->zip_file);
                        if (!dcCore::app()->admin->updater->checkDownload(dcCore::app()->admin->zip_file)) {
                            throw new Exception(
                                sprintf(__('Downloaded Dotclear archive seems to be corrupted. ' .
                                    'Try <a %s>download it</a> again.'), 'href="' . dcCore::app()->admin->getPageURL() . '?step=download"') .
                                ' ' .
                                __('If this problem persists try to ' .
                                    '<a href="https://dotclear.org/download">update manually</a>.')
                            );
                        }
                        Http::redirect(dcCore::app()->admin->getPageURL() . '?step=backup');

                        break;
                    case 'backup':
                        dcCore::app()->admin->updater->backup(
                            dcCore::app()->admin->zip_file,
                            'dotclear/inc/digests',
                            DC_ROOT,
                            DC_ROOT . '/inc/digests',
                            DC_BACKUP_PATH . '/backup-' . DC_VERSION . '.zip'
                        );
                        Http::redirect(dcCore::app()->admin->getPageURL() . '?step=unzip');

                        break;
                    case 'unzip':
                        dcCore::app()->admin->updater->performUpgrade(
                            dcCore::app()->admin->zip_file,
                            'dotclear/inc/digests',
                            'dotclear',
                            DC_ROOT,
                            DC_ROOT . '/inc/digests'
                        );

                        // Disable REST service until next authentication
                        dcCore::app()->enableRestServer(false);

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

                if (count($bad_files = dcCore::app()->admin->updater->getBadFiles())) {
                    $msg .= '<ul><li><strong>' . implode('</strong></li><li><strong>', $bad_files) . '</strong></li></ul>';
                }

                dcCore::app()->error->add($msg);

                # --BEHAVIOR-- adminDCUpdateException -- Exception
                dcCore::app()->callBehavior('adminDCUpdateException', $e);
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        $safe_mode = false;

        if (dcCore::app()->admin->step == 'unzip' && !dcCore::app()->error->flag()) {
            // Check if safe_mode is ON, will be use below
            $safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

            // Update done, need to go back to authentication (see below), but we need
            // to kill the admin session before sending any header
            dcCore::app()->killAdminSession();
        }

        dcPage::open(
            __('Dotclear update'),
            (!dcCore::app()->admin->step ?
                dcPage::jsPageTabs(dcCore::app()->admin->default_tab) .
                dcPage::jsLoad('js/_update.js')
                : ''),
            dcPage::breadcrumb(
                [
                    __('System')          => '',
                    __('Dotclear update') => '',
                ]
            )
        );

        if (!dcCore::app()->error->flag() && !empty($_GET['nocache'])) {
            dcPage::success(__('Manual checking of update done successfully.'));
        }

        if (!dcCore::app()->admin->step) {
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
            if (empty(dcCore::app()->admin->new_v)) {
                echo
                '<p><strong>' . __('No newer Dotclear version available.') . '</strong></p>' .
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="get">' .
                '<p><input type="hidden" name="nocache" value="1" />' .
                '<input type="submit" value="' . __('Force checking update Dotclear') . '" /></p>' .
                '</form>';
            } else {
                echo
                '<p class="static-msg dc-update updt-info">' . sprintf(__('Dotclear %s is available.'), dcCore::app()->admin->new_v) .
                (dcCore::app()->admin->version_info ? ' <a href="' . dcCore::app()->admin->version_info . '" title="' . __('Information about this version') . '">(' .
                __('Information about this version') . ')</a>' : '') .
                '</p>';
                if (version_compare(phpversion(), dcCore::app()->admin->updater->getPHPVersion()) < 0) {
                    echo
                    '<p class="warning-msg">' . sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), dcCore::app()->admin->updater->getPHPVersion()) . '</p>';
                } else {
                    if (dcCore::app()->admin->update_warning) {
                        echo
                        '<p class="warning-msg">' . __('This update may potentially require some precautions, you should carefully read the information post associated with this release (see above).') . '</p>';
                    }
                    echo
                    '<p>' . __('To upgrade your Dotclear installation simply click on the following button. A backup file of your current installation will be created in your root directory.') . '</p>' .
                    '<form action="' . dcCore::app()->admin->getPageURL() . '" method="get">' .
                    '<p><input type="hidden" name="step" value="check" />' .
                    '<input type="submit" value="' . __('Update Dotclear') . '" /></p>' .
                    '</form>';
                }
            }
            echo
            '</div>';

            if (!empty(dcCore::app()->admin->archives)) {
                $archives = dcCore::app()->admin->archives;
                echo
                '<div class="multi-part" id="files" title="' . __('Manage backup files') . '">';

                echo
                '<h3>' . __('Update backup files') . '</h3>' .
                '<p>' . __('The following files are backups of previously updates. You can revert your previous installation or delete theses files.') . '</p>';

                echo
                '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">';
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
                dcCore::app()->formNonce() . '</p>' .
                '</form></div>';
            }
        } elseif (dcCore::app()->admin->step == 'unzip' && !dcCore::app()->error->flag()) {
            // Keep safe-mode for next authentication
            $params = $safe_mode ? ['safe_mode' => 1] : []; // @phpstan-ignore-line

            echo
            '<p class="message">' .
            __("Congratulations, you're one click away from the end of the update.") .
            ' <strong><a href="' . dcCore::app()->adminurl->get('admin.auth', $params) . '" class="button submit">' . __('Finish the update.') . '</a></strong>' .
            '</p>';
        }

        dcPage::helpBlock('core_update');
        dcPage::close();
    }
}

adminUpdate::init();
adminUpdate::process();
adminUpdate::render();
