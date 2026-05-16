<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema;

use Dotclear\App;
use Dotclear\Database\Structure;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\CategoriesInterface;
use Dotclear\Interface\Core\CredentialInterface;
use Dotclear\Interface\Core\LogInterface;
use Dotclear\Interface\Core\MetaInterface;
use Dotclear\Interface\Core\NoticeInterface;
use Dotclear\Interface\Core\PostMediaInterface;
use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\TrackbackInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Dotclear\Interface\Core\VersionInterface;

/**
 * @brief   Installation helpers
 *
 * @since 2.33
 */
class Schema
{
    /**
     * Fill given structure with Dotclear database schema.
     *
     * @param   Structure   $struct     The database structure handler instance
     */
    public static function fillStructure(Structure $struct): void
    {
        /* Tables
        -------------------------------------------------------- */
        $struct->table(BlogInterface::BLOG_TABLE_NAME)
            ->field('blog_id', 'varchar', 32, false)
            ->field('blog_uid', 'varchar', 32, false)
            ->field('blog_creadt', 'timestamp', 0, false, 'now()')
            ->field('blog_upddt', 'timestamp', 0, false, 'now()')
            ->field('blog_url', 'varchar', 255, false)
            ->field('blog_name', 'varchar', 255, false)
            ->field('blog_desc', 'text', 0, true)
            ->field('blog_status', 'smallint', 0, false, App::status()->blog()::ONLINE) // 2.24+ transition ', update)

            ->primary('pk_blog', 'blog_id')
        ;

        $struct->table(CategoriesInterface::CATEGORY_TABLE_NAME)
            ->field('cat_id', 'bigint', 0, false)
            ->field('blog_id', 'varchar', 32, false)
            ->field('cat_title', 'varchar', 255, false)
            ->field('cat_url', 'varchar', 255, false)
            ->field('cat_desc', 'text', 0, true)
            ->field('cat_position', 'integer', 0, true, 0)
            ->field('cat_lft', 'integer', 0, true)
            ->field('cat_rgt', 'integer', 0, true)

            ->primary('pk_category', 'cat_id')

            ->unique('uk_cat_url', 'cat_url', 'blog_id')
        ;

        $struct->table(SessionInterface::SESSION_TABLE_NAME)
            ->field('ses_id', 'varchar', 40, false)
            ->field('ses_time', 'integer', 0, false, 0)
            ->field('ses_start', 'integer', 0, false, 0)
            ->field('ses_value', 'text', 0, false)

            ->primary('pk_session', 'ses_id')
        ;

        $struct->table(BlogWorkspaceInterface::NS_TABLE_NAME)
            ->field('setting_id', 'varchar', 255, false)
            ->field('blog_id', 'varchar', 32, true)
            ->field('setting_ns', 'varchar', 32, false, "'system'")
            ->field('setting_value', 'text', 0, true, null)
            ->field('setting_type', 'varchar', 8, false, "'string'")
            ->field('setting_label', 'text', 0, true)

            ->unique('uk_setting', 'setting_ns', 'setting_id', 'blog_id')
        ;

        $struct->table(AuthInterface::USER_TABLE_NAME)
            ->field('user_id', 'varchar', 32, false)
            ->field('user_super', 'smallint', 0, true)
            ->field('user_status', 'smallint', 0, false, App::status()->user()::ENABLED)
            ->field('user_pwd', 'varchar', 255, false)
            ->field('user_change_pwd', 'smallint', 0, false, 0)
            ->field('user_recover_key', 'varchar', 32, true, null)
            ->field('user_name', 'varchar', 255, true, null)
            ->field('user_firstname', 'varchar', 255, true, null)
            ->field('user_displayname', 'varchar', 255, true, null)
            ->field('user_email', 'varchar', 255, true, null)
            ->field('user_url', 'varchar', 255, true, null)
            ->field('user_desc', 'text', 0, true)
            ->field('user_default_blog', 'varchar', 32, true, null)
            ->field('user_options', 'text', 0, true)
            ->field('user_lang', 'varchar', 5, true, null)
            ->field('user_tz', 'varchar', 128, false, "'UTC'")
            ->field('user_post_status', 'smallint', 0, false, App::status()->post()::PENDING)
            ->field('user_creadt', 'timestamp', 0, false, 'now()')
            ->field('user_upddt', 'timestamp', 0, false, 'now()')

            ->primary('pk_user', 'user_id')
        ;

        $struct->table(AuthInterface::PERMISSIONS_TABLE_NAME)
            ->field('user_id', 'varchar', 32, false)
            ->field('blog_id', 'varchar', 32, false)
            ->field('permissions', 'text', 0, true)

            ->primary('pk_permissions', 'user_id', 'blog_id')
        ;

        $struct->table(BlogInterface::POST_TABLE_NAME)
            ->field('post_id', 'bigint', 0, false)
            ->field('blog_id', 'varchar', 32, false)
            ->field('user_id', 'varchar', 32, false)
            ->field('cat_id', 'bigint', 0, true)
            ->field('post_dt', 'timestamp', 0, false, 'now()')
            ->field('post_tz', 'varchar', 128, false, "'UTC'")
            ->field('post_creadt', 'timestamp', 0, false, 'now()')
            ->field('post_upddt', 'timestamp', 0, false, 'now()')
            ->field('post_password', 'varchar', 32, true, null)
            ->field('post_type', 'varchar', 32, false, "'post'")
            ->field('post_format', 'varchar', 32, false, "'xhtml'")
            ->field('post_url', 'varchar', 255, false)
            ->field('post_lang', 'varchar', 5, true, null)
            ->field('post_title', 'varchar', 255, true, null)
            ->field('post_excerpt', 'text', 0, true, null)
            ->field('post_excerpt_xhtml', 'text', 0, true, null)
            ->field('post_content', 'text', 0, true, null)
            ->field('post_content_xhtml', 'text', 0, false)
            ->field('post_notes', 'text', 0, true, null)
            ->field('post_meta', 'text', 0, true, null)
            ->field('post_words', 'text', 0, true, null)
            ->field('post_status', 'smallint', 0, false, App::status()->post()::UNPUBLISHED)
            ->field('post_firstpub', 'smallint', 0, false, 0)
            ->field('post_selected', 'smallint', 0, false, 0)
            ->field('post_position', 'integer', 0, false, 0)
            ->field('post_open_comment', 'smallint', 0, false, 0)
            ->field('post_open_tb', 'smallint', 0, false, 0)
            ->field('nb_comment', 'integer', 0, false, 0)
            ->field('nb_trackback', 'integer', 0, false, 0)

            ->primary('pk_post', 'post_id')

            ->unique('uk_post_url', 'post_url', 'post_type', 'blog_id')
        ;

        $struct->table(PostMediaInterface::MEDIA_TABLE_NAME)
            ->field('media_id', 'bigint', 0, false)
            ->field('user_id', 'varchar', 32, false)
            ->field('media_path', 'varchar', 255, false)
            ->field('media_title', 'varchar', 255, false)
            ->field('media_file', 'varchar', 255, false)
            ->field('media_dir', 'varchar', 255, false, "'.'")
            ->field('media_meta', 'text', 0, true, null)
            ->field('media_dt', 'timestamp', 0, false, 'now()')
            ->field('media_creadt', 'timestamp', 0, false, 'now()')
            ->field('media_upddt', 'timestamp', 0, false, 'now()')
            ->field('media_private', 'smallint', 0, false, 0)

            ->primary('pk_media', 'media_id')
        ;

        $struct->table(PostMediaInterface::POST_MEDIA_TABLE_NAME)
            ->field('media_id', 'bigint', 0, false)
            ->field('post_id', 'bigint', 0, false)
            ->field('link_type', 'varchar', 32, false, "'attachment'")

            ->primary('pk_post_media', 'media_id', 'post_id', 'link_type')
        ;

        $struct->table(LogInterface::LOG_TABLE_NAME)
            ->field('log_id', 'bigint', 0, false)
            ->field('user_id', 'varchar', 32, true)
            ->field('blog_id', 'varchar', 32, true)
            ->field('log_table', 'varchar', 255, false)
            ->field('log_dt', 'timestamp', 0, false, 'now()')
            ->field('log_ip', 'varchar', 39, false)
            ->field('log_msg', 'text', 0, true, null)

            ->primary('pk_log', 'log_id')
        ;

        $struct->table(VersionInterface::VERSION_TABLE_NAME)
            ->field('module', 'varchar', 64, false)
            ->field('version', 'varchar', 32, false)

            ->primary('pk_version', 'module')
        ;

        $struct->table(TrackbackInterface::PING_TABLE_NAME)
            ->field('post_id', 'bigint', 0, false)
            ->field('ping_url', 'varchar', 255, false)
            ->field('ping_dt', 'timestamp', 0, false, 'now()')

            ->primary('pk_ping', 'post_id', 'ping_url')
        ;

        $struct->table(BlogInterface::COMMENT_TABLE_NAME)
            ->field('comment_id', 'bigint', 0, false)
            ->field('post_id', 'bigint', 0, false)
            ->field('comment_dt', 'timestamp', 0, false, 'now()')
            ->field('comment_tz', 'varchar', 128, false, "'UTC'")
            ->field('comment_upddt', 'timestamp', 0, false, 'now()')
            ->field('comment_author', 'varchar', 255, true, null)
            ->field('comment_email', 'varchar', 255, true, null)
            ->field('comment_site', 'varchar', 255, true, null)
            ->field('comment_content', 'text', 0, true)
            ->field('comment_words', 'text', 0, true, null)
            ->field('comment_ip', 'varchar', 39, true, null)
            ->field('comment_status', 'smallint', 0, true, App::status()->comment()::UNPUBLISHED)
            ->field('comment_spam_status', 'varchar', 128, true, 0)
            ->field('comment_spam_filter', 'varchar', 32, true, null)
            ->field('comment_trackback', 'smallint', 0, false, 0)

            ->primary('pk_comment', 'comment_id')
        ;

        $struct->table(MetaInterface::META_TABLE_NAME)
            ->field('meta_id', 'varchar', 255, false)
            ->field('meta_type', 'varchar', 64, false)
            ->field('post_id', 'bigint', 0, false)

            ->primary('pk_meta', 'meta_id', 'meta_type', 'post_id')
        ;

        $struct->table(UserWorkspaceInterface::WS_TABLE_NAME)
            ->field('pref_id', 'varchar', 255, false)
            ->field('user_id', 'varchar', 32, true)
            ->field('pref_ws', 'varchar', 32, false, "'system'")
            ->field('pref_value', 'text', 0, true, null)
            ->field('pref_type', 'varchar', 8, false, "'string'")
            ->field('pref_label', 'text', 0, true)

            ->unique('uk_pref', 'pref_ws', 'pref_id', 'user_id')
        ;

        $struct->table(NoticeInterface::NOTICE_TABLE_NAME)
            ->field('notice_id', 'bigint', 0, false)
            ->field('ses_id', 'varchar', 40, false)
            ->field('notice_type', 'varchar', 32, true)
            ->field('notice_ts', 'timestamp', 0, false, 'now()')
            ->field('notice_msg', 'text', 0, true, null)
            ->field('notice_format', 'varchar', 32, true, "'text'")
            ->field('notice_options', 'text', 0, true, null)

            ->primary('pk_notice', 'notice_id')
        ;

        $struct->table(CredentialInterface::CREDENTIAL_TABLE_NAME)
            ->field('user_id', 'varchar', 32, false)
            ->field('blog_id', 'varchar', 32, true, null)
            ->field('credential_dt', 'timestamp', 0, false, 'now()')
            ->field('credential_type', 'varchar', 32, false)
            ->field('credential_value', 'varchar', 255, false, "''")
            ->field('credential_data', 'text', 0, true, null)

            ->unique('uk_credential', 'credential_type', 'user_id', 'blog_id', 'credential_value')
        ;

        /* References indexes
        -------------------------------------------------------- */
        $struct->table(CategoriesInterface::CATEGORY_TABLE_NAME)->index('idx_category_blog_id', 'btree', 'blog_id');
        $struct->table(CategoriesInterface::CATEGORY_TABLE_NAME)->index('idx_category_cat_lft_blog_id', 'btree', 'blog_id', 'cat_lft');
        $struct->table(CategoriesInterface::CATEGORY_TABLE_NAME)->index('idx_category_cat_rgt_blog_id', 'btree', 'blog_id', 'cat_rgt');
        $struct->table(BlogWorkspaceInterface::NS_TABLE_NAME)->index('idx_setting_blog_id', 'btree', 'blog_id');
        $struct->table(AuthInterface::USER_TABLE_NAME)->index('idx_user_user_default_blog', 'btree', 'user_default_blog');
        $struct->table(AuthInterface::PERMISSIONS_TABLE_NAME)->index('idx_permissions_blog_id', 'btree', 'blog_id');
        $struct->table(BlogInterface::POST_TABLE_NAME)->index('idx_post_cat_id', 'btree', 'cat_id');
        $struct->table(BlogInterface::POST_TABLE_NAME)->index('idx_post_user_id', 'btree', 'user_id');
        $struct->table(BlogInterface::POST_TABLE_NAME)->index('idx_post_blog_id', 'btree', 'blog_id');
        $struct->table(PostMediaInterface::MEDIA_TABLE_NAME)->index('idx_media_user_id', 'btree', 'user_id');
        $struct->table(PostMediaInterface::POST_MEDIA_TABLE_NAME)->index('idx_post_media_post_id', 'btree', 'post_id');
        $struct->table(PostMediaInterface::POST_MEDIA_TABLE_NAME)->index('idx_post_media_media_id', 'btree', 'media_id');
        $struct->table(LogInterface::LOG_TABLE_NAME)->index('idx_log_user_id', 'btree', 'user_id');
        $struct->table(BlogInterface::COMMENT_TABLE_NAME)->index('idx_comment_post_id', 'btree', 'post_id');
        $struct->table(MetaInterface::META_TABLE_NAME)->index('idx_meta_post_id', 'btree', 'post_id');
        $struct->table(MetaInterface::META_TABLE_NAME)->index('idx_meta_meta_type', 'btree', 'meta_type');
        $struct->table(UserWorkspaceInterface::WS_TABLE_NAME)->index('idx_pref_user_id', 'btree', 'user_id');
        $struct->table(CredentialInterface::CREDENTIAL_TABLE_NAME)->index('idx_credential_user_id', 'btree', 'user_id');
        $struct->table(CredentialInterface::CREDENTIAL_TABLE_NAME)->index('idx_credential_blog_id', 'btree', 'blog_id');

        /* Performance indexes
        -------------------------------------------------------- */
        $struct->table(BlogInterface::COMMENT_TABLE_NAME)->index('idx_comment_post_id_dt_status', 'btree', 'post_id', 'comment_dt', 'comment_status');
        $struct->table(BlogInterface::POST_TABLE_NAME)->index('idx_post_post_dt', 'btree', 'post_dt');
        $struct->table(BlogInterface::POST_TABLE_NAME)->index('idx_post_post_dt_post_id', 'btree', 'post_dt', 'post_id');
        $struct->table(BlogInterface::POST_TABLE_NAME)->index('idx_blog_post_post_dt_post_id', 'btree', 'blog_id', 'post_dt', 'post_id');
        $struct->table(BlogInterface::POST_TABLE_NAME)->index('idx_blog_post_post_status', 'btree', 'blog_id', 'post_status');
        $struct->table(BlogInterface::BLOG_TABLE_NAME)->index('idx_blog_blog_upddt', 'btree', 'blog_upddt');
        $struct->table(PostMediaInterface::MEDIA_TABLE_NAME)->index('idx_media_media_path', 'btree', 'media_path', 'media_dir', 'media_private');
        $struct->table(AuthInterface::USER_TABLE_NAME)->index('idx_user_user_super', 'btree', 'user_super');
        //$struct->table(CredentialInterface::CREDENTIAL_TABLE_NAME)->index('idx_credential_credential_type', 'btree', 'credential_type');

        /* Foreign keys
        -------------------------------------------------------- */
        $struct->table(CategoriesInterface::CATEGORY_TABLE_NAME)
            ->reference('fk_category_blog', 'blog_id', BlogInterface::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');

        $struct->table(BlogWorkspaceInterface::NS_TABLE_NAME)
            ->reference('fk_setting_blog', 'blog_id', BlogInterface::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');

        $struct->table(AuthInterface::USER_TABLE_NAME)
            ->reference('fk_user_default_blog', 'user_default_blog', BlogInterface::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'set null');
        $struct->table(AuthInterface::PERMISSIONS_TABLE_NAME)
            ->reference('fk_permissions_blog', 'blog_id', BlogInterface::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');
        $struct->table(AuthInterface::PERMISSIONS_TABLE_NAME)
            ->reference('fk_permissions_user', 'user_id', AuthInterface::USER_TABLE_NAME, 'user_id', 'cascade', 'cascade');

        $struct->table(BlogInterface::POST_TABLE_NAME)
            ->reference('fk_post_category', 'cat_id', CategoriesInterface::CATEGORY_TABLE_NAME, 'cat_id', 'cascade', 'set null');
        $struct->table(BlogInterface::POST_TABLE_NAME)
            ->reference('fk_post_user', 'user_id', AuthInterface::USER_TABLE_NAME, 'user_id', 'cascade', 'cascade');
        $struct->table(BlogInterface::POST_TABLE_NAME)
            ->reference('fk_post_blog', 'blog_id', BlogInterface::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');
        $struct->table(BlogInterface::COMMENT_TABLE_NAME)
            ->reference('fk_comment_post', 'post_id', BlogInterface::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');

        $struct->table(PostMediaInterface::MEDIA_TABLE_NAME)
            ->reference('fk_media_user', 'user_id', AuthInterface::USER_TABLE_NAME, 'user_id', 'cascade', 'cascade');
        $struct->table(PostMediaInterface::POST_MEDIA_TABLE_NAME)
            ->reference('fk_media', 'media_id', PostMediaInterface::MEDIA_TABLE_NAME, 'media_id', 'cascade', 'cascade');
        $struct->table(PostMediaInterface::POST_MEDIA_TABLE_NAME)
            ->reference('fk_media_post', 'post_id', BlogInterface::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');

        $struct->table(TrackbackInterface::PING_TABLE_NAME)
            ->reference('fk_ping_post', 'post_id', BlogInterface::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');

        $struct->table(LogInterface::LOG_TABLE_NAME)
            ->reference('fk_log_blog', 'blog_id', BlogInterface::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'set null');

        $struct->table(MetaInterface::META_TABLE_NAME)
            ->reference('fk_meta_post', 'post_id', BlogInterface::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');

        $struct->table(UserWorkspaceInterface::WS_TABLE_NAME)
            ->reference('fk_pref_user', 'user_id', AuthInterface::USER_TABLE_NAME, 'user_id', 'cascade', 'cascade');

        $struct->table(NoticeInterface::NOTICE_TABLE_NAME)
            ->reference('fk_notice_session', 'ses_id', SessionInterface::SESSION_TABLE_NAME, 'ses_id', 'cascade', 'cascade');

        $struct->table(CredentialInterface::CREDENTIAL_TABLE_NAME)
            ->reference('fk_credential_user', 'user_id', AuthInterface::USER_TABLE_NAME, 'user_id', 'cascade', 'cascade');
        $struct->table(CredentialInterface::CREDENTIAL_TABLE_NAME)
            ->reference('fk_credential_blog', 'blog_id', BlogInterface::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');

        /* PostgreSQL specific indexes
        -------------------------------------------------------- */
        if ($struct->driver() === 'pgsql') {
            $struct->table(BlogWorkspaceInterface::NS_TABLE_NAME)->index('idx_setting_blog_id_null', 'btree', '(blog_id IS NULL)');
            $struct->table(UserWorkspaceInterface::WS_TABLE_NAME)->index('idx_pref_user_id_null', 'btree', '(user_id IS NULL)');
        }
    }
}
