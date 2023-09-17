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

/**
 * @brief   The module prepend process.
 * @ingroup Uninstaller
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Add default cleaners to Uninstaller
        App::behavior()->addBehavior('UninstallerCleanersConstruct', function (CleanersStack $cleaners): void {
            $cleaners
                ->set(new Cleaner\Settings())
                ->set(new Cleaner\Preferences())
                ->set(new Cleaner\Tables())
                ->set(new Cleaner\Versions())
                ->set(new Cleaner\Logs())
                ->set(new Cleaner\Caches())
                ->set(new Cleaner\Vars())
                ->set(new Cleaner\Themes())
                ->set(new Cleaner\Plugins())
            ;
        });

        return true;
    }
}
