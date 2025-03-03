<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\App;
use Dotclear\Core\PostType;
use Dotclear\Core\Process;

/**
 * @brief   The module prepend process.
 * @ingroup pages
 */
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

        App::postTypes()->set(new PostType('page', '', App::url()->getURLFor('pages', '%s'), 'Pages', ''));

        return true;
    }
}
