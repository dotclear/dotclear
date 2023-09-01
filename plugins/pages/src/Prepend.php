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

use Dotclear\App;
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

        App::url()->register('pages', 'pages', '^pages/(.+)$', FrontendUrl::pages(...));
        App::url()->register('pagespreview', 'pagespreview', '^pagespreview/(.+)$', FrontendUrl::pagespreview(...));

        App::postTypes()->set(new PostType('page', '', App::url()->getURLFor('pages', '%s'), 'Pages'));

        return true;
    }
}
