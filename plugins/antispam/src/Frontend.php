<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use dcCore;
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'publicBeforeCommentCreate'   => [Antispam::class, 'isSpam'],
            'publicBeforeTrackbackCreate' => [Antispam::class, 'isSpam'],
            'publicBeforeDocumentV2'      => [Antispam::class, 'purgeOldSpam'],
        ]);

        return true;
    }
}
