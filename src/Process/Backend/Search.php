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
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/search.php
 */
class Search
{
    use TraitProcess;

    // Local properties (used by behavior callbacks)

    /**
     * Number of items found
     */
    protected static int $count = 0;

    /**
     * List of related entries
     *
     * @var null|\Dotclear\Core\Backend\Listing\ListingPosts|\Dotclear\Core\Backend\Listing\ListingComments   $list
     */
    protected static $list;

    /**
     * Available actions on entries
     *
     * @var null|\Dotclear\Core\Backend\Action\ActionsPosts|\Dotclear\Core\Backend\Action\ActionsComments   $actions
     */
    protected static $actions;

    /**
     * Action performed?
     */
    protected static mixed $performed = null;

    protected static string $q;

    protected static string $qtype;

    protected static int $page;

    protected static int $nb;

    /**
     * @var array<int, array<string, string>> $qtype_combo
     */
    protected static array $qtype_combo;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
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
        self::$qtype_combo = $qtype_combo;

        return self::status(true);
    }

    public static function process(): bool
    {
        if (isset($_REQUEST['q']) && is_string($_REQUEST['q'])) {
            self::$q = $_REQUEST['q'];
        } elseif (isset($_REQUEST['qx']) && is_string($_REQUEST['qx'])) {
            self::$q = $_REQUEST['qx'];
        } else {
            self::$q = '';
        }

        if (self::$q !== '') {
            // Cope with search beginning with : (quick menu access)
            $prefix = App::auth()->prefs()->get('interface')->getStr('quickmenuprefix', false) ?: ':';
            if (str_starts_with(self::$q, $prefix)) {
                if (strlen(self::$q) > 1) {
                    // Look for a quick menu access
                    $term = Html::escapeHTML(substr(self::$q, 1));
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
            if (str_starts_with(self::$q, '\\' . $prefix)) {
                // Search term begins with quick menu prefix
                self::$q = substr(self::$q, 1);
            }
        }

        self::$qtype = isset($_REQUEST['qtype']) && is_string($qtype = $_REQUEST['qtype']) ? $qtype : 'p';
        self::$q     = Html::escapeHTML(self::$q);

        if (self::$q !== '' && !in_array(self::$qtype, self::$qtype_combo)) {
            self::$qtype = 'p';
        }

        self::$page = isset($_GET['page']) && is_numeric($page = $_GET['page']) ? max(1, (int) $page) : 1;
        self::$nb   = App::backend()->userPref()->getUserFilterNb('search') ?? 0;
        self::$nb   = isset($_GET['nb']) && is_numeric($nb = $_GET['nb']) ? max(1, (int) $nb) : self::$nb;

        return true;
    }

    public static function render(): void
    {
        $args = [
            'q'     => self::$q,
            'qtype' => self::$qtype,
            'page'  => self::$page,
            'nb'    => self::$nb,
        ];

        # --BEHAVIOR-- adminSearchPageHead -- array<string,string>
        $starting_scripts = self::$q !== '' ? App::behavior()->callBehavior('adminSearchPageHeadV2', $args) : '';

        if (self::$q !== '') {
            # --BEHAVIOR-- adminSearchPageProcess -- array<string,string>
            App::behavior()->callBehavior('adminSearchPageProcessV2', $args);
            if (self::$performed) {
                return;
            }
        }

        App::backend()->page()->open(
            __('Search'),
            $starting_scripts,
            App::backend()->page()->breadcrumb(
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
                                (new Input('q', 'search'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$q))
                                    ->label(new Label(__('Query:'), Label::OL_TF)),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('qtype'))
                                    ->items(self::$qtype_combo)
                                    ->default(self::$qtype)
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

        if (self::$q !== '' && !App::error()->flag()) {
            ob_start();

            # --BEHAVIOR-- adminSearchPageDisplay -- array<string,string>
            App::behavior()->callBehavior('adminSearchPageDisplayV2', $args);

            $res = ob_get_contents();
            ob_end_clean();
            echo $res ?: '<p>' . __('No results found') . '</p>';
        }

        App::backend()->page()->helpBlock('core_search');
        App::backend()->page()->close();
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
        if ($args['qtype'] === 'p') {
            return App::backend()->page()->jsLoad('js/_posts_list.js');
        }

        if ($args['qtype'] === 'c') {
            return App::backend()->page()->jsLoad('js/_comments.js');
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
        if ($args['qtype'] !== 'p') {
            return '';
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(((int) $args['page'] - 1) * (int) $args['nb']), (int) $args['nb']],
            'no_content' => true,
            'order'      => 'post_dt DESC',
            'post_type'  => '',
        ];

        try {
            self::$count     = App::blog()->getPosts($params, true)->cardinal();
            self::$list      = App::backend()->listing()->posts(App::blog()->getPosts($params), self::$count);
            self::$actions   = App::backend()->action()->posts(App::backend()->url()->get('admin.search'), $args);
            self::$performed = self::$actions->process();
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
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
        if ($args['qtype'] !== 'p' || self::$count === 0) {
            return '';
        }

        if (self::$count > 0) {
            echo (new Text('h3', sprintf(__('One entry found', '%d entries found', self::$count), self::$count)))
            ->render();
        }

        if (self::$actions && self::$list) {
            $combo = self::$actions->getCombo();
            if ($combo !== []) {
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
                $block,
                false,
                true
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
        if ($args['qtype'] !== 'c') {
            return '';
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(((int) $args['page'] - 1) * (int) $args['nb']), (int) $args['nb']],
            'no_content' => true,
            'order'      => 'comment_dt DESC',
        ];

        try {
            self::$count     = App::blog()->getComments($params, true)->cardinal();
            self::$list      = App::backend()->listing()->comments(App::blog()->getComments($params), self::$count);
            self::$actions   = App::backend()->action()->comments(App::backend()->url()->get('admin.search'), $args);
            self::$performed = self::$actions->process();
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
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
        if ($args['qtype'] !== 'c' || self::$count === 0) {
            return '';
        }

        if (self::$count > 0) {
            echo (new Text('h3', sprintf(__('One comment found', '%d comments found', self::$count), self::$count)))
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
            if ($combo !== []) {
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
