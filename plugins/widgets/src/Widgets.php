<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use dcCore;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Feed\Reader;
use Exception;

/**
 * @brief   The widgets handler.
 * @ingroup widgets
 */
class Widgets
{
    /**
     * Navigation widgets group.
     *
     * @var     string  WIDGETS_NAV
     */
    public const WIDGETS_NAV = 'nav';

    /**
     * extra widgets group.
     *
     * @var     string  WIDGETS_EXTRA
     */
    public const WIDGETS_EXTRA = 'extra';

    /**
     * custom widgets group.
     *
     * @var     string  WIDGETS_CUSTOM
     */
    public const WIDGETS_CUSTOM = 'custom';

    /**
     * The current widgets stack.
     */
    public static WidgetsStack $widgets;

    /**
     * The default widgets stack.
     *
     * @var     array<string, WidgetsStack>  $default_widgets
     */
    public static array $default_widgets;

    private const WIDGET_ID_SEARCH       = 'search';
    private const WIDGET_ID_NAVIGATION   = 'navigation';
    private const WIDGET_ID_BESTOF       = 'bestof';
    private const WIDGET_ID_LANGS        = 'langs';
    private const WIDGET_ID_CATEGORIES   = 'categories';
    private const WIDGET_ID_SUBSCRIBE    = 'subscribe';
    private const WIDGET_ID_FEED         = 'feed';
    private const WIDGET_ID_TEXT         = 'text';
    private const WIDGET_ID_LASTPOSTS    = 'lastposts';
    private const WIDGET_ID_LASTCOMMENTS = 'lastcomments';

    /**
     * Initializes the default widgets.
     */
    public static function init(): void
    {
        // Available widgets
        self::$widgets = new WidgetsStack();

        // deprecated since 2.28, use Widgets::$widgets instead
        dcCore::app()->widgets = self::$widgets;

        // deprecated since 2.23, use Widgets::$widgets instead
        $GLOBALS['__widgets'] = self::$widgets;

        self::$widgets
            ->create(self::WIDGET_ID_SEARCH, __('Search engine'), Widgets::search(...), null, 'Search engine form')
            ->addTitle(__('Search'))
            ->setting('placeholder', __('Placeholder (HTML5 only, optional):'), '')
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        self::$widgets
            ->create(self::WIDGET_ID_NAVIGATION, __('Navigation links'), Widgets::navigation(...), null, 'List of navigation links')
            ->addTitle()
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        self::$widgets
            ->create(self::WIDGET_ID_BESTOF, __('Selected entries'), Widgets::bestof(...), null, 'List of selected entries')
            ->addTitle(__('Best of me'))
            ->setting('orderby', __('Sort:'), 'asc', 'combo', [__('Ascending') => 'asc', __('Descending') => 'desc'])
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        self::$widgets
            ->create(self::WIDGET_ID_LANGS, __('Blog languages'), Widgets::langs(...), null, 'List of available languages')
            ->addTitle(__('Languages'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        self::$widgets
            ->create(self::WIDGET_ID_CATEGORIES, __('List of categories'), Widgets::categories(...), null, 'List of categories')
            ->addTitle(__('Categories'))
            ->setting('postcount', __('With entries counts'), 0, 'check')
            ->setting('subcatscount', __('Include sub cats in count'), false, 'check')
            ->setting('with_empty', __('Include empty categories'), 0, 'check')
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        self::$widgets
            ->create(self::WIDGET_ID_SUBSCRIBE, __('Subscribe links'), Widgets::subscribe(...), null, 'Feed subscription links (RSS or Atom)')
            ->addTitle(__('Subscribe'))
            ->setting('type', __('Feeds type:'), 'atom', 'combo', ['Atom' => 'atom', 'RSS' => 'rss2'])
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        self::$widgets
            ->create(self::WIDGET_ID_FEED, __('Feed reader'), Widgets::feed(...), null, 'List of last entries from feed (RSS or Atom)')
            ->addTitle(__('Somewhere else'))
            ->setting('url', __('Feed URL:'), '')
            ->setting('limit', __('Entries limit:'), 10)
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        self::$widgets
            ->create(self::WIDGET_ID_TEXT, __('Text'), Widgets::text(...), null, 'Simple text')
            ->addTitle()
            ->setting('text', __('Text:'), '', 'textarea')
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        $rs         = App::blog()->getCategories(['post_type' => 'post']);
        $categories = ['' => '', __('Uncategorized') => 'null'];
        while ($rs->fetch()) {
            $categories[str_repeat('&nbsp;&nbsp;', (int) $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . Html::escapeHTML($rs->cat_title)] = $rs->cat_id;
        }
        $w = self::$widgets->create(self::WIDGET_ID_LASTPOSTS, __('Last entries'), Widgets::lastposts(...), null, 'List of last entries published');
        $w
            ->addTitle(__('Last entries'))
            ->setting('category', __('Category:'), '', 'combo', $categories);
        if (App::plugins()->moduleExists('tags')) {
            $w->setting('tag', __('Tag:'), '');
        }
        $w
            ->setting('limit', __('Entries limit:'), 10)
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
        unset($rs, $categories, $w);

        self::$widgets
            ->create('lastcomments', __(self::WIDGET_ID_LASTCOMMENTS), Widgets::lastcomments(...), null, 'List of last comments published')
            ->addTitle(__('Last comments'))
            ->setting('limit', __('Comments limit:'), 10)
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        # --BEHAVIOR-- initWidgets -- WidgetsStack
        App::behavior()->callBehavior('initWidgets', self::$widgets);

        # Default widgets
        self::$default_widgets = [
            Widgets::WIDGETS_NAV    => new WidgetsStack(),
            Widgets::WIDGETS_EXTRA  => new WidgetsStack(),
            Widgets::WIDGETS_CUSTOM => new WidgetsStack(),
        ];

        self::$default_widgets[Widgets::WIDGETS_NAV]->append(self::$widgets->get(self::WIDGET_ID_SEARCH));
        self::$default_widgets[Widgets::WIDGETS_NAV]->append(self::$widgets->get(self::WIDGET_ID_BESTOF));
        self::$default_widgets[Widgets::WIDGETS_NAV]->append(self::$widgets->get(self::WIDGET_ID_CATEGORIES));
        self::$default_widgets[Widgets::WIDGETS_CUSTOM]->append(self::$widgets->get(self::WIDGET_ID_SUBSCRIBE));

        # --BEHAVIOR-- initDefaultWidgets -- WidgetsStack, array<string,WidgetsStack>
        App::behavior()->callBehavior('initDefaultWidgets', self::$widgets, self::$default_widgets);
    }

    /**
     * Render search form widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function search(WidgetsElement $widget): string
    {
        if (App::blog()->settings()->system->no_search) {
            return '';
        }

        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $value = App::frontend()->search ?? '';

        return $widget->renderDiv(
            (bool) $widget->content_only,
            $widget->class,
            'id="search"',
            ($widget->title ? $widget->renderTitle(
                (new Label(Html::escapeHTML($widget->title), Label::OL_TF))->for('q')->render()
            ) : '') .
            (new Form('q-form'))
                ->method('get')
                ->action(App::blog()->url())
                ->role('search')
                ->fields([
                    (new Para())
                        ->separator(' ')
                        ->items([
                            (new Input('q', 'search'))
                                ->size(10)
                                ->maxlength(255)
                                ->value($value)
                                ->placeholder($widget->get('placeholder') ? Html::escapeHTML($widget->get('placeholder')) : '')
                                ->extra('aria-label="' . __('Search') . '"'),
                            (new Submit('q-submit', 'ok'))
                                ->class('submit')
                                ->title(__('Search')),
                        ]),
                ])
            ->render()
        );
    }

    /**
     * Render navigation widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function navigation(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $links = [];
        if (!App::url()->isHome(App::url()->getType())) {
            // Not on home page (standard or static), add home link
            $links[] = (new Li())
                ->class('topnav-home')
                ->items([
                    (new Link())
                        ->href(App::blog()->url())
                        ->text(__('Home')),
                ]);
            if (App::blog()->settings()->system->static_home) {
                // Static mode: add recent posts link
                $links[] = (new Li())
                    ->class('topnav-posts')
                    ->items([
                        (new Link())
                            ->href(App::blog()->url() . App::url()->getURLFor('posts'))
                            ->text(__('Recent posts')),
                    ]);
            }
        } elseif (App::blog()->settings()->system->static_home) {
            // Static mode: add recent posts link
            $links[] = (new Li())
                ->class('topnav-posts')
                ->items([
                    (new Link())
                        ->href(App::blog()->url() . App::url()->getURLFor('posts'))
                        ->text(__('Recent posts')),
                ]);
        }

        $links[] = (new Li())
            ->class('topnav-arch')
            ->items([
                (new Link())
                    ->href(App::blog()->url() . App::url()->getURLFor('archive'))
                    ->text(__('Archives')),
            ]);

        $res .= (new Div(null, 'nav'))
            ->items([
                (new Ul())
                    ->items($links),
            ])
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, $widget->class, 'id="topnav"', $res);
    }

    public static function buildCategoryList(MetaRecord $rs, WidgetsElement $widget): Ul
    {
        $root       = new Ul();
        $stack      = [0 => $root];    // level => Ul
        $last_child = [];              // level => last Li object at this level

        $categories = $rs->rows();
        $count      = $rs->count();

        for ($i = 0; $i < $count; $i++) {
            $category = $categories[$i];
            $level    = $category['level'];

            $class = (App::url()->getType() === 'category' && App::frontend()->context()->categories instanceof MetaRecord && App::frontend()->context()->categories->cat_id == $category['cat_id']) || (App::url()->getType() === 'post' && App::frontend()->context()->posts instanceof MetaRecord && App::frontend()->context()->posts->cat_id == $category['cat_id']) ? 'category-current' : '';

            $li = (new Li())
                ->class([$class])
                ->items([
                    (new Set())
                        ->separator(' ')
                        ->items([
                            (new Link())
                                ->href(App::blog()->url() . App::url()->getURLFor('category', $category['cat_url']))
                                ->text(Html::escapeHTML($category['cat_title'])),
                            $widget->get('postcount') ?
                            (new Span('(' . ($widget->get('subcatscount') ? $category['nb_total'] : $category['nb_post']) . ')')) :
                            (new None()),
                        ]),
                ]);

            // Add Li to its parent Ul
            $items                    = $stack[$level - 1]->items;
            $items[]                  = $li;    // @phpstan-ignore-line
            $stack[$level - 1]->items = $items;
            $last_child[$level]       = $li;

            // Look ahead to see if this node will have children
            $next_level = $categories[$i + 1]['level'] ?? 0;
            if ($next_level > $level) {
                $ul = new Ul();

                $items   = $li->items;
                $items[] = $ul;     // @phpstan-ignore-line
                $li->items($items);

                $stack[$level] = $ul;
            }

            // Remove deeper levels from stack and lastLis
            foreach (array_keys($stack) as $k) {
                if ($k > $level) {
                    unset($stack[$k], $last_child[$k]);
                }
            }
        }

        return $root;
    }

    /**
     * Render categories widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function categories(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $rs = App::blog()->getCategories(['post_type' => 'post', 'without_empty' => !$widget->get('with_empty')]);
        if ($rs->isEmpty()) {
            return '';
        }

        // Static loading of all records, as we'll be navigating backwards and forwards each time we change category level.
        $rs = $rs->toStatic();

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $res .= static::buildCategoryList($rs, $widget)->render();

        return $widget->renderDiv((bool) $widget->content_only, 'categories ' . $widget->class, '', $res);
    }

    /**
     * Render selected posts widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function bestof(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $params = [
            'post_selected' => true,
            'no_content'    => true,
            'order'         => 'post_dt ' . strtoupper((string) $widget->get('orderby')),
        ];

        $rs = App::blog()->getPosts($params);

        if ($rs->isEmpty()) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $posts = function (MetaRecord $rs) {
            $class = App::url()->getType() === 'post' && App::frontend()->context()->posts instanceof MetaRecord && App::frontend()->context()->posts->post_id == $rs->post_id ? 'post-current' : '';
            while ($rs->fetch()) {
                yield (new Li())
                    ->class([$class])
                    ->items([
                        (new Link())
                            ->href($rs->getURL())
                            ->text(Html::escapeHTML($rs->post_title)),
                    ]);
            }
        };

        $res .= (new Ul())
            ->items([
                ...$posts($rs),
            ])
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, 'selected ' . $widget->class, '', $res);
    }

    /**
     * Render langs widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function langs(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $rs = App::blog()->getLangs([
            'order' => 'asc',
        ]);

        if ($rs->count() <= 1) {
            return '';
        }

        $langs = L10n::getISOcodes();
        $res   = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $lis = function (MetaRecord $rs) use ($langs) {
            while ($rs->fetch()) {
                $link = (new Link())
                    ->href(App::blog()->url() . App::url()->getURLFor('lang', $rs->post_lang))
                    ->class('lang-' . $rs->post_lang)
                    ->text($langs[$rs->post_lang] ?? $rs->post_lang);

                yield (new Li())
                    ->items([
                        App::frontend()->context()->cur_lang == $rs->post_lang ?
                        (new Strong())
                            ->items([
                                $link,
                            ]) :
                        $link,
                    ]);
            }
        };

        $res .= (new Ul())
            ->items([
                ...$lis($rs),
            ])
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, 'langs ' . $widget->class, '', $res);
    }

    /**
     * Render feed subscription widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function subscribe(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $type = ($widget->get('type') == 'atom' || $widget->get('type') == 'rss2') ? $widget->get('type') : 'rss2';
        $mime = $type == 'rss2' ? 'application/rss+xml' : 'application/atom+xml';
        if (App::frontend()->context()->exists('cur_lang')) {
            $type = App::frontend()->context()->cur_lang . '/' . $type;
        }

        $p_title = __('This blog\'s entries %s feed');
        $c_title = __('This blog\'s comments %s feed');

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $res .= (new Ul())
            ->items([
                (new Li())
                    ->items([
                        (new Link())
                            ->type($mime)
                            ->href(App::blog()->url() . App::url()->getURLFor('feed', $type))
                            ->title(sprintf($p_title, ($type == 'atom' ? 'Atom' : 'RSS')))
                            ->class('feed')
                            ->text(__('Entries feed')),
                    ]),
                (App::blog()->settings()->system->allow_comments || App::blog()->settings()->system->allow_trackbacks) ?
                    (new Li())
                        ->items([
                            (new Link())
                                ->type($mime)
                                ->href(App::blog()->url() . App::url()->getURLFor('feed', $type . '/comments'))
                                ->title(sprintf($c_title, ($type == 'atom' ? 'Atom' : 'RSS')))
                                ->class('feed')
                                ->text(__('Comments feed')),
                        ]) :
                    (new None()),
            ])
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, 'syndicate ' . $widget->class, '', $res);
    }

    /**
     * Render feed widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function feed(WidgetsElement $widget): string
    {
        if (!$widget->get('url')) {
            return '';
        }

        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $limit = abs((int) $widget->get('limit'));

        try {
            $feed = Reader::quickParse($widget->get('url'), App::config()->cacheRoot());
            if (!$feed || !count($feed->items)) {
                return '';
            }
        } catch (Exception) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $news = function ($items) use ($limit) {
            $i = 0;
            foreach ($items as $item) {
                $title = isset($item->title) && strlen(trim((string) $item->title)) ? $item->title : '';
                $link  = isset($item->link)  && strlen(trim((string) $item->link)) ? $item->link : '';

                if (!$link && !$title) {
                    continue;
                }

                if (!$title) {
                    $title = substr($link, 0, 25) . '...';
                }

                yield (new Li())
                    ->items([
                        $link ?
                        (new Link())
                            ->href(Html::escapeHTML($item->link))
                            ->text($title) :
                        (new Text($title)),
                    ]);

                $i++;
                if ($i >= $limit) {
                    break;
                }
            }
        };

        $res .= (new Ul())
            ->items([
                ...$news($feed->items),
            ])
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, 'feed ' . $widget->class, '', $res);
    }

    /**
     * Render text widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function text(WidgetsElement $widget): string
    {
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '') . $widget->get('text');

        return $widget->renderDiv((bool) $widget->content_only, 'text ' . $widget->class, '', $res);
    }

    /**
     * Render last posts widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function lastposts(WidgetsElement $widget): string
    {
        $params = [];
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $params['limit']      = abs((int) $widget->get('limit'));
        $params['order']      = 'post_dt desc';
        $params['no_content'] = true;

        if ($widget->get('category')) {
            if ($widget->get('category') == 'null') {
                $params['sql'] = ' AND P.cat_id IS NULL ';
            } elseif (is_numeric($widget->get('category'))) {
                $params['cat_id'] = (int) $widget->get('category');
            } else {
                $params['cat_url'] = $widget->get('category');
            }
        }

        if ($widget->get('tag')) {
            $params['meta_id'] = $widget->get('tag');
            $rs                = App::meta()->getPostsByMeta($params);
        } else {
            $rs = App::blog()->getPosts($params);
        }

        if ($rs->isEmpty()) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $posts = function (MetaRecord $rs) {
            $class = App::url()->getType() === 'post' && App::frontend()->context()->posts instanceof MetaRecord && App::frontend()->context()->posts->post_id == $rs->post_id ? 'post-current' : '';
            while ($rs->fetch()) {
                yield (new Li())
                    ->class([$class])
                    ->items([
                        (new Link())
                            ->href($rs->getURL())
                            ->text(Html::escapeHTML($rs->post_title)),
                    ]);
            }
        };

        $res .= (new Ul())
            ->items([
                ...$posts($rs),
            ])
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, 'lastposts ' . $widget->class, '', $res);
    }

    /**
     * Render last comments widget.
     *
     * @param   WidgetsElement  $widget     The widget
     */
    public static function lastcomments(WidgetsElement $widget): string
    {
        $params = [];
        if ($widget->offline) {
            return '';
        }

        if (!$widget->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $params['limit'] = abs((int) $widget->get('limit'));
        $params['order'] = 'comment_dt desc';
        $rs              = App::blog()->getComments($params);

        if ($rs->isEmpty()) {
            return '';
        }

        $res = ($widget->title ? $widget->renderTitle(Html::escapeHTML($widget->title)) : '');

        $comments = function (MetaRecord $rs) {
            while ($rs->fetch()) {
                yield (new Li())
                    ->class((bool) $rs->comment_trackback ? 'last-tb' : 'last-comment')
                    ->items([
                        (new Link())
                            ->href($rs->getPostURL() . '#c' . $rs->comment_id)
                            ->text(Html::escapeHTML($rs->post_title) . ' - ' . Html::escapeHTML($rs->comment_author)),
                    ]);
            }
        };

        $res .= (new Ul())
            ->items([
                ...$comments($rs),
            ])
        ->render();

        return $widget->renderDiv((bool) $widget->content_only, 'lastcomments ' . $widget->class, '', $res);
    }
}
