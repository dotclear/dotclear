<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * Dotclear upgrade procedure.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Upgrade\GrowUp;

use Dotclear\Upgrade\Upgrade;

class GrowUp_2_9_1_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Some settings and prefs should be moved from string to array
        Upgrade::prefs2array('dashboard', 'favorites');
        Upgrade::prefs2array('interface', 'media_last_dirs');

        return $cleanup_sessions;
    }
}
