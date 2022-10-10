<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class rsExtendPublic
{
    /**
     * Initializes the object.
     */
    public static function init()
    {
        dcCore::app()->addBehavior('publicHeadContent', [self::class, 'publicHeadContent']);
        dcCore::app()->addBehavior('coreBlogGetPosts', [self::class, 'coreBlogGetPosts']);
        dcCore::app()->addBehavior('coreBlogGetComments', [self::class, 'coreBlogGetComments']);
    }

    /**
     * Add smilies.css in head if necessary
     */
    public static function publicHeadContent()
    {
        if (dcCore::app()->blog->settings->system->use_smilies) {
            echo dcUtils::cssLoad(dcCore::app()->blog->getQmarkURL() . 'pf=smilies.css');
        }
    }

    /**
     * Extend Posts recordset methods
     *
     * @param      dcRecord  $rs     Posts recordset
     */
    public static function coreBlogGetPosts(dcRecord $rs)
    {
        $rs->extend('rsExtPostPublic');
    }

    /**
     * Extend Comments recordset methods
     *
     * @param      dcRecord  $rs     Comments recordset
     */
    public static function coreBlogGetComments(dcRecord $rs)
    {
        $rs->extend('rsExtCommentPublic');
    }
}

class rsExtPostPublic extends rsExtPost
{
    /**
     * Gets the post's content.
     *
     * Return content cut to 350 characters in short feed context
     * Replace textual smilies by their image representation if requested
     *
     * @param      dcRecord  $rs     Invisible parameter
     * @param      bool      $absolute_urls  Use absolute urls
     *
     * @return     string  The content.
     */
    public static function getContent(dcRecord $rs, bool $absolute_urls = false): string
    {
        // Not very nice hack but it does the job :)
        if (isset(dcCore::app()->ctx) && dcCore::app()->ctx->short_feed_items === true) {
            $content = parent::getContent($rs, $absolute_urls);
            $content = context::remove_html($content);
            $content = context::cut_string($content, 350);

            $content = '<p>' . $content . '... ' .
            '<a href="' . $rs->getURL() . '"><em>' . __('Read') . '</em> ' .
            html::escapeHTML($rs->post_title) . '</a></p>';

            return $content;
        }

        if (dcCore::app()->blog->settings->system->use_smilies) {
            return self::smilies(parent::getContent($rs, $absolute_urls), dcCore::app()->blog);
        }

        return parent::getContent($rs, $absolute_urls);
    }

    /**
     * Gets the post's excerpt.
     *
     * Replace textual smilies by their image representation if requested
     *
     * @param      dcRecord  $rs             Invisible parameter
     * @param      bool      $absolute_urls  Use absolute urls
     *
     * @return     string  The excerpt.
     */
    public static function getExcerpt(dcRecord $rs, bool $absolute_urls = false): string
    {
        if (dcCore::app()->blog->settings->system->use_smilies) {
            return self::smilies(parent::getExcerpt($rs, $absolute_urls), dcCore::app()->blog);
        }

        return parent::getExcerpt($rs, $absolute_urls);
    }

    /**
     * Cope with smileys in content
     *
     * @param      string  $content  The content
     * @param      dcBlog  $blog     The blog
     *
     * @return     string
     */
    protected static function smilies(string $content, dcBlog $blog): string
    {
        if (!isset(dcCore::app()->public->smilies)) {
            dcCore::app()->public->smilies = context::getSmilies($blog);
        }

        return context::addSmilies($content);
    }
}

class rsExtCommentPublic extends rsExtComment
{
    /**
     * Gets the comment's content.
     *
     * Replace textual smilies by their image representation if requested
     *
     * @param      dcRecord  $rs             Invisible parameter
     * @param      bool      $absolute_urls  Use absolute urls
     *
     * @return     string  The content.
     */
    public static function getContent(dcRecord $rs, bool $absolute_urls = false): string
    {
        if (dcCore::app()->blog->settings->system->use_smilies) {
            $content = parent::getContent($rs, $absolute_urls);

            if (!isset(dcCore::app()->public->smilies)) {
                dcCore::app()->public->smilies = context::getSmilies(dcCore::app()->blog);
            }

            return context::addSmilies($content);
        }

        return parent::getContent($rs, $absolute_urls);
    }
}
