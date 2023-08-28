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

use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Core::behavior()->addBehaviors([
            'publicBeforeCommentCreate'   => [Antispam::class, 'isSpam'],
            'publicBeforeTrackbackCreate' => [Antispam::class, 'isSpam'],
            'publicBeforeDocumentV2'      => [Antispam::class, 'purgeOldSpam'],
        ]);

        return true;
    }
}
