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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class installBlogroll
{
    /**
     * Installs the plugin.
     *
     * @return     mixed
     */
    public static function install()
    {
        $version = dcCore::app()->plugins->moduleInfo('blogroll', 'version');

        if (version_compare((string) dcCore::app()->getVersion('blogroll'), $version, '>=')) {
            return;
        }

        /* Database schema
        -------------------------------------------------------- */
        $schema = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);

        $schema->link
            ->link_id('bigint', 0, false)
            ->blog_id('varchar', 32, false)
            ->link_href('varchar', 255, false)
            ->link_title('varchar', 255, false)
            ->link_desc('varchar', 255, true)
            ->link_lang('varchar', 5, true)
            ->link_xfn('varchar', 255, true)
            ->link_position('integer', 0, false, 0)

            ->primary('pk_link', 'link_id')
        ;

        $schema->link->index('idx_link_blog_id', 'btree', 'blog_id');
        $schema->link->reference('fk_link_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');

        # Schema installation
        (new dbStruct(dcCore::app()->con, dcCore::app()->prefix))->synchronize($schema);

        dcCore::app()->setVersion('blogroll', $version);

        return true;
    }
}

return installBlogroll::install();
