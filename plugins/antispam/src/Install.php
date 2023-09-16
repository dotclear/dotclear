<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\Structure;
use initAntispam;

/**
 * @brief   The module install process.
 * @ingroup antispam
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        /* Database schema
        -------------------------------------------------------- */
        $schema = new Structure(App::con(), App::con()->prefix());

        $schema->{initAntispam::SPAMRULE_TABLE_NAME}
            ->rule_id('bigint', 0, false)
            ->blog_id('varchar', 32, true)
            ->rule_type('varchar', 16, false, "'word'")
            ->rule_content('varchar', 128, false)

            ->primary('pk_spamrule', 'rule_id')

            ->index('idx_spamrule_blog_id', 'btree', 'blog_id')
            ->reference('fk_spamrule_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade')
        ;

        if ($schema->driver() === 'pgsql') {
            $schema->{initAntispam::SPAMRULE_TABLE_NAME}->index('idx_spamrule_blog_id_null', 'btree', '(blog_id IS NULL)');
        }

        // Schema installation
        (new Structure(App::con(), App::con()->prefix()))->synchronize($schema);

        // Creating default wordslist
        if (App::version()->getVersion(My::id()) === '') {
            (new Filters\Words())->defaultWordsList();
        }

        My::settings()->put('antispam_moderation_ttl', 0, 'integer', 'Antispam Moderation TTL (days)', false);

        return true;
    }
}
