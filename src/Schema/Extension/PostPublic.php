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
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
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

            $post_title = is_string($post_title = $rs->post_title) ? $post_title : '';
            $post_url   = is_string($post_url = $rs->getURL()) ? $post_url : '';

            return (new Para())
                ->items([
                    (new Text(null, $content . '... ')),
                    (new Link())
                        ->href($post_url)
                        ->separator(' ')
                        ->items([
                            (new Text('em', __('Read'))),
                            (new Text(null, Html::escapeHTML($post_title))),
                        ]),
                ])
            ->render();
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
        if (!isset(App::frontend()->smilies)) {
            $smilies = Ctx::getSmilies($blog);
            if ($smilies !== false) {
                App::frontend()->smilies = $smilies;
            }
        }

        return Ctx::addSmilies($content);
    }
}
