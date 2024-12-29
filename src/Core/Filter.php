<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use dcCore;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\HtmlFilter;
use Dotclear\Helper\Html\WikiToHtml;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\FilterInterface;

/**
 * @brief   Wiki and HTML filter handler.
 *
 * @since   2.28, wiki and HTML filters features have been grouped in this class
 */
class Filter implements FilterInterface
{
    /**
     * The working blog instance
     */
    private ?BlogInterface $blog = null;

    /**
     * The wiki instance.
     */
    private ?WikiToHtml $wiki = null;

    /**
     * Get wiki instance.
     *
     * @return  null|WikiToHtml Instance or null if not set
     */
    public function wiki(): ?WikiToHtml
    {
        return $this->wiki;
    }

    /**
     * Constructor.
     *
     * @param   BehaviorInterface   $behavior   The behavior instance
     */
    public function __construct(
        protected BehaviorInterface $behavior,
    ) {
    }

    public function loadFromBlog(BlogInterface $blog): FilterInterface
    {
        $this->blog = $blog;

        return $this;
    }

    /**
     * Initializes the WikiToHtml methods.
     */
    private function initWiki(): void
    {
        $this->wiki = new WikiToHtml();

        // deprecated since 2.27, use App::filter()->wiki instead
        dcCore::app()->wiki = $this->wiki;

        // deprecated since 2.27, use App::filter()->wiki instead
        dcCore::app()->wiki2xhtml = $this->wiki;
    }

    public function wikiTransform(string $str): string
    {
        if (!$this->wiki instanceof WikiToHtml) {
            $this->initWiki();
        }

        return $this->wiki instanceof WikiToHtml ? $this->wiki->transform($str) : $str;
    }

    public function initWikiPost(): void
    {
        $this->initWiki();

        if (!$this->wiki instanceof WikiToHtml) {
            return;
        }

        $this->wiki->setOpts([
            'active_title'        => 1,
            'active_setext_title' => 0,
            'active_hr'           => 1,
            'active_lists'        => 1,
            'active_defl'         => 1,
            'active_quote'        => 1,
            'active_pre'          => 1,
            'active_empty'        => 1,
            'active_auto_urls'    => 0,
            'active_auto_br'      => 0,
            'active_antispam'     => 1,
            'active_urls'         => 1,
            'active_auto_img'     => 0,
            'active_img'          => 1,
            'active_anchor'       => 1,
            'active_em'           => 1,
            'active_strong'       => 1,
            'active_br'           => 1,
            'active_q'            => 1,
            'active_code'         => 1,
            'active_acronym'      => 1,
            'active_ins'          => 1,
            'active_del'          => 1,
            'active_footnotes'    => 1,
            'active_wikiwords'    => 0,
            'active_macros'       => 1,
            'active_mark'         => 1,
            'active_aside'        => 1,
            'active_sup'          => 1,
            'active_sub'          => 1,
            'active_i'            => 1,
            'active_span'         => 1,
            'parse_pre'           => 1,
            'active_fr_syntax'    => 0,
            'first_title_level'   => 3,
            'note_prefix'         => 'wiki-footnote',
            'note_str'            => '<div class="footnotes"><h4>Notes</h4>%s</div>',
            'img_style_left'      => 'class="media-left"',
            'img_style_center'    => 'class="media-center"',
            'img_style_right'     => 'class="media-right"',
        ]);

        $this->wiki->registerFunction('url:post', $this->wikiPostLink(...));

        # --BEHAVIOR-- coreWikiPostInit -- WikiToHtml
        $this->behavior->callBehavior('coreInitWikiPost', $this->wiki);
    }

    public function initWikiSimpleComment(): void
    {
        $this->initWiki();

        if (!$this->wiki instanceof WikiToHtml) {
            return;
        }

        $this->wiki->setOpts([
            'active_title'        => 0,
            'active_setext_title' => 0,
            'active_hr'           => 0,
            'active_lists'        => 0,
            'active_defl'         => 0,
            'active_quote'        => 0,
            'active_pre'          => 0,
            'active_empty'        => 0,
            'active_auto_urls'    => 1,
            'active_auto_br'      => 1,
            'active_antispam'     => 1,
            'active_urls'         => 0,
            'active_auto_img'     => 0,
            'active_img'          => 0,
            'active_anchor'       => 0,
            'active_em'           => 0,
            'active_strong'       => 0,
            'active_br'           => 0,
            'active_q'            => 0,
            'active_code'         => 0,
            'active_acronym'      => 0,
            'active_ins'          => 0,
            'active_del'          => 0,
            'active_inline_html'  => 0,
            'active_footnotes'    => 0,
            'active_wikiwords'    => 0,
            'active_macros'       => 0,
            'active_mark'         => 0,
            'active_aside'        => 0,
            'active_sup'          => 0,
            'active_sub'          => 0,
            'active_i'            => 0,
            'active_span'         => 0,
            'parse_pre'           => 0,
            'active_fr_syntax'    => 0,
        ]);

        # --BEHAVIOR-- coreInitWikiSimpleComment -- WikiToHtml
        $this->behavior->callBehavior('coreInitWikiSimpleComment', $this->wiki);
    }

    public function initWikiComment(): void
    {
        $this->initWiki();

        if (!$this->wiki instanceof WikiToHtml) {
            return;
        }

        $this->wiki->setOpts([
            'active_title'        => 0,
            'active_setext_title' => 0,
            'active_hr'           => 0,
            'active_lists'        => 1,
            'active_defl'         => 0,
            'active_quote'        => 1,
            'active_pre'          => 1,
            'active_empty'        => 0,
            'active_auto_br'      => 1,
            'active_auto_urls'    => 1,
            'active_urls'         => 1,
            'active_auto_img'     => 0,
            'active_img'          => 0,
            'active_anchor'       => 0,
            'active_em'           => 1,
            'active_strong'       => 1,
            'active_br'           => 1,
            'active_q'            => 1,
            'active_code'         => 1,
            'active_acronym'      => 1,
            'active_ins'          => 1,
            'active_del'          => 1,
            'active_footnotes'    => 0,
            'active_inline_html'  => 0,
            'active_wikiwords'    => 0,
            'active_macros'       => 0,
            'active_mark'         => 1,
            'active_aside'        => 0,
            'active_sup'          => 1,
            'active_sub'          => 1,
            'active_i'            => 1,
            'active_span'         => 0,
            'parse_pre'           => 0,
            'active_fr_syntax'    => 0,
        ]);

        # --BEHAVIOR-- coreInitWikiComment -- WikiToHtml
        $this->behavior->callBehavior('coreInitWikiComment', $this->wiki);
    }

    public function wikiPostLink(string $url, string $content): array
    {
        if (!$this->blog?->isDefined()) {
            return [];
        }

        $post_id = abs((int) substr($url, 5));
        if ($post_id === 0) {
            return [];
        }

        $post = $this->blog->getPosts(['post_id' => $post_id]);
        if ($post->isEmpty()) {
            return [];
        }

        $res = ['url' => $post->getURL()];

        if ($content !== $url) {
            $res['title'] = Html::escapeHTML($post->post_title);
        }

        if ($content === '' || $content === $url) {
            $res['content'] = Html::escapeHTML($post->post_title);
        }

        if ($post->post_lang) {
            $res['lang'] = (string) $post->post_lang;
        }

        return $res;
    }

    public function HTMLfilter(string $str): string
    {
        if (!$this->blog?->isDefined() || !$this->blog->settings()->system->enable_html_filter) {
            return $str;
        }

        $options = new ArrayObject([
            'keep_aria' => false,
            'keep_data' => false,
            'keep_js'   => false,
        ]);
        # --BEHAVIOR-- HTMLfilter -- ArrayObject
        $this->behavior->callBehavior('HTMLfilter', $options);

        $filter = new HtmlFilter((bool) $options['keep_aria'], (bool) $options['keep_data'], (bool) $options['keep_js']);

        return trim($filter->apply($str));
    }
}
