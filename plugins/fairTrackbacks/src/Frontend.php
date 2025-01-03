<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\fairTrackbacks;

use ArrayObject;
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

        if (defined('DC_FAIRTRACKBACKS_FORCE') && constant('DC_FAIRTRACKBACKS_FORCE')) {
            App::behavior()->addBehavior('AntispamInitFilters', function (ArrayObject $stack): string {
                $stack->append(AntispamFilterFairTrackbacks::class);

                return '';
            });
        }

        return true;
    }
}
