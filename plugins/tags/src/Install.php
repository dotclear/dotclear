<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Interface\Core\UserWorkspaceInterface;

/**
 * @brief   The module install process.
 * @ingroup simpleMenu
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!App::auth()->prefs()->get('interface')->prefExists('tag_list_format', true)) {
            // Migrate old option if possible
            $format = App::auth()->getOption('tag_list_format') ?? 'more';
            App::auth()->prefs()->get('interface')->put(
                'tag_list_format',
                $format,
                UserWorkspaceInterface::WS_STRING,
                'Tag list format',
                true,
                true
            );
        }

        return true;
    }
}
