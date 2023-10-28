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
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Backup extends Process
{
    /**
     * @var     array<int, string>  $archives
     */
    private static array $archives = [];

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

        $archives = [];
        foreach (Files::scanDir(App::config()->backupRoot()) as $v) {
            if (preg_match('/backup-([0-9A-Za-z\.-]+).zip/', $v)) {
                $archives[] = $v;
            }
        }
        if (!empty($archives)) {
            usort($archives, fn ($a, $b) => $a <=> $b);
        }

        self::$archives = $archives;

        return self::status(true);
    }

    public static function process(): bool
    {
        # Revert or delete backup file
        if (!empty($_POST['backup_file']) && in_array($_POST['backup_file'], self::$archives)) {
            $b_file = $_POST['backup_file'];

            try {
                if (!empty($_POST['b_del'])) {
                    if (!@unlink(App::config()->backupRoot() . '/' . $b_file)) {
                        throw new Exception(sprintf(__('Unable to delete file %s'), Html::escapeHTML($b_file)));
                    }
                    Notices::addSuccessNotice(__('Backup deleted.'));
                    App::upgrade()->url()->redirect('upgrade.backup');
                }

                if (!empty($_POST['b_revert'])) {
                    $zip = new Unzip(App::config()->backupRoot() . '/' . $b_file);
                    $zip->unzipAll(App::config()->backupRoot() . '/');
                    @unlink(App::config()->backupRoot() . '/' . $b_file);
                    Notices::addSuccessNotice(__('Backup restored.'));
                    App::upgrade()->url()->redirect('upgrade.backup');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            __('Backups'),
            '',
            Page::breadcrumb(
                [
                    __('System')              => '',
                    __('Backups and restore') => '',
                ]
            )
        );

        echo Notices::getNotices();

        if (empty(self::$archives)) {
            echo
            '<p class="info">' . __('There are no backups available.') . '</p>';
        } else {
            $archives = self::$archives;
            echo
            '<h3>' . __('Update backup files') . '</h3>' .
            '<p>' . __('The following files are backups of previously updates. You can revert your previous installation or delete theses files.') . '</p>';

            echo
            '<form action="' . App::upgrade()->url()->get('upgrade.backup') . '" method="post">';
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
            '</form>';
        }

        Page::close();
    }
}
