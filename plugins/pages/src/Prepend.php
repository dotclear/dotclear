<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use dcCore;
use Dotclear\Core\PostType;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->url->register('pages', 'pages', '^pages/(.+)$', FrontendUrl::pages(...));
        dcCore::app()->url->register('pagespreview', 'pagespreview', '^pagespreview/(.+)$', FrontendUrl::pagespreview(...));

        dcCore::app()->post_types->set(new PostType('page', '', dcCore::app()->url->getURLFor('pages', '%s'), 'Pages'));

        return true;
    }
}
