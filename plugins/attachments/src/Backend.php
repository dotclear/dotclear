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
use dcPage;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminPostFormItems' => [BackendBehaviors::class, 'adminPostFormItems'],
            'adminPostAfterForm' => [BackendBehaviors::class, 'adminPostAfterForm'],
            'adminPostHeaders'   => fn () => dcPage::jsModuleLoad('attachments/js/post.js'),
            'adminPageFormItems' => [BackendBehaviors::class, 'adminPostFormItems'],
            'adminPageAfterForm' => [BackendBehaviors::class, 'adminPostAfterForm'],
            'adminPageHeaders'   => fn () => dcPage::jsModuleLoad('attachments/js/post.js'),
            'adminPageHelpBlock' => [BackendBehaviors::class, 'adminPageHelpBlock'],
        ]);

        return true;
    }
}
