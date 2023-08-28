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

use Dotclear\Core\Core;
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

        Core::url()->register('pages', 'pages', '^pages/(.+)$', [FrontendUrl::class, 'pages']);
        Core::url()->register('pagespreview', 'pagespreview', '^pagespreview/(.+)$', [FrontendUrl::class, 'pagespreview']);

        $admin_url = defined('DC_CONTEXT_ADMIN') ? urldecode(Core::backend()->url->get('admin.plugin', ['p' => 'pages', 'act' => 'page', 'id' => '%d'], '&')) : '';
        Core::postTypes()->set(new PostType('page', $admin_url, Core::url()->getURLFor('pages', '%s'), 'Pages'));

        return true;
    }
}
