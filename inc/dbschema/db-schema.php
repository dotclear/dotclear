<?php
/**
 * @brief Dotclear database schema
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

if (!($_s instanceof dbStruct)) {
    throw new Exception('No valid schema object');
}

/* Tables
-------------------------------------------------------- */
$_s->blog
    ->blog_id('varchar', 32, false)
    ->blog_uid('varchar', 32, false)
    ->blog_creadt('timestamp', 0, false, 'now()')
    ->blog_upddt('timestamp', 0, false, 'now()')
    ->blog_url('varchar', 255, false)
    ->blog_name('varchar', 255, false)
    ->blog_desc('text', 0, true)
    ->blog_status('smallint', 0, false, 1)

    ->primary('pk_blog', 'blog_id')
;

$_s->category
    ->cat_id('bigint', 0, false)
    ->blog_id('varchar', 32, false)
    ->cat_title('varchar', 255, false)
    ->cat_url('varchar', 255, false)
    ->cat_desc('text', 0, true)
    ->cat_position('integer', 0, true, 0)
    ->cat_lft('integer', 0, true)
    ->cat_rgt('integer', 0, true)

    ->primary('pk_category', 'cat_id')

    ->unique('uk_cat_url', 'cat_url', 'blog_id')
;

$_s->session
    ->ses_id('varchar', 40, false)
    ->ses_time('integer', 0, false, 0)
    ->ses_start('integer', 0, false, 0)
    ->ses_value('text', 0, false)

    ->primary('pk_session', 'ses_id')
;

$_s->setting
    ->setting_id('varchar', 255, false)
    ->blog_id('varchar', 32, true)
    ->setting_ns('varchar', 32, false, "'system'")
    ->setting_value('text', 0, true, null)
    ->setting_type('varchar', 8, false, "'string'")
    ->setting_label('text', 0, true)

    ->unique('uk_setting', 'setting_ns', 'setting_id', 'blog_id')
;

$_s->user
    ->user_id('varchar', 32, false)
    ->user_super('smallint', 0, true)
    ->user_status('smallint', 0, false, 1)
    ->user_pwd('varchar', 255, false)
    ->user_change_pwd('smallint', 0, false, 0)
    ->user_recover_key('varchar', 32, true, null)
    ->user_name('varchar', 255, true, null)
    ->user_firstname('varchar', 255, true, null)
    ->user_displayname('varchar', 255, true, null)
    ->user_email('varchar', 255, true, null)
    ->user_url('varchar', 255, true, null)
    ->user_desc('text', 0, true)
    ->user_default_blog('varchar', 32, true, null)
    ->user_options('text', 0, true)
    ->user_lang('varchar', 5, true, null)
    ->user_tz('varchar', 128, false, "'UTC'")
    ->user_post_status('smallint', 0, false, -2)
    ->user_creadt('timestamp', 0, false, 'now()')
    ->user_upddt('timestamp', 0, false, 'now()')

    ->primary('pk_user', 'user_id')
;

$_s->permissions
    ->user_id('varchar', 32, false)
    ->blog_id('varchar', 32, false)
    ->permissions('text', 0, true)

    ->primary('pk_permissions', 'user_id', 'blog_id')
;

$_s->post
    ->post_id('bigint', 0, false)
    ->blog_id('varchar', 32, false)
    ->user_id('varchar', 32, false)
    ->cat_id('bigint', 0, true)
    ->post_dt('timestamp', 0, false, 'now()')
    ->post_tz('varchar', 128, false, "'UTC'")
    ->post_creadt('timestamp', 0, false, 'now()')
    ->post_upddt('timestamp', 0, false, 'now()')
    ->post_password('varchar', 32, true, null)
    ->post_type('varchar', 32, false, "'post'")
    ->post_format('varchar', 32, false, "'xhtml'")
    ->post_url('varchar', 255, false)
    ->post_lang('varchar', 5, true, null)
    ->post_title('varchar', 255, true, null)
    ->post_excerpt('text', 0, true, null)
    ->post_excerpt_xhtml('text', 0, true, null)
    ->post_content('text', 0, true, null)
    ->post_content_xhtml('text', 0, false)
    ->post_notes('text', 0, true, null)
    ->post_meta('text', 0, true, null)
    ->post_words('text', 0, true, null)
    ->post_status('smallint', 0, false, 0)
    ->post_firstpub('smallint', 0, false, 0)
    ->post_selected('smallint', 0, false, 0)
    ->post_position('integer', 0, false, 0)
    ->post_open_comment('smallint', 0, false, 0)
    ->post_open_tb('smallint', 0, false, 0)
    ->nb_comment('integer', 0, false, 0)
    ->nb_trackback('integer', 0, false, 0)

    ->primary('pk_post', 'post_id')

    ->unique('uk_post_url', 'post_url', 'post_type', 'blog_id')
;

$_s->media
    ->media_id('bigint', 0, false)
    ->user_id('varchar', 32, false)
    ->media_path('varchar', 255, false)
    ->media_title('varchar', 255, false)
    ->media_file('varchar', 255, false)
    ->media_dir('varchar', 255, false, "'.'")
    ->media_meta('text', 0, true, null)
    ->media_dt('timestamp', 0, false, 'now()')
    ->media_creadt('timestamp', 0, false, 'now()')
    ->media_upddt('timestamp', 0, false, 'now()')
    ->media_private('smallint', 0, false, 0)

    ->primary('pk_media', 'media_id')
;

$_s->post_media
    ->media_id('bigint', 0, false)
    ->post_id('bigint', 0, false)
    ->link_type('varchar', 32, false, "'attachment'")

    ->primary('pk_post_media', 'media_id', 'post_id', 'link_type')
;

$_s->log
    ->log_id('bigint', 0, false)
    ->user_id('varchar', 32, true)
    ->blog_id('varchar', 32, true)
    ->log_table('varchar', 255, false)
    ->log_dt('timestamp', 0, false, 'now()')
    ->log_ip('varchar', 39, false)
    ->log_msg('text', 0, true, null)

    ->primary('pk_log', 'log_id')
;

$_s->version
    ->module('varchar', 64, false)
    ->version('varchar', 32, false)

    ->primary('pk_version', 'module')
;

$_s->ping
    ->post_id('bigint', 0, false)
    ->ping_url('varchar', 255, false)
    ->ping_dt('timestamp', 0, false, 'now()')

    ->primary('pk_ping', 'post_id', 'ping_url')
;

$_s->comment
    ->comment_id('bigint', 0, false)
    ->post_id('bigint', 0, false)
    ->comment_dt('timestamp', 0, false, 'now()')
    ->comment_tz('varchar', 128, false, "'UTC'")
    ->comment_upddt('timestamp', 0, false, 'now()')
    ->comment_author('varchar', 255, true, null)
    ->comment_email('varchar', 255, true, null)
    ->comment_site('varchar', 255, true, null)
    ->comment_content('text', 0, true)
    ->comment_words('text', 0, true, null)
    ->comment_ip('varchar', 39, true, null)
    ->comment_status('smallint', 0, true, 0)
    ->comment_spam_status('varchar', 128, true, 0)
    ->comment_spam_filter('varchar', 32, true, null)
    ->comment_trackback('smallint', 0, false, 0)

    ->primary('pk_comment', 'comment_id')
;

$_s->meta
    ->meta_id('varchar', 255, false)
    ->meta_type('varchar', 64, false)
    ->post_id('bigint', 0, false)

    ->primary('pk_meta', 'meta_id', 'meta_type', 'post_id')
;

$_s->pref
    ->pref_id('varchar', 255, false)
    ->user_id('varchar', 32, true)
    ->pref_ws('varchar', 32, false, "'system'")
    ->pref_value('text', 0, true, null)
    ->pref_type('varchar', 8, false, "'string'")
    ->pref_label('text', 0, true)

    ->unique('uk_pref', 'pref_ws', 'pref_id', 'user_id')
;

/* References indexes
-------------------------------------------------------- */
$_s->category->index('idx_category_blog_id', 'btree', 'blog_id');
$_s->category->index('idx_category_cat_lft_blog_id', 'btree', 'blog_id', 'cat_lft');
$_s->category->index('idx_category_cat_rgt_blog_id', 'btree', 'blog_id', 'cat_rgt');
$_s->setting->index('idx_setting_blog_id', 'btree', 'blog_id');
$_s->user->index('idx_user_user_default_blog', 'btree', 'user_default_blog');
$_s->permissions->index('idx_permissions_blog_id', 'btree', 'blog_id');
$_s->post->index('idx_post_cat_id', 'btree', 'cat_id');
$_s->post->index('idx_post_user_id', 'btree', 'user_id');
$_s->post->index('idx_post_blog_id', 'btree', 'blog_id');
$_s->media->index('idx_media_user_id', 'btree', 'user_id');
$_s->post_media->index('idx_post_media_post_id', 'btree', 'post_id');
$_s->post_media->index('idx_post_media_media_id', 'btree', 'media_id');
$_s->log->index('idx_log_user_id', 'btree', 'user_id');
$_s->comment->index('idx_comment_post_id', 'btree', 'post_id');
$_s->meta->index('idx_meta_post_id', 'btree', 'post_id');
$_s->meta->index('idx_meta_meta_type', 'btree', 'meta_type');
$_s->pref->index('idx_pref_user_id', 'btree', 'user_id');

/* Performance indexes
-------------------------------------------------------- */
$_s->comment->index('idx_comment_post_id_dt_status', 'btree', 'post_id', 'comment_dt', 'comment_status');
$_s->post->index('idx_post_post_dt', 'btree', 'post_dt');
$_s->post->index('idx_post_post_dt_post_id', 'btree', 'post_dt', 'post_id');
$_s->post->index('idx_blog_post_post_dt_post_id', 'btree', 'blog_id', 'post_dt', 'post_id');
$_s->post->index('idx_blog_post_post_status', 'btree', 'blog_id', 'post_status');
$_s->blog->index('idx_blog_blog_upddt', 'btree', 'blog_upddt');
$_s->user->index('idx_user_user_super', 'btree', 'user_super');

/* Foreign keys
-------------------------------------------------------- */
$_s->category->reference('fk_category_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
$_s->setting->reference('fk_setting_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
$_s->user->reference('fk_user_default_blog', 'user_default_blog', 'blog', 'blog_id', 'cascade', 'set null');
$_s->permissions->reference('fk_permissions_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
$_s->permissions->reference('fk_permissions_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');
$_s->post->reference('fk_post_category', 'cat_id', 'category', 'cat_id', 'cascade', 'set null');
$_s->post->reference('fk_post_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');
$_s->post->reference('fk_post_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade');
$_s->media->reference('fk_media_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');
$_s->post_media->reference('fk_media', 'media_id', 'media', 'media_id', 'cascade', 'cascade');
$_s->post_media->reference('fk_media_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
$_s->ping->reference('fk_ping_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
$_s->comment->reference('fk_comment_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
$_s->log->reference('fk_log_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'set null');
$_s->meta->reference('fk_meta_post', 'post_id', 'post', 'post_id', 'cascade', 'cascade');
$_s->pref->reference('fk_pref_user', 'user_id', 'user', 'user_id', 'cascade', 'cascade');

/* PostgreSQL specific indexes
-------------------------------------------------------- */
if ($_s->driver() == 'pgsql') {
    $_s->setting->index('idx_setting_blog_id_null', 'btree', '(blog_id IS NULL)');
    $_s->media->index('idx_media_media_path', 'btree', 'media_path', 'media_dir');
    $_s->pref->index('idx_pref_user_id_null', 'btree', '(user_id IS NULL)');
}
