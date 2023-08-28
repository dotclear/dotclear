<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use dcCore;
use Dotclear\Core\Core;
use Dotclear\Core\Process;

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

        // Rename settings namespace
        if (version_compare(Core::version()->getVersion(My::id()), '1.0', '<=')
            && Core::blog()->settings->exists('dclegacyeditor')
        ) {
            Core::blog()->settings->delNamespace(My::id());
            Core::blog()->settings->renNamespace('dclegacyeditor', My::id());
        }

        My::settings()->put('active', true, 'boolean', 'dcLegacyEditor plugin activated ?', false, true);

        return true;
    }
}
