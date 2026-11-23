<?php

/**
 * @brief       DotclearWatch uninstall class.
 * @ingroup     DotclearWatch
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\DotclearWatch;

use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Plugin\Uninstaller\Uninstaller;

class Uninstall
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::UNINSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Uninstaller::instance()
            ->addUserAction(
                'settings',
                'delete_all',
                My::id()
            )
            ->addUserAction(
                'plugins',
                'delete',
                My::id()
            )
            ->addUserAction(
                'versions',
                'delete',
                My::id()
            )
            ->addUserAction(
                'vars',
                'delete',
                My::id()
            )
            ->addUserAction(
                'preferences',
                'delete_all',
                My::id()
            )
            ->addDirectAction(
                'settings',
                'delete_all',
                My::id()
            )
            ->addDirectAction(
                'plugins',
                'delete',
                My::id()
            )
            ->addDirectAction(
                'versions',
                'delete',
                My::id()
            )
            ->addDirectAction(
                'vars',
                'delete',
                My::id()
            )
            ->addDirectAction(
                'preferences',
                'delete_all',
                My::id()
            )
        ;

        // no custom action
        return false;
    }
}
