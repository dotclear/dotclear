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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

class installAntispam
{
    /**
     * Installs the plugin.
     *
     * @return     mixed
     */
    public static function install()
    {
        $version = dcCore::app()->plugins->moduleInfo('antispam', 'version');
        if (version_compare(dcCore::app()->getVersion('antispam'), $version, '>=')) {
            return;
        }

        /* Database schema
        -------------------------------------------------------- */
        $schema = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);

        $schema->spamrule
            ->rule_id('bigint', 0, false)
            ->blog_id('varchar', 32, true)
            ->rule_type('varchar', 16, false, "'word'")
            ->rule_content('varchar', 128, false)

            ->primary('pk_spamrule', 'rule_id')
        ;

        $schema->spamrule->index('idx_spamrule_blog_id', 'btree', 'blog_id');
        $schema->spamrule->reference('fk_spamrule_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');

        if ($schema->driver() === 'pgsql') {
            $schema->spamrule->index('idx_spamrule_blog_id_null', 'btree', '(blog_id IS NULL)');
        }

        // Schema installation
        (new dbStruct(dcCore::app()->con, dcCore::app()->prefix))->synchronize($schema);

        // Creating default wordslist
        if (dcCore::app()->getVersion('antispam') === null) {
            (new dcFilterWords())->defaultWordsList();
        }

        dcCore::app()->blog->settings->addNamespace('antispam');
        dcCore::app()->blog->settings->antispam->put('antispam_moderation_ttl', 0, 'integer', 'Antispam Moderation TTL (days)', false);

        dcCore::app()->setVersion('antispam', $version);

        return true;
    }
}

return installAntispam::install();
