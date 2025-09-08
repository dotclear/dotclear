<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Plugin\blogroll\Status\Link;

/**
 * @brief   The module install process.
 * @ingroup blogroll
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

        $schema = App::db()->structure();

        $schema->{Blogroll::LINK_TABLE_NAME}
            ->field('link_id', 'bigint', 0, false)
            ->field('blog_id', 'varchar', 32, false)
            ->field('link_href', 'varchar', 255, false)
            ->field('link_title', 'varchar', 255, false)
            ->field('link_desc', 'varchar', 255, true)
            ->field('link_lang', 'varchar', 5, true)
            ->field('link_xfn', 'varchar', 255, true)
            ->field('link_position', 'integer', 0, false, 0)
            ->field('link_status', 'smallint', 0, false, Link::ONLINE)

            ->primary('pk_link', 'link_id')
            ->index('idx_link_blog_id', 'btree', 'blog_id')
            ->reference('fk_link_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade')
        ;

        App::db()->structure()->synchronize($schema);

        return true;
    }
}
