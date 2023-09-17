<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\fairTrackbacks;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup fairTrackbacks
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (DC_FAIRTRACKBACKS_FORCE) {
            App::behavior()->addBehavior('AntispamInitFilters', function ($stack) {
                $stack->append(AntispamFilterFairTrackbacks::class);
            });
        }

        return true;
    }
}
