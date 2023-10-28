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
use Exception;

class Tools extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        try {
            if (!empty($_POST['cleartplcache'])) {
                App::cache()->emptyTemplatesCache();
                Notices::addSuccessNotice(__('Templates cache directory emptied.'));
                App::upgrade()->url()->redirect('upgrade.tools');
            }
            if (!empty($_POST['clearrepocache'])) {
                App::cache()->emptyModulesStoreCache();
                Notices::addSuccessNotice(__('Repositories cache directory emptied.'));
                App::upgrade()->url()->redirect('upgrade.tools');
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            __('Tools'),
            '',
            Page::breadcrumb(
                [
                    __('Dotclear update') => '',
                    __('Tools')           => '',
                ]
            )
        );

        echo
        '<form action="' . App::upgrade()->url()->get('upgrade.tools') . '" method="post">' .
        '<h3>' . __('Cache management') . '</h3>' .
        '<p><input type="submit" name="cleartplcache" value="' . __('Empty templates cache directory') . '" /></p>' .
        '<p><input type="submit" name="clearrepocache" value="' . __('Empty repositories cache directory') . '" />' .
        App::nonce()->getFormNonce() . '</p>' .
        '</form>';

        Page::close();
    }
}
