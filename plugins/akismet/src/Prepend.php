<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\akismet;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module prepend process.
 * @ingroup akismet
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Akismet');
        __('Akismet interface for Dotclear');

        return self::status(App::config()->configPath() !== '');
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('AntispamInitFilters', function (ArrayObject $stack): string {
            $stack->append(AntispamFilterAkismet::class);

            return '';
        });

        return true;
    }
}
