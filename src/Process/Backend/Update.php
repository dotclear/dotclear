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
use Dotclear\Helper\Html\Html;

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

        # Hide "update me" message
        if (!empty($_GET['hide_msg'])) {
            App::backend()->updater->setNotify(false);
            App::backend()->url()->redirect('admin.home');
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        return self::status();
    }

    public static function render(): void
    {
        // Keep for < 2.29 process vs new code
        if (($_GET['step'] ?? '') == 'unzip' && !App::error()->flag()) {
            // Check if safe_mode is ON, will be use below
            $safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

            // Update done, need to go back to authentication (see below), but we need
            // to kill the admin session before sending any header
            App::backend()->killAdminSession();

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

            // Keep safe-mode for next authentication
            $params = $safe_mode ? ['safe_mode' => 1] : []; // @phpstan-ignore-line

            echo
            '<p class="message">' .
            __("Congratulations, you're one click away from the end of the update.") .
            ' <strong><a href="' . App::backend()->url()->get('admin.auth', $params) . '" class="button submit">' . __('Finish the update.') . '</a></strong>' .
            '</p>';

            Page::helpBlock('core_update');
            Page::close();

            return;
        }

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

        if (!App::error()->flag() && !empty($_GET['nocache'])) {
            Notices::success(__('Manual checking of update done successfully.'));
        }

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
            '<p class="static-msg dc-update updt-info">' . sprintf(__('Dotclear %s is available.'), App::backend()->new_v) . '</p>';
            if (version_compare(phpversion(), App::backend()->updater->getPHPVersion()) < 0) {
                echo
                '<p class="warning-msg">' . sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), App::backend()->updater->getPHPVersion()) . '</p>';
            } else {
                echo
                '<p><a title="' . Html::escapeHTML(__('Dotclear upgrade dashboard')) . '" href="' . App::backend()->url()->get('upgrade.home') . '">' . __('To upgrade your Dotclear installation go to upgrade dashboard and follow instructions.') . '</a></p>';
            }
        }

        Page::helpBlock('core_update');
        Page::close();
    }
}
