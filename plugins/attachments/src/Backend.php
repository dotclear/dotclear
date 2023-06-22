<?php
/**
 * @brief attachments, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('attachments') . __('Manage post attachments');

        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminPostFormItems' => [BackendBehaviors::class, 'adminPostFormItems'],
            'adminPostAfterForm' => [BackendBehaviors::class, 'adminPostAfterForm'],
            'adminPostHeaders'   => fn () => My::jsLoad('post.js'),
            'adminPageFormItems' => [BackendBehaviors::class, 'adminPostFormItems'],
            'adminPageAfterForm' => [BackendBehaviors::class, 'adminPostAfterForm'],
            'adminPageHeaders'   => fn () => My::jsLoad('post.js'),
            'adminPageHelpBlock' => [BackendBehaviors::class, 'adminPageHelpBlock'],
        ]);

        return true;
    }
}
