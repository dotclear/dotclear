<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;

class rsExtendPublic
{
    /**
     * Initializes the object.
     */
    public static function init()
    {
        dcCore::app()->behavior->addBehaviors([
            'publicHeadContent'   => [self::class, 'publicHeadContent'],
            'coreBlogGetPosts'    => [self::class, 'coreBlogGetPosts'],
            'coreBlogGetComments' => [self::class, 'coreBlogGetComments'],
        ]);
    }

    /**
     * Add smilies.css in head if necessary
     */
    public static function publicHeadContent()
    {
        if (!dcCore::app()->blog->settings->system->no_public_css) {
            echo dcUtils::cssLoad(dcCore::app()->blog->getQmarkURL() . 'pf=public.css');
        }
        if (dcCore::app()->blog->settings->system->use_smilies) {
            echo dcUtils::cssLoad(dcCore::app()->blog->getQmarkURL() . 'pf=smilies.css');
        }
    }

    /**
     * Extend Posts recordset methods
     *
     * @param      MetaRecord  $rs     Posts recordset
     */
    public static function coreBlogGetPosts(MetaRecord $rs)
    {
        $rs->extend('rsExtPostPublic');
    }

    /**
     * Extend Comments recordset methods
     *
     * @param      MetaRecord  $rs     Comments recordset
     */
    public static function coreBlogGetComments(MetaRecord $rs)
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
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  Use absolute urls
     *
     * @return     string  The content.
     */
    public static function getContent(MetaRecord $rs, $absolute_urls = false): string
    {
        // Not very nice hack but it does the job :)
        if (isset(dcCore::app()->ctx) && dcCore::app()->ctx->short_feed_items === true) {
            $content = parent::getContent($rs, $absolute_urls);
            $content = context::remove_html($content);
            $content = context::cut_string($content, 350);

            $content = '<p>' . $content . '... ' .
            '<a href="' . $rs->getURL() . '"><em>' . __('Read') . '</em> ' .
            Html::escapeHTML($rs->post_title) . '</a></p>';

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
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  Use absolute urls
     *
     * @return     string  The excerpt.
     */
    public static function getExcerpt(MetaRecord $rs, $absolute_urls = false): string
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
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  Use absolute urls
     *
     * @return     string  The content.
     */
    public static function getContent(MetaRecord $rs, $absolute_urls = false): string
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
