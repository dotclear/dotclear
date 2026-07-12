<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module install process.
 * @ingroup Uninstaller
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            My::settings()->put(
                'no_direct_uninstall',
                false,
                App::blogWorkspace()::NS_BOOL,
                'Disabled uninstall actions on module deletion',
                false,
                true
            );

            return true;
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());

            return false;
        }
    }
}
