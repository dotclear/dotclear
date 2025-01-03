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
 * @brief   The module prepend process.
 * @ingroup fairTrackbacks
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Fair Trackbacks');
        __('Trackback validity check');

        if (!defined('DC_FAIRTRACKBACKS_FORCE')) {
            define('DC_FAIRTRACKBACKS_FORCE', false);
        }

        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (defined('DC_FAIRTRACKBACKS_FORCE') && !constant('DC_FAIRTRACKBACKS_FORCE')) {
            App::behavior()->addBehavior('AntispamInitFilters', function (ArrayObject $stack): string {
                $stack->append(AntispamFilterFairTrackbacks::class);

                return '';
            });
        }

        return true;
    }
}
