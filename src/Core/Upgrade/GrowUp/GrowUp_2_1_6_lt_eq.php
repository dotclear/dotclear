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

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\Core\Upgrade\Upgrade;

class GrowUp_2_1_6_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
                // ie7js has been upgraded
                'admin/js/ie7/ie7-base64.php ',
                'admin/js/ie7/ie7-content.htc',
                'admin/js/ie7/ie7-core.js',
                'admin/js/ie7/ie7-css2-selectors.js',
                'admin/js/ie7/ie7-css3-selectors.js',
                'admin/js/ie7/ie7-css-strict.js',
                'admin/js/ie7/ie7-dhtml.js',
                'admin/js/ie7/ie7-dynamic-attributes.js',
                'admin/js/ie7/ie7-fixed.js',
                'admin/js/ie7/ie7-graphics.js',
                'admin/js/ie7/ie7-html4.js',
                'admin/js/ie7/ie7-ie5.js',
                'admin/js/ie7/ie7-layout.js',
                'admin/js/ie7/ie7-load.htc',
                'admin/js/ie7/ie7-object.htc',
                'admin/js/ie7/ie7-overflow.js',
                'admin/js/ie7/ie7-quirks.js',
                'admin/js/ie7/ie7-server.css',
                'admin/js/ie7/ie7-standard-p.js',
                'admin/js/ie7/ie7-xml-extras.js',
            ],
        );

        return $cleanup_sessions;
    }
}
