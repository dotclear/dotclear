<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup simpleMenu
 */
class Manage extends Process
{
    // Local constants

    private const STEP_LIST       = 0;
    private const STEP_TYPE       = 1;
    private const STEP_SUBTYPE    = 2;
    private const STEP_ATTRIBUTES = 3;
    private const STEP_ADD        = 4;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::backend()->page_title = __('Simple menu');

        # Url du blog
        App::backend()->blog_url = Html::stripHostURL(App::blog()->url());

        # Liste des catégories
        $categories_label                = [];
        $rs                              = App::blog()->getCategories(['post_type' => 'post']);
        App::backend()->categories_combo = Combos::getCategoriesCombo($rs, false, true);
        $rs->moveStart();
        while ($rs->fetch()) {
            $categories_label[$rs->cat_url] = Html::escapeHTML($rs->cat_title);
        }
        App::backend()->categories_label = $categories_label;

        # Liste des langues utilisées
        App::backend()->langs_combo = Combos::getLangscombo(
            App::blog()->getLangs(['order' => 'asc'])
        );

        # Liste des mois d'archive
        $rs                          = App::blog()->getDates(['type' => 'month']);
        App::backend()->months_combo = array_merge(
            [__('All months') => '-'],
            Combos::getDatesCombo($rs)
        );

        App::backend()->first_year = App::backend()->last_year = 0;
        while ($rs->fetch()) {
            if ((App::backend()->first_year == 0) || ($rs->year() < App::backend()->first_year)) {
                App::backend()->first_year = $rs->year();
            }

            if ((App::backend()->last_year == 0) || ($rs->year() > App::backend()->last_year)) {
                App::backend()->last_year = $rs->year();
            }
        }
        unset($rs);

        # Liste des pages -- Doit être pris en charge plus tard par le plugin ?
        $pages_combo = [];

        try {
            $rs = App::blog()->getPosts(['post_type' => 'page']);
            while ($rs->fetch()) {
                $pages_combo[$rs->post_title] = $rs->getURL();
            }
            unset($rs);
        } catch (Exception) {
        }
        App::backend()->pages_combo = $pages_combo;

        # Liste des tags -- Doit être pris en charge plus tard par le plugin ?
        $tags_combo = [];

        try {
            $rs                         = App::meta()->getMetadata(['meta_type' => 'tag']);
            $tags_combo[__('All tags')] = '-';
            while ($rs->fetch()) {
                $tags_combo[$rs->meta_id] = $rs->meta_id;
            }
            unset($rs);
        } catch (Exception) {
        }
        App::backend()->tags_combo = $tags_combo;

        /**
         * Liste des types d'item de menu
         *
         * @var        ArrayObject<string, ArrayObject<string, bool>>
         */
        $items = new ArrayObject();

        $items['home'] = new ArrayObject([__('Home'), false]);  // @phpstan-ignore-line

        if (App::blog()->settings()->system->static_home) {
            $items['posts'] = new ArrayObject([__('Posts'), false]);    // @phpstan-ignore-line
        }

        if (count(App::backend()->langs_combo) > 1) {
            $items['lang'] = new ArrayObject([__('Language'), true]);   // @phpstan-ignore-line
        }
        if (count(App::backend()->categories_combo)) {
            $items['category'] = new ArrayObject([__('Category'), true]);   // @phpstan-ignore-line
        }
        if (count(App::backend()->months_combo) > 1) {
            $items['archive'] = new ArrayObject([__('Archive'), true]); // @phpstan-ignore-line
        }
        if (App::plugins()->moduleExists('pages') && count(App::backend()->pages_combo)) {
            $items['pages'] = new ArrayObject([__('Page'), true]);  // @phpstan-ignore-line
        }
        if (App::plugins()->moduleExists('tags') && count(App::backend()->tags_combo) > 1) {
            $items['tags'] = new ArrayObject([__('Tags'), true]);   // @phpstan-ignore-line
        }

        # --BEHAVIOR-- adminSimpleMenuAddType -- ArrayObject
        # Should add an item to $items[<id>] as an [<label>,<optional step (true or false)>]
        App::behavior()->callBehavior('adminSimpleMenuAddType', $items);

        $items['special'] = new ArrayObject([__('User defined'), false]);   // @phpstan-ignore-line

        $items_combo = [];
        foreach ($items as $k => $v) {
            $items_combo[$v[0]] = $k;
        }

        App::backend()->items       = $items;
        App::backend()->items_combo = $items_combo;

        # Lecture menu existant
        App::backend()->current_menu = App::blog()->settings()->system->get('simpleMenu');
        if (!is_array(App::backend()->current_menu)) {
            App::backend()->current_menu = [];
        }

        # Récupération état d'activation du menu
        App::backend()->menu_active = (bool) App::blog()->settings()->system->simpleMenu_active;

        // Saving new configuration
        App::backend()->item_type         = '';
        App::backend()->item_select       = '';
        App::backend()->item_select_label = '';
        App::backend()->item_label        = '';
        App::backend()->item_descr        = '';
        App::backend()->item_url          = '';
        App::backend()->item_type_label   = '';

        $item_targetBlank = false;

        // Get current menu
        $menu = App::backend()->current_menu;

        App::backend()->step = self::STEP_LIST;
        if (!empty($_POST['saveconfig'])) {
            try {
                App::backend()->menu_active = (empty($_POST['active'])) ? false : true;
                App::blog()->settings()->system->put('simpleMenu_active', App::backend()->menu_active, 'boolean');
                App::blog()->triggerBlog();

                // All done successfully, return to menu items list
                Notices::addSuccessNotice(__('Configuration successfully updated.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        } else {
            # Récupération paramètres postés
            App::backend()->item_type   = $_POST['item_type']   ?? '';
            App::backend()->item_select = $_POST['item_select'] ?? '';
            App::backend()->item_label  = $_POST['item_label']  ?? '';
            App::backend()->item_descr  = $_POST['item_descr']  ?? '';
            App::backend()->item_url    = $_POST['item_url']    ?? '';
            $item_targetBlank           = isset($_POST['item_targetBlank']) ? (empty($_POST['item_targetBlank']) ? false : true) : false;
            # Traitement
            App::backend()->step = (!empty($_GET['add']) ? (int) $_GET['add'] : self::STEP_LIST);
            if ((App::backend()->step > self::STEP_ADD) || (App::backend()->step < self::STEP_LIST)) {
                App::backend()->step = self::STEP_LIST;
            }

            if (App::backend()->step !== self::STEP_LIST) {
                # Récupération libellés des choix
                App::backend()->item_type_label = isset(App::backend()->items[App::backend()->item_type]) ? App::backend()->items[App::backend()->item_type][0] : '';

                switch (App::backend()->step) {
                    case self::STEP_TYPE:
                        // First step, menu item type to be selected
                        App::backend()->item_type = App::backend()->item_select = '';

                        break;
                    case self::STEP_SUBTYPE:
                        if (App::backend()->items[App::backend()->item_type][1]) {  // @phpstan-ignore-line
                            // Second step (optional), menu item sub-type to be selected
                            App::backend()->item_select = '';

                            break;
                        }
                    case self::STEP_ATTRIBUTES:
                        // Third step, menu item attributes to be changed or completed if necessary
                        App::backend()->item_select_label = '';
                        App::backend()->item_label        = __('Label');
                        App::backend()->item_descr        = __('Description');
                        App::backend()->item_url          = App::backend()->blog_url;
                        switch (App::backend()->item_type) {
                            case 'home':
                                App::backend()->item_label = __('Home');
                                App::backend()->item_descr = App::blog()->settings()->system->static_home ? __('Home page') : __('Recent posts');

                                break;
                            case 'posts':
                                App::backend()->item_label = __('Posts');
                                App::backend()->item_descr = __('Recent posts');
                                App::backend()->item_url .= App::url()->getURLFor('posts');

                                break;
                            case 'lang':
                                App::backend()->item_select_label = array_search(App::backend()->item_select, App::backend()->langs_combo);
                                App::backend()->item_label        = App::backend()->item_select_label;
                                App::backend()->item_descr        = sprintf(__('Switch to %s language'), App::backend()->item_select_label);
                                App::backend()->item_url .= App::url()->getURLFor('lang', App::backend()->item_select);

                                break;
                            case 'category':
                                App::backend()->item_select_label = App::backend()->categories_label[App::backend()->item_select];
                                App::backend()->item_label        = App::backend()->item_select_label;
                                App::backend()->item_descr        = __('Recent Posts from this category');
                                App::backend()->item_url .= App::url()->getURLFor('category', App::backend()->item_select);

                                break;
                            case 'archive':
                                App::backend()->item_select_label = array_search(App::backend()->item_select, App::backend()->months_combo);
                                if (App::backend()->item_select == '-') {
                                    App::backend()->item_label = __('Archives');
                                    App::backend()->item_descr = App::backend()->first_year . (App::backend()->first_year != App::backend()->last_year ? ' - ' . App::backend()->last_year : '');
                                    App::backend()->item_url .= App::url()->getURLFor('archive');
                                } else {
                                    App::backend()->item_label = App::backend()->item_select_label;
                                    App::backend()->item_descr = sprintf(__('Posts from %s'), App::backend()->item_select_label);
                                    App::backend()->item_url .= App::url()->getURLFor('archive', substr(App::backend()->item_select, 0, 4) . '/' . substr(App::backend()->item_select, -2));
                                }

                                break;
                            case 'pages':
                                App::backend()->item_select_label = array_search(App::backend()->item_select, App::backend()->pages_combo);
                                App::backend()->item_label        = App::backend()->item_select_label;
                                App::backend()->item_descr        = '';
                                App::backend()->item_url          = Html::stripHostURL(App::backend()->item_select);

                                break;
                            case 'tags':
                                App::backend()->item_select_label = array_search(App::backend()->item_select, App::backend()->tags_combo);
                                if (App::backend()->item_select == '-') {
                                    App::backend()->item_label = __('All tags');
                                    App::backend()->item_descr = '';
                                    App::backend()->item_url .= App::url()->getURLFor('tags');
                                } else {
                                    App::backend()->item_label = App::backend()->item_select_label;
                                    App::backend()->item_descr = sprintf(__('Recent posts for %s tag'), App::backend()->item_select_label);
                                    App::backend()->item_url .= App::url()->getURLFor('tag', App::backend()->item_select);
                                }

                                break;
                            case 'special':
                                break;
                            default:
                                # --BEHAVIOR-- adminSimpleMenuBeforeEdit - string, string, array<int,string>
                                # Should modify if necessary $item_label, $item_descr and $item_url
                                # Should set if necessary $item_select_label (displayed on further admin step only)
                                [
                                    $item_url, $item_descr, $item_label, $item_select_label
                                ] = [
                                    App::backend()->item_url,
                                    App::backend()->item_descr,
                                    App::backend()->item_label,
                                    App::backend()->item_select_label,
                                ];
                                App::behavior()->callBehavior(
                                    'adminSimpleMenuBeforeEdit',
                                    App::backend()->item_type,
                                    App::backend()->item_select,
                                    [
                                        &$item_label,
                                        &$item_descr,
                                        &$item_url,
                                        &$item_select_label,
                                    ]
                                );
                                [
                                    App::backend()->item_url,
                                    App::backend()->item_descr,
                                    App::backend()->item_label,
                                    App::backend()->item_select_label,
                                ] = [
                                    $item_url, $item_descr, $item_label, $item_select_label,
                                ];

                                break;
                        }

                        break;
                    case self::STEP_ADD:
                        // Fourth step, menu item to be added
                        try {
                            if ((App::backend()->item_label != '') && (App::backend()->item_url != '')) {
                                // Add new item menu in menu array
                                $menu[] = [
                                    'label'       => App::backend()->item_label,
                                    'descr'       => App::backend()->item_descr,
                                    'url'         => App::backend()->item_url,
                                    'targetBlank' => $item_targetBlank,
                                ];

                                // Save menu in blog settings
                                App::blog()->settings()->system->put('simpleMenu', $menu);
                                App::blog()->triggerBlog();

                                // All done successfully, return to menu items list
                                Notices::addSuccessNotice(__('Menu item has been successfully added.'));
                                My::redirect();
                            } else {
                                App::backend()->step              = self::STEP_ATTRIBUTES;
                                App::backend()->item_select_label = App::backend()->item_label;
                                Notices::addErrorNotice(__('Label and URL of menu item are mandatory.'));
                            }
                        } catch (Exception $e) {
                            App::error()->add($e->getMessage());
                        }

                        break;
                }
            } else {
                # Remove selected menu items
                if (!empty($_POST['removeaction'])) {
                    try {
                        if (!empty($_POST['items_selected'])) {
                            foreach ($_POST['items_selected'] as $k => $v) {
                                $menu[$v]['label'] = '';
                            }
                            $newmenu = [];
                            foreach ($menu as $k => $v) {
                                if ($v['label']) {
                                    $newmenu[] = [
                                        'label'       => $v['label'],
                                        'descr'       => $v['descr'],
                                        'url'         => $v['url'],
                                        'targetBlank' => $v['targetBlank'],
                                    ];
                                }
                            }
                            $menu = $newmenu;
                            // Save menu in blog settings
                            App::blog()->settings()->system->put('simpleMenu', $menu);
                            App::blog()->triggerBlog();

                            // All done successfully, return to menu items list
                            Notices::addSuccessNotice(__('Menu items have been successfully removed.'));
                            My::redirect();
                        } else {
                            throw new Exception(__('No menu items selected.'));
                        }
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }
                }

                # Update menu items
                if (!empty($_POST['updateaction'])) {
                    try {
                        foreach ($_POST['items_label'] as $k => $v) {
                            if (!$v) {
                                throw new Exception(__('Label is mandatory.'));
                            }
                        }
                        foreach ($_POST['items_url'] as $k => $v) {
                            if (!$v) {
                                throw new Exception(__('URL is mandatory.'));
                            }
                        }
                        $newmenu = [];
                        for ($i = 0; $i < (is_countable($_POST['items_label']) ? count($_POST['items_label']) : 0); $i++) {
                            $newmenu[] = [
                                'label'       => $_POST['items_label'][$i],
                                'descr'       => $_POST['items_descr'][$i],
                                'url'         => $_POST['items_url'][$i],
                                'targetBlank' => (empty($_POST['items_targetBlank' . $i])) ? false : true,
                            ];
                        }
                        $menu = $newmenu;

                        if (App::auth()->prefs()->accessibility->nodragdrop) {
                            # Order menu items
                            $order = [];
                            if (empty($_POST['im_order']) && !empty($_POST['order'])) {
                                $order = $_POST['order'];
                                asort($order);
                                $order = array_keys($order);
                            } elseif (!empty($_POST['im_order'])) {
                                $order = $_POST['im_order'];
                                if (str_ends_with($order, ',')) {
                                    $order = substr($order, 0, strlen($order) - 1);
                                }
                                $order = explode(',', $order);
                            }
                            if (!empty($order)) {
                                $newmenu = [];
                                foreach ($order as $i => $k) {
                                    $newmenu[] = [
                                        'label' => $menu[$k]['label'],
                                        'descr' => $menu[$k]['descr'],
                                        'url'   => $menu[$k]['url'], ];
                                }
                                $menu = $newmenu;
                            }
                        }

                        // Save menu in blog settings
                        App::blog()->settings()->system->put('simpleMenu', $menu);
                        App::blog()->triggerBlog();

                        // All done successfully, return to menu items list
                        Notices::addSuccessNotice(__('Menu items have been successfully updated.'));
                        My::redirect();
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }
                }
            }
        }

        // Store current menu (used in render)
        App::backend()->current_menu = $menu;

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $head = '';
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $head .= Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js');
        }
        $head .= My::jsLoad('simplemenu') .
            Page::jsJson('simplemenu', ['confirm_items_delete' => __('Are you sure you want to remove selected menu items?')]) .
            Page::jsConfirmClose('settings', 'menuitemsappend', 'additem', 'menuitems');

        Page::openModule(App::backend()->page_title, $head);

        $step_label = '';
        if (App::backend()->step) {
            switch (App::backend()->step) {
                case self::STEP_TYPE:
                    $step_label = __('Step #1');

                    break;
                case self::STEP_SUBTYPE:
                    if (App::backend()->items[App::backend()->item_type][1]) {
                        $step_label = __('Step #2');

                        break;
                    }
                case self::STEP_ATTRIBUTES:
                    if (App::backend()->items[App::backend()->item_type][1]) {
                        $step_label = __('Step #3');
                    } else {
                        $step_label = __('Step #2');
                    }

                    break;
            }
            echo
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    App::backend()->page_title            => App::backend()->getPageURL(),
                    __('Add item')                        => '',
                    $step_label                           => '',
                ],
                [
                    'hl_pos' => -2,
                ]
            ) .
            Notices::getNotices();
        } else {
            echo
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    App::backend()->page_title            => '',
                ]
            ) .
            Notices::getNotices();
        }

        if (App::backend()->step !== self::STEP_LIST) {
            // Formulaire d'ajout d'un item
            switch (App::backend()->step) {
                case self::STEP_TYPE:

                    // Selection du type d'item

                    echo (new Form('additem'))
                        ->method('post')
                        ->action(App::backend()->getPageURL() . '&add=' . trim((string) self::STEP_SUBTYPE))
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('Select type')))
                                ->fields([
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Select('item_type'))
                                                ->items(App::backend()->items_combo)
                                                ->label(new Label(__('Type of item menu:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ...My::hiddenFields(),
                                            (new Submit('appendaction', __('Continue...'))),
                                            (new Link('cancel'))
                                                ->href(My::manageUrl())
                                                ->class('button')
                                                ->accesskey('c')
                                                ->text(__('Cancel') . ' (c)'),
                                        ]),
                                ]),
                        ])
                    ->render();

                    break;

                case self::STEP_SUBTYPE:

                    if (App::backend()->items[App::backend()->item_type][1]) {
                        // Choix à faire

                        $choice = match (App::backend()->item_type) {
                            'lang' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(App::backend()->langs_combo)
                                        ->label(new Label(__('Select language:'), Label::OL_TF)),
                                ]),
                            'category' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(App::backend()->categories_combo)
                                        ->label(new Label(__('Select category:'), Label::OL_TF)),
                                ]),
                            'archive' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(App::backend()->months_combo)
                                        ->label(new Label(__('Select month (if necessary):'), Label::OL_TF)),
                                ]),
                            'pages' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(App::backend()->pages_combo)
                                        ->label(new Label(__('Select page:'), Label::OL_TF)),
                                ]),
                            'tags' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(App::backend()->tags_combo)
                                        ->label(new Label(__('Select tag (if necessary):'), Label::OL_TF)),
                                ]),
                            default => # --BEHAVIOR-- adminSimpleMenuSelect -- string, string
                                # Optional step once App::backend()->item_type known : should provide a field using 'item_select' as id, included in a <p class="field"></p> and don't forget the <label> ;-)
                                (new Text(null, App::behavior()->callBehavior('adminSimpleMenuSelect', App::backend()->item_type, 'item_select'))),
                        };

                        echo (new Form('additem'))
                            ->method('post')
                            ->action(App::backend()->getPageURL() . '&add=' . trim((string) self::STEP_ATTRIBUTES))
                            ->fields([
                                (new Fieldset())
                                    ->legend(new Legend(App::backend()->item_type_label))
                                    ->fields([
                                        $choice,
                                        (new Para())
                                            ->class('form-buttons')
                                            ->items([
                                                ...My::hiddenFields(),
                                                (new Hidden('item_type', App::backend()->item_type)),
                                                (new Submit('appendaction', __('Continue...'))),
                                                (new Link('cancel'))
                                                    ->href(My::manageUrl())
                                                    ->class('button')
                                                    ->accesskey('c')
                                                    ->text(__('Cancel') . ' (c)'),
                                            ]),
                                    ]),
                            ])
                        ->render();

                        break;
                    }

                case self::STEP_ATTRIBUTES:

                    // Libellé et description

                    echo (new Form('additem'))
                        ->method('post')
                        ->action(App::backend()->getPageURL() . '&add=' . trim((string) self::STEP_ADD))
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(App::backend()->item_type_label . (App::backend()->item_select_label != '' ? ' (' . App::backend()->item_select_label . ')' : '')))
                                ->fields([
                                    (new Note())
                                        ->class('form-note')
                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Input('item_label'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->value(App::backend()->item_label)
                                                ->required(true)
                                                ->placeholder(__('Label'))
                                                ->lang(App::auth()->getInfo('user_lang'))
                                                ->spellcheck(true)
                                                ->label(
                                                    (new Label(
                                                        (new Text('span', '*'))->render() . __('Label of item menu:'),
                                                        Label::OL_TF
                                                    ))->class('required')
                                                ),
                                        ]),
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Input('item_descr'))
                                                ->size(40)
                                                ->maxlength(255)
                                                ->value(App::backend()->item_descr)
                                                ->lang(App::auth()->getInfo('user_lang'))
                                                ->spellcheck(true)
                                                ->label(new Label(__('Description of item menu:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Input('item_url'))
                                                ->size(40)
                                                ->maxlength(255)
                                                ->value(App::backend()->item_url)
                                                ->required(true)
                                                ->placeholder(__('URL'))
                                                ->lang(App::auth()->getInfo('user_lang'))
                                                ->spellcheck(true)
                                                ->label(
                                                    (new Label(
                                                        (new Text('span', '*'))->render() . __('URL of item menu:'),
                                                        Label::OL_TF
                                                    ))->class('required')
                                                ),
                                        ]),
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Checkbox('item_targetBlank'))
                                                ->value('blank')
                                                ->label(new Label(__('Open URL on a new tab'), Label::OL_FT)),
                                        ]),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ...My::hiddenFields(),
                                            (new Hidden('item_type', App::backend()->item_type)),
                                            (new Hidden('item_select', App::backend()->item_select)),
                                            (new Submit('appendaction', __('Add this item'))),
                                            (new Link('cancel'))
                                                ->href(My::manageUrl())
                                                ->class('button')
                                                ->accesskey('c')
                                                ->text(__('Cancel') . ' (c)'),
                                        ]),
                                ]),
                        ])
                    ->render();

                    break;
            }
        }

        if (App::backend()->step === self::STEP_LIST) {
            // Formulaire d'activation

            echo (new Form('settings'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('active', App::backend()->menu_active))
                                ->value(1)
                                ->label(new Label(__('Enable simple menu for this blog'), Label::IL_FT)),
                        ]),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            ...My::hiddenFields(),
                            (new Submit('saveconfig', __('Save configuration'))),
                            (new Button(['back'], __('Back')))
                                ->class(['go-back', 'reset', 'hidden-if-no-js']),
                        ]),
                ])
            ->render();

            // Ajout d'un item

            echo (new Form('menuitemsappend'))
                ->method('post')
                ->action(App::backend()->getPageURL() . '&add=' . trim((string) self::STEP_TYPE))
                ->fields([
                    (new Para())
                        ->class('top-add')
                        ->items([
                            ...My::hiddenFields(),
                            (new Submit(['appendaction'], __('Add an item')))
                                ->class(['button', 'add']),
                        ]),
                ])
            ->render();
        }

        if (count(App::backend()->current_menu)) {
            // Prepare list

            // Entête table
            $headers = [];
            if (App::backend()->step === self::STEP_LIST) {
                $headers = array_merge($headers, [
                    (new Th())->scope('col'),
                    (new Th())->scope('col'),
                ]);
            }
            $headers = array_merge($headers, [
                (new Th())->scope('col')->text(__('Label')),
                (new Th())->scope('col')->text(__('Description')),
                (new Th())->scope('col')->text(__('URL')),
                (new Th())->scope('col')->text(__('Open URL on a new tab')),
            ]);

            $rows  = [];
            $count = 0;
            foreach (App::backend()->current_menu as $i => $m) {
                $cols = [];

                // targetBlank may not exists as this value has been added after this plugin creation.
                if ((isset($m['targetBlank'])) && ($m['targetBlank'])) {
                    $targetBlank    = true;
                    $targetBlankStr = 'X';
                } else {
                    $targetBlank    = false;
                    $targetBlankStr = '';
                }

                if (App::backend()->step === self::STEP_LIST) {
                    $count++;
                    $cols = [
                        (new Td())
                            ->class(['minimal', App::auth()->prefs()->accessibility->nodragdrop ? '' : 'handle'])
                            ->items([
                                (new Number(['order[' . $i . ']'], 1, count(App::backend()->current_menu), $count))
                                    ->class('position')
                                    ->title(sprintf(__('position of %s'), Html::escapeHTML($m['label']))),
                                (new Hidden(['dynorder[]', 'dynorder-' . $i], (string) $i)),
                            ]),
                        (new Td())
                            ->class('minimal')
                            ->items([
                                (new Checkbox(['items_selected[]', 'ims-' . $i]))
                                    ->value($i),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Input(['items_label[]', 'iml-' . $i]))
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($m['label']))
                                    ->lang(App::auth()->getInfo('user_lang'))
                                    ->spellcheck(true),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Input(['items_descr[]', 'imd-' . $i]))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($m['descr']))
                                    ->lang(App::auth()->getInfo('user_lang'))
                                    ->spellcheck(true),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Input(['items_url[]', 'imu-' . $i]))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($m['url'])),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Checkbox('items_targetBlank' . (string) $i, $targetBlank))
                                    ->value('blank'),
                            ]),
                    ];
                } else {
                    $cols = [
                        (new Td())
                            ->class('nowrap')
                            ->text(Html::escapeHTML($m['label'])),
                        (new Td())
                            ->class('nowrap')
                            ->text(Html::escapeHTML($m['descr'])),
                        (new Td())
                            ->class('nowrap')
                            ->text(Html::escapeHTML($m['url'])),
                        (new Td())
                            ->class('nowrap')
                            ->text($targetBlankStr),
                    ];
                }

                $rows[] = (new Tr())
                    ->id('l_' . (string) $i)
                    ->class('line')
                    ->cols($cols);
            }

            $list = (new Div())
                ->class('table-outer')
                ->items([
                    (new Table())
                        ->class('dragable')
                        ->caption(new Caption(__('Menu items list')))
                        ->thead((new Thead())
                            ->rows([
                                (new Tr())
                                    ->cols($headers),
                            ]))
                        ->tbody((new Tbody())
                            ->id(App::backend()->step === self::STEP_LIST ? 'menuitemslist' : '')
                            ->rows($rows)),
                ]);

            // Display form/list

            echo (App::backend()->step === self::STEP_LIST ?
                (new Form('menuitems'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        $list,
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())
                                    ->class(['col', 'checkboxes-helpers']),
                                (new para())
                                    ->class(['col', 'right'])
                                    ->items([
                                        (new Submit(['removeaction', 'remove-action'], __('Delete selected menu items')))
                                            ->class('delete'),
                                    ]),
                            ]),
                        (new Para())
                            ->class('col')
                            ->items([
                                ...My::hiddenFields(),
                                (new Hidden('im_order', '')),
                                (new Submit(['updateaction'], __('Update menu'))),
                            ]),
                    ]) :
                (new Set())
                    ->items([
                        $list,
                    ])
            )->render();
        } else {
            echo (new Note())
                ->text(__('No menu items so far.'))
            ->render();
        }

        Page::helpBlock('simpleMenu');

        Page::closeModule();
    }
}
