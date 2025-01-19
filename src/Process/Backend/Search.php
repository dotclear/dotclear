<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Listing\ListingComments;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @since 2.27 Before as admin/search.php
 *
 * @todo switch Helper/Html/Form/...
 */
class Search extends Process
{
    // Local properties (used by behavior callbacks)

    /**
     * Number of items found
     */
    protected static ?int $count = null;

    /**
     * List of related entries
     *
     * @var null|ListingPosts|ListingComments
     */
    protected static $list;

    /**
     * Available actions on entries
     *
     * @var null|ActionsPosts|ActionsComments
     */
    protected static $actions;

    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        App::behavior()->addBehaviors([
            'adminSearchPageComboV2' => static::typeCombo(...),
            'adminSearchPageHeadV2'  => static::pageHead(...),
            // posts search
            'adminSearchPageProcessV2' => static::processPosts(...),
            'adminSearchPageDisplayV2' => static::displayPosts(...),
        ]);
        App::behavior()->addBehaviors([
            // comments search
            'adminSearchPageProcessV2' => static::processComments(...),
            'adminSearchPageDisplayV2' => static::displayComments(...),
        ]);

        $qtype_combo = [];
        # --BEHAVIOR-- adminSearchPageCombo -- array<int,array>
        App::behavior()->callBehavior('adminSearchPageComboV2', [&$qtype_combo]);
        App::backend()->qtype_combo = $qtype_combo;

        return self::status(true);
    }

    public static function process(): bool
    {
        App::backend()->q = $_REQUEST['q'] ?? $_REQUEST['qx'] ?? null;

        if (strlen((string) App::backend()->q) !== 0) {
            // Cope with search beginning with : (quick menu access)
            $prefix = App::auth()->prefs()->interface->quickmenuprefix ?: ':';
            if (str_starts_with((string) App::backend()->q, $prefix)) {
                if (strlen((string) App::backend()->q) > 1) {
                    // Look for a quick menu access
                    $term = Html::escapeHTML(substr((string) App::backend()->q, 1));
                    $link = App::backend()->searchMenuitem($term);
                    if ($link !== false) {
                        $link = str_replace('&amp;', '&', $link);
                        Http::redirect($link);
                    }
                } else {
                    // Back to dashboard
                    App::backend()->url()->redirect('admin.home');
                }
            }

            // Nothing found, back to normal
            if (str_starts_with((string) App::backend()->q, '\\' . $prefix)) {
                // Search term begins with quick menu prefix
                App::backend()->q = substr((string) App::backend()->q, 1);
            }
        }

        App::backend()->qtype = $_REQUEST['qtype'] ?? 'p';
        App::backend()->q     = Html::escapeHTML(App::backend()->q);

        if (App::backend()->q !== '' && !in_array(App::backend()->qtype, App::backend()->qtype_combo)) {
            App::backend()->qtype = 'p';
        }

        App::backend()->page = empty($_GET['page']) ? 1 : max(1, (int) $_GET['page']);
        App::backend()->nb   = UserPref::getUserFilters('search', 'nb');
        if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
            App::backend()->nb = (int) $_GET['nb'];
        }

        return true;
    }

    public static function render(): void
    {
        $args = ['q' => App::backend()->q, 'qtype' => App::backend()->qtype, 'page' => App::backend()->page, 'nb' => App::backend()->nb];

        # --BEHAVIOR-- adminSearchPageHead -- array<string,string>
        $starting_scripts = App::backend()->q ? App::behavior()->callBehavior('adminSearchPageHeadV2', $args) : '';

        if (App::backend()->q) {
            # --BEHAVIOR-- adminSearchPageProcess -- array<string,string>
            App::behavior()->callBehavior('adminSearchPageProcessV2', $args);
        }

        Page::open(
            __('Search'),
            $starting_scripts,
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Search')                          => '',
                ]
            )
        );

        echo (new Form('search-frm'))
            ->method('get')
            ->action(App::backend()->url()->get('admin.search'))
            ->role('search')
            ->fields([
                (new Fieldset())
                    ->legend(new Legend(__('Search options')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Input('q'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->q))
                                    ->label(new Label(__('Query:'), Label::OL_TF)),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('qtype'))
                                    ->items(App::backend()->qtype_combo)
                                    ->default(App::backend()->qtype)
                                    ->label(new Label(__('In:'), Label::OL_TF)),
                            ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Submit('search-submit', __('Search'))),
                                (new Button('back'))
                                    ->class(['go-back', 'reset', 'hidden-if-no-js'])
                                    ->value(__('Back')),
                                (new Hidden('process', 'Search')),
                            ]),
                    ]),
            ])
        ->render();

        if (App::backend()->q && !App::error()->flag()) {
            ob_start();

            # --BEHAVIOR-- adminSearchPageDisplay -- array<string,string>
            App::behavior()->callBehavior('adminSearchPageDisplayV2', $args);

            $res = ob_get_contents();
            ob_end_clean();
            echo $res ?: '<p>' . __('No results found') . '</p>';
        }

        Page::helpBlock('core_search');
        Page::close();
    }

    /**
     * Behaviors callbacks
     */

    /**
     * Populate combo with available search actions
     *
     * @param      array<int, array<string, string>>  $combo  The combo
     */
    public static function typeCombo(array $combo): void
    {
        $combo[0][__('Search in entries')]  = 'p';
        $combo[0][__('Search in comments')] = 'c';
    }

    /**
     * Add specific scripts
     *
     * @param      array<string,string>   $args   The arguments
     */
    public static function pageHead(array $args): string
    {
        if ($args['qtype'] == 'p') {
            return Page::jsLoad('js/_posts_list.js');
        } elseif ($args['qtype'] == 'c') {
            return Page::jsLoad('js/_comments.js');
        }

        return '';
    }

    /**
     * Process search in posts
     *
     * @param      array<string,string>   $args   The arguments
     */
    public static function processPosts(array $args): string
    {
        if ($args['qtype'] != 'p') {
            return '';
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(((int) $args['page'] - 1) * (int) $args['nb']), (int) $args['nb']],
            'no_content' => true,
            'order'      => 'post_dt DESC',
        ];

        try {
            self::$count   = (int) App::blog()->getPosts($params, true)->f(0);
            self::$list    = new ListingPosts(App::blog()->getPosts($params), self::$count);
            self::$actions = new ActionsPosts(App::backend()->url()->get('admin.search'), $args);
            self::$actions->process();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return '';
    }

    /**
     * Display search in posts
     *
     * @param      array<string,string>   $args   The arguments
     */
    public static function displayPosts(array $args): string
    {
        if ($args['qtype'] != 'p' || self::$count === null) {
            return '';
        }

        if (self::$count > 0) {
            echo (new Text('h3', sprintf(__('one entry found', '%d entries found', self::$count), self::$count)))
            ->render();
        }

        if (self::$actions && self::$list) {
            $combo = self::$actions->getCombo();
            if (is_array($combo)) {
                $block = (new Form('form-entries'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.search'))
                    ->fields([
                        (new Text(null, '%s')), // Here will go the posts list
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items($combo)
                                            ->label(new Label(__('Selected entries action:'), Label::IL_TF)),
                                        (new Submit('do-action', __('ok'))),
                                        App::nonce()->formNonce(),
                                        ... self::$actions->hiddenFields(),
                                    ]),
                            ]),
                    ])
                ->render();
            } else {
                $block = (new Text(null, '%s'))
                ->render();
            }

            self::$list->display(
                (int) $args['page'],
                (int) $args['nb'],
                $block
            );
        }

        return '';
    }

    /**
     * Process search in comments
     *
     * @param      array<string,string>   $args   The arguments
     */
    public static function processComments(array $args): string
    {
        if ($args['qtype'] != 'c') {
            return '';
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(((int) $args['page'] - 1) * (int) $args['nb']), (int) $args['nb']],
            'no_content' => true,
            'order'      => 'comment_dt DESC',
        ];

        try {
            self::$count   = (int) App::blog()->getComments($params, true)->f(0);
            self::$list    = new ListingComments(App::blog()->getComments($params), self::$count);
            self::$actions = new ActionsComments(App::backend()->url()->get('admin.search'), $args);
            if (self::$actions->process()) {
                return '';
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return '';
    }

    /**
     * Display search in comments
     *
     * @param      array<string,string>   $args   The arguments
     */
    public static function displayComments(array $args): string
    {
        if ($args['qtype'] != 'c' || self::$count === null) {
            return '';
        }

        if (self::$count > 0) {
            echo (new Text('h3', sprintf(__('one comment found', '%d comments found', self::$count), self::$count)))
            ->render();
        }

        // IP are available only for super-admin and admin
        $show_ip = App::auth()->check(
            App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            App::blog()->id()
        );

        if (self::$actions && self::$list) {
            $combo = self::$actions->getCombo();
            if (is_array($combo)) {
                $block = (new Form('form-comments'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.search'))
                    ->fields([
                        (new Text(null, '%s')), // Here will go the comments list
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items($combo)
                                            ->label(new Label(__('Selected comments action:'), Label::IL_TF)),
                                        (new Submit('do-action', __('ok'))),
                                        App::nonce()->formNonce(),
                                        ... self::$actions->hiddenFields(),
                                    ]),
                            ]),
                    ])
                ->render();
            } else {
                $block = (new Text(null, '%s'))
                ->render();
            }

            self::$list->display(
                (int) $args['page'],
                (int) $args['nb'],
                $block,
                false,
                false,
                $show_ip
            );
        }

        return '';
    }
}
