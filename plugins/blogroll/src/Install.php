<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use dcCore;
use dcNsProcess;
use Dotclear\Database\Structure;
use initBlogroll;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        $module       = basename(dirname(__DIR__));
        static::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion($module, dcCore::app()->plugins->moduleInfo($module, 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        $schema = new Structure(dcCore::app()->con, dcCore::app()->prefix);

        $schema->{initBlogroll::LINK_TABLE_NAME}
            ->link_id('bigint', 0, false)
            ->blog_id('varchar', 32, false)
            ->link_href('varchar', 255, false)
            ->link_title('varchar', 255, false)
            ->link_desc('varchar', 255, true)
            ->link_lang('varchar', 5, true)
            ->link_xfn('varchar', 255, true)
            ->link_position('integer', 0, false, 0)

            ->primary('pk_link', 'link_id')
            ->index('idx_link_blog_id', 'btree', 'blog_id')
            ->reference('fk_link_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade')
        ;

        (new Structure(dcCore::app()->con, dcCore::app()->prefix))->synchronize($schema);

        return true;
    }
}
