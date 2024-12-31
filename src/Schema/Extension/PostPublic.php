<?php

/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Extension;

use Dotclear\App;
use Dotclear\Core\Frontend\Ctx;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\BlogInterface;

/**
 * @brief Dotclear post record helpers
 *
 * This class adds new methods to database post results.
 * You can call them on every record comming from Blog::getPosts and similar
 * methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class PostPublic extends Post
{
    /**
     * Gets the post's content.
     *
     * Return content cut to 350 characters in short feed context
     * Replace textual smilies by their image representation if requested
     *
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  Use absolute urls
     */
    public static function getContent(MetaRecord $rs, $absolute_urls = false): string
    {
        // Not very nice hack but it does the job :)
        if (App::task()->checkContext('FRONTEND') && App::frontend()->context()->short_feed_items === true) {
            $content = parent::getContent($rs, $absolute_urls);
            $content = Ctx::remove_html($content);
            $content = Ctx::cut_string($content, 350);

            return '<p>' . $content . '... ' .
            '<a href="' . $rs->getURL() . '"><em>' . __('Read') . '</em> ' .
            Html::escapeHTML($rs->post_title) . '</a></p>';
        }

        if (App::blog()->settings()->system->use_smilies) {
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
     */
    public static function getExcerpt(MetaRecord $rs, $absolute_urls = false): string
    {
        if (App::blog()->settings()->system->use_smilies) {
            return self::smilies(parent::getExcerpt($rs, $absolute_urls), App::blog());
        }

        return parent::getExcerpt($rs, $absolute_urls);
    }

    /**
     * Cope with smileys in content
     *
     * @param      string         $content  The content
     * @param      BlogInterface  $blog     The blog
     */
    protected static function smilies(string $content, BlogInterface $blog): string
    {
        if (App::frontend()->smilies === null) {
            App::frontend()->smilies = Ctx::getSmilies($blog);
        }

        return Ctx::addSmilies($content);
    }
}
