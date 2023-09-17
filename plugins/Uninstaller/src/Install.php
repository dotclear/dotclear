<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

use Dotclear\App;
use Dotclear\Core\Process;
use Exception;

/**
 * @brief   The module install process.
 * @ingroup Uninstaller
 */
class Install extends Process
{
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
                'boolean',
                'Disabled uninstall actions on module deletion',
                false,
                true
            );

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
