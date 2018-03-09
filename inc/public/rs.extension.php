<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('coreBlogGetPosts', array('rsExtendPublic', 'coreBlogGetPosts'));
$core->addBehavior('coreBlogGetComments', array('rsExtendPublic', 'coreBlogGetComments'));

class rsExtendPublic
{
    public static function coreBlogGetPosts($rs)
    {
        $rs->extend('rsExtPostPublic');
    }

    public static function coreBlogGetComments($rs)
    {
        $rs->extend('rsExtCommentPublic');
    }
}

class rsExtPostPublic extends rsExtPost
{
    public static function getContent($rs, $absolute_urls = false)
    {
        # Not very nice hack but it does the job :)
        if (isset($GLOBALS['_ctx']) && $GLOBALS['_ctx']->short_feed_items === true) {
            $_ctx = &$GLOBALS['_ctx'];
            $c    = parent::getContent($rs, $absolute_urls);
            $c    = context::remove_html($c);
            $c    = context::cut_string($c, 350);

            $c =
            '<p>' . $c . '... ' .
            '<a href="' . $rs->getURL() . '"><em>' . __('Read') . '</em> ' .
            html::escapeHTML($rs->post_title) . '</a></p>';

            return $c;
        }

        if ($rs->core->blog->settings->system->use_smilies) {
            return self::smilies(parent::getContent($rs, $absolute_urls), $rs->core->blog);
        }

        return parent::getContent($rs, $absolute_urls);
    }

    public static function getExcerpt($rs, $absolute_urls = false)
    {
        if ($rs->core->blog->settings->system->use_smilies) {
            return self::smilies(parent::getExcerpt($rs, $absolute_urls), $rs->core->blog);
        }

        return parent::getExcerpt($rs, $absolute_urls);
    }

    protected static function smilies($c, $blog)
    {
        if (!isset($GLOBALS['__smilies'])) {
            $GLOBALS['__smilies'] = context::getSmilies($blog);
        }
        return context::addSmilies($c);
    }
}

class rsExtCommentPublic extends rsExtComment
{
    public static function getContent($rs, $absolute_urls = false)
    {
        if ($rs->core->blog->settings->system->use_smilies) {
            $c = parent::getContent($rs, $absolute_urls);

            if (!isset($GLOBALS['__smilies'])) {
                $GLOBALS['__smilies'] = context::getSmilies($rs->core->blog);
            }
            return context::addSmilies($c);
        }

        return parent::getContent($rs, $absolute_urls);
    }
}
