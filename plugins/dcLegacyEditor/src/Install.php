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
use dcNsProcess;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion('dcLegacyEditor', dcCore::app()->plugins->moduleInfo('dcLegacyEditor', 'version'));

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        $s = dcCore::app()->blog->settings->get('dcLegacyEditor');
        $s->put('active', true, 'boolean', 'dcLegacyEditor plugin activated ?', false, true);

        return true;
    }
}
