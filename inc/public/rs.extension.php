<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;

class rsExtendPublic
{
    /**
     * Initializes the object.
     */
    public static function init()
    {
        App::behavior()->addBehaviors([
            'publicHeadContent'   => self::publicHeadContent(...),
            'coreBlogGetPosts'    => self::coreBlogGetPosts(...),
            'coreBlogGetComments' => self::coreBlogGetComments(...),
        ]);
    }

    /**
     * Add smilies.css in head if necessary
     */
    public static function publicHeadContent()
    {
        if (!App::blog()->settings->system->no_public_css) {
            echo dcUtils::cssLoad(App::blog()->getQmarkURL() . 'pf=public.css');
        }
        if (App::blog()->settings->system->use_smilies) {
            echo dcUtils::cssLoad(App::blog()->getQmarkURL() . 'pf=smilies.css');
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
        if (isset(App::frontend()->ctx) && App::frontend()->ctx->short_feed_items === true) {
            $content = parent::getContent($rs, $absolute_urls);
            $content = context::remove_html($content);
            $content = context::cut_string($content, 350);

            $content = '<p>' . $content . '... ' .
            '<a href="' . $rs->getURL() . '"><em>' . __('Read') . '</em> ' .
            Html::escapeHTML($rs->post_title) . '</a></p>';

            return $content;
        }

        if (App::blog()->settings->system->use_smilies) {
            return self::smilies(parent::getContent($rs, $absolute_urls), App::blog());
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
        if (App::blog()->settings->system->use_smilies) {
            return self::smilies(parent::getExcerpt($rs, $absolute_urls), App::blog());
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
        if (!isset(App::frontend()->smilies)) {
            App::frontend()->smilies = context::getSmilies($blog);
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
        if (App::blog()->settings->system->use_smilies) {
            $content = parent::getContent($rs, $absolute_urls);

            if (!isset(App::frontend()->smilies)) {
                App::frontend()->smilies = context::getSmilies(App::blog());
            }

            return context::addSmilies($content);
        }

        return parent::getContent($rs, $absolute_urls);
    }
}
