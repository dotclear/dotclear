<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use ArrayObject;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

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
         * @var        ArrayObject<string, ArrayObject<int, string|bool>>
         */
        $items = new ArrayObject();

        $items['home'] = new ArrayObject([__('Home'), false]);

        if (App::blog()->settings()->system->static_home) {
            $items['posts'] = new ArrayObject([__('Posts'), false]);
        }

        if (count(App::backend()->langs_combo) > 1) {
            $items['lang'] = new ArrayObject([__('Language'), true]);
        }
        if (count(App::backend()->categories_combo)) {
            $items['category'] = new ArrayObject([__('Category'), true]);
        }
        if (count(App::backend()->months_combo) > 1) {
            $items['archive'] = new ArrayObject([__('Archive'), true]);
        }
        if (App::plugins()->moduleExists('pages') && count(App::backend()->pages_combo)) {
            $items['pages'] = new ArrayObject([__('Page'), true]);
        }
        if (App::plugins()->moduleExists('tags') && count(App::backend()->tags_combo) > 1) {
            $items['tags'] = new ArrayObject([__('Tags'), true]);
        }

        # --BEHAVIOR-- adminSimpleMenuAddType -- ArrayObject
        # Should add an item to $items[<id>] as an [<label>,<optional step (true or false)>]
        App::behavior()->callBehavior('adminSimpleMenuAddType', $items);

        $items['special'] = new ArrayObject([__('User defined'), false]);

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
                                if (substr($order, -1) == ',') {
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
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
                My::jsLoad('simplemenu');
        }
        $head .= Page::jsConfirmClose('settings', 'menuitemsappend', 'additem', 'menuitems');

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

                    echo
                    '<form id="additem" action="' . App::backend()->getPageURL() . '&amp;add=' . trim((string) self::STEP_SUBTYPE) . '" method="post">' .
                    '<fieldset><legend>' . __('Select type') . '</legend>' .
                    '<p class="field"><label for="item_type" class="classic">' . __('Type of item menu:') . '</label>' .
                    form::combo('item_type', App::backend()->items_combo) . '</p>' .
                    '<p>' . App::nonce()->getFormNonce() .
                    '<input type="submit" name="appendaction" value="' . __('Continue...') . '" />' . '</p>' .
                    '</fieldset>' .
                    '</form>';

                    break;

                case self::STEP_SUBTYPE:

                    if (App::backend()->items[App::backend()->item_type][1]) {
                        // Choix à faire
                        echo
                        '<form id="additem" action="' . App::backend()->getPageURL() .
                        '&amp;add=' . trim((string) self::STEP_ATTRIBUTES) . '" method="post">' .
                        '<fieldset><legend>' . App::backend()->item_type_label . '</legend>';

                        echo match (App::backend()->item_type) {
                            'lang' => '<p class="field"><label for="item_select" class="classic">' . __('Select language:') . '</label>' .
                                form::combo('item_select', App::backend()->langs_combo) .
                                '</p>',
                            'category' => '<p class="field"><label for="item_select" class="classic">' . __('Select category:') . '</label>' .
                                form::combo('item_select', App::backend()->categories_combo) .
                                '</p>',
                            'archive' => '<p class="field"><label for="item_select" class="classic">' . __('Select month (if necessary):') . '</label>' .
                                form::combo('item_select', App::backend()->months_combo) .
                                '</p>',
                            'pages' => '<p class="field"><label for="item_select" class="classic">' . __('Select page:') . '</label>' .
                                form::combo('item_select', App::backend()->pages_combo) .
                                '</p>',
                            'tags' => '<p class="field"><label for="item_select" class="classic">' . __('Select tag (if necessary):') . '</label>' .
                                form::combo('item_select', App::backend()->tags_combo) .
                                '</p>',
                            default => # --BEHAVIOR-- adminSimpleMenuSelect -- string, string
                                # Optional step once App::backend()->item_type known : should provide a field using 'item_select' as id, included in a <p class="field"></p> and don't forget the <label> ;-)
                                App::behavior()->callBehavior('adminSimpleMenuSelect', App::backend()->item_type, 'item_select'),
                        };

                        echo
                        form::hidden('item_type', App::backend()->item_type) .
                        '<p>' . App::nonce()->getFormNonce() .
                        '<input type="submit" name="appendaction" value="' . __('Continue...') . '" /></p>' .
                        '</fieldset>' .
                        '</form>';

                        break;
                    }

                case self::STEP_ATTRIBUTES:

                    // Libellé et description

                    echo
                    '<form id="additem" action="' . App::backend()->getPageURL() . '&amp;add=' . trim((string) self::STEP_ADD) . '" method="post">' .
                    '<fieldset><legend>' . App::backend()->item_type_label . (App::backend()->item_select_label != '' ? ' (' . App::backend()->item_select_label . ')' : '') . '</legend>' .
                    '<p class="field"><label for="item_label" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
                    __('Label of item menu:') . '</label>' .
                    form::field('item_label', 20, 255, [
                        'default'    => App::backend()->item_label,
                        'extra_html' => 'required placeholder="' . __('Label') . '" lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                    ]) .
                    '</p>' .
                    '<p class="field"><label for="item_descr" class="classic">' .
                    __('Description of item menu:') . '</label>' . form::field(
                        'item_descr',
                        30,
                        255,
                        [
                            'default'    => App::backend()->item_descr,
                            'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>' .
                    '<p class="field"><label for="item_url" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
                    __('URL of item menu:') . '</label>' .
                    form::field('item_url', 40, 255, [
                        'default'    => App::backend()->item_url,
                        'extra_html' => 'required placeholder="' . __('URL') . '"',
                    ]) .
                    '</p>' .
                    form::hidden('item_type', App::backend()->item_type) .
                    form::hidden('item_select', App::backend()->item_select) .
                    '<p class="field"><label for="item_descr" class="classic">' .
                    __('Open URL on a new tab') . ':</label>' . form::checkbox('item_targetBlank', 'blank') . '</p>' .
                    '<p>' . App::nonce()->getFormNonce() .
                    '<input type="submit" name="appendaction" value="' . __('Add this item') . '" /></p>' .
                    '</fieldset>' .
                    '</form>';

                    break;
            }
        }

        if (App::backend()->step === self::STEP_LIST) {
            // Formulaire d'activation

            echo
            '<form id="settings" action="' . App::backend()->getPageURL() . '" method="post">' .
            '<p>' . form::checkbox('active', 1, App::backend()->menu_active) .
            '<label class="classic" for="active">' . __('Enable simple menu for this blog') . '</label>' . '</p>' .
            '<p>' . App::nonce()->getFormNonce() .
            '<input type="submit" name="saveconfig" value="' . __('Save configuration') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '</form>';

            // Liste des items

            echo
            '<form id="menuitemsappend" action="' . App::backend()->getPageURL() .
            '&amp;add=' . trim((string) self::STEP_TYPE) . '" method="post">' .
            '<p class="top-add">' . App::nonce()->getFormNonce() .
            '<input class="button add" type="submit" name="appendaction" value="' . __('Add an item') . '" /></p>' .
            '</form>';
        }

        if (count(App::backend()->current_menu)) {
            if (App::backend()->step === self::STEP_LIST) {
                echo
                '<form id="menuitems" action="' . App::backend()->getPageURL() . '" method="post">';
            }

            // Entête table
            echo
            '<div class="table-outer">' .
            '<table class="dragable">' .
            '<caption>' . __('Menu items list') . '</caption>' .
            '<thead>' .
            '<tr>';

            if (App::backend()->step === self::STEP_LIST) {
                echo
                '<th scope="col"></th>' .
                '<th scope="col"></th>';
            }

            echo
            '<th scope="col">' . __('Label') . '</th>' .
            '<th scope="col">' . __('Description') . '</th>' .
            '<th scope="col">' . __('URL') . '</th>' .
            '<th scope="col">' . __('Open URL on a new tab') . '</th>' .
            '</tr>' .
            '</thead>' .
            '<tbody' . (App::backend()->step === self::STEP_LIST ? ' id="menuitemslist"' : '') . '>';
            $count = 0;
            foreach (App::backend()->current_menu as $i => $m) {
                echo
                '<tr class="line" id="l_' . $i . '">';

                //because targetBlank can not exists. This value has been added after this plugin creation.
                if ((isset($m['targetBlank'])) && ($m['targetBlank'])) {
                    $targetBlank    = true;
                    $targetBlankStr = 'X';
                } else {
                    $targetBlank    = false;
                    $targetBlankStr = '';
                }

                if (App::backend()->step === self::STEP_LIST) {
                    $count++;
                    echo
                    '<td class="handle minimal">' .
                    form::number(['order[' . $i . ']'], [
                        'min'        => 1,
                        'max'        => count(App::backend()->current_menu),
                        'default'    => $count,
                        'class'      => 'position',
                        'extra_html' => 'title="' . sprintf(__('position of %s'), Html::escapeHTML($m['label'])) . '"',
                    ]) .
                    form::hidden(['dynorder[]', 'dynorder-' . $i], $i) . '</td>' .
                    '<td class="minimal">' . form::checkbox(['items_selected[]', 'ims-' . $i], $i) . '</td>' .
                    '<td class="nowrap" scope="row">' . form::field(
                        ['items_label[]', 'iml-' . $i],
                        null,
                        255,
                        [
                            'default'    => Html::escapeHTML($m['label']),
                            'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                        ]
                    ) . '</td>' .
                    '<td class="nowrap">' . form::field(
                        ['items_descr[]', 'imd-' . $i],
                        30,
                        255,
                        [
                            'default'    => Html::escapeHTML($m['descr']),
                            'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                        ]
                    ) . '</td>' .
                    '<td class="nowrap">' . form::field(['items_url[]', 'imu-' . $i], 30, 255, Html::escapeHTML($m['url'])) . '</td>' .
                    '<td class="nowrap">' . form::checkbox('items_targetBlank' . $i, 'blank', $targetBlank) . '</td>';
                } else {
                    echo
                    '<td class="nowrap" scope="row">' . Html::escapeHTML($m['label']) . '</td>' .
                    '<td class="nowrap">' . Html::escapeHTML($m['descr']) . '</td>' .
                    '<td class="nowrap">' . Html::escapeHTML($m['url']) . '</td>' .
                    '<td class="nowrap">' . $targetBlankStr . '</td>';
                }
                echo
                '</tr>';
            }
            echo
            '</tbody>' .
            '</table></div>';

            if (App::backend()->step === self::STEP_LIST) {
                echo
                '<div class="two-cols">' .
                '<p class="col">' . form::hidden('im_order', '') . App::nonce()->getFormNonce() .
                '<input type="submit" name="updateaction" value="' . __('Update menu') . '" />' . '</p>' .
                '<p class="col right">' . '<input id="remove-action" type="submit" class="delete" name="removeaction" ' .
                'value="' . __('Delete selected menu items') . '" ' .
                'onclick="return window.confirm(\'' . Html::escapeJS(__('Are you sure you want to remove selected menu items?')) . '\');" />' .
                '</p>' .
                '</div>' .
                '</form>';
            }
        } else {
            echo
            '<p>' . __('No menu items so far.') . '</p>';
        }

        Page::helpBlock('simpleMenu');

        Page::closeModule();
    }
}
