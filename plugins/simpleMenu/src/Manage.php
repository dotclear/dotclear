<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use ArrayObject;
use dcCore;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

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

        Core::backend()->page_title = __('Simple menu');

        # Url du blog
        Core::backend()->blog_url = Html::stripHostURL(Core::blog()->url);

        # Liste des catégories
        $categories_label                 = [];
        $rs                               = Core::blog()->getCategories(['post_type' => 'post']);
        Core::backend()->categories_combo = Combos::getCategoriesCombo($rs, false, true);
        $rs->moveStart();
        while ($rs->fetch()) {
            $categories_label[$rs->cat_url] = Html::escapeHTML($rs->cat_title);
        }
        Core::backend()->categories_label = $categories_label;

        # Liste des langues utilisées
        Core::backend()->langs_combo = Combos::getLangscombo(
            Core::blog()->getLangs(['order' => 'asc'])
        );

        # Liste des mois d'archive
        $rs                           = Core::blog()->getDates(['type' => 'month']);
        Core::backend()->months_combo = array_merge(
            [__('All months') => '-'],
            Combos::getDatesCombo($rs)
        );

        Core::backend()->first_year = Core::backend()->last_year = 0;
        while ($rs->fetch()) {
            if ((Core::backend()->first_year == 0) || ($rs->year() < Core::backend()->first_year)) {
                Core::backend()->first_year = $rs->year();
            }

            if ((Core::backend()->last_year == 0) || ($rs->year() > Core::backend()->last_year)) {
                Core::backend()->last_year = $rs->year();
            }
        }
        unset($rs);

        # Liste des pages -- Doit être pris en charge plus tard par le plugin ?
        $pages_combo = [];

        try {
            $rs = Core::blog()->getPosts(['post_type' => 'page']);
            while ($rs->fetch()) {
                $pages_combo[$rs->post_title] = $rs->getURL();
            }
            unset($rs);
        } catch (Exception $e) {
        }
        Core::backend()->pages_combo = $pages_combo;

        # Liste des tags -- Doit être pris en charge plus tard par le plugin ?
        $tags_combo = [];

        try {
            $rs                         = Core::meta()->getMetadata(['meta_type' => 'tag']);
            $tags_combo[__('All tags')] = '-';
            while ($rs->fetch()) {
                $tags_combo[$rs->meta_id] = $rs->meta_id;
            }
            unset($rs);
        } catch (Exception $e) {
        }
        Core::backend()->tags_combo = $tags_combo;

        # Liste des types d'item de menu
        $items         = new ArrayObject();
        $items['home'] = new ArrayObject([__('Home'), false]);

        if (Core::blog()->settings->system->static_home) {
            $items['posts'] = new ArrayObject([__('Posts'), false]);
        }

        if (count(Core::backend()->langs_combo) > 1) {
            $items['lang'] = new ArrayObject([__('Language'), true]);
        }
        if (count(Core::backend()->categories_combo)) {
            $items['category'] = new ArrayObject([__('Category'), true]);
        }
        if (count(Core::backend()->months_combo) > 1) {
            $items['archive'] = new ArrayObject([__('Archive'), true]);
        }
        if (Core::plugins()->moduleExists('pages') && count(Core::backend()->pages_combo)) {
            $items['pages'] = new ArrayObject([__('Page'), true]);
        }
        if (Core::plugins()->moduleExists('tags') && count(Core::backend()->tags_combo) > 1) {
            $items['tags'] = new ArrayObject([__('Tags'), true]);
        }

        # --BEHAVIOR-- adminSimpleMenuAddType -- ArrayObject
        # Should add an item to $items[<id>] as an [<label>,<optional step (true or false)>]
        Core::behavior()->callBehavior('adminSimpleMenuAddType', $items);

        $items['special'] = new ArrayObject([__('User defined'), false]);

        $items_combo = [];
        foreach ($items as $k => $v) {
            $items_combo[$v[0]] = $k;
        }

        Core::backend()->items       = $items;
        Core::backend()->items_combo = $items_combo;

        # Lecture menu existant
        Core::backend()->current_menu = Core::blog()->settings->system->get('simpleMenu');
        if (!is_array(Core::backend()->current_menu)) {
            Core::backend()->current_menu = [];
        }

        # Récupération état d'activation du menu
        Core::backend()->menu_active = (bool) Core::blog()->settings->system->simpleMenu_active;

        // Saving new configuration
        Core::backend()->item_type         = '';
        Core::backend()->item_select       = '';
        Core::backend()->item_select_label = '';
        Core::backend()->item_label        = '';
        Core::backend()->item_descr        = '';
        Core::backend()->item_url          = '';
        Core::backend()->item_type_label   = '';

        $item_targetBlank = false;

        // Get current menu
        $menu = Core::backend()->current_menu;

        Core::backend()->step = self::STEP_LIST;
        if (!empty($_POST['saveconfig'])) {
            try {
                Core::backend()->menu_active = (empty($_POST['active'])) ? false : true;
                Core::blog()->settings->system->put('simpleMenu_active', Core::backend()->menu_active, 'boolean');
                Core::blog()->triggerBlog();

                // All done successfully, return to menu items list
                Notices::addSuccessNotice(__('Configuration successfully updated.'));
                My::redirect();
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        } else {
            # Récupération paramètres postés
            Core::backend()->item_type   = $_POST['item_type']   ?? '';
            Core::backend()->item_select = $_POST['item_select'] ?? '';
            Core::backend()->item_label  = $_POST['item_label']  ?? '';
            Core::backend()->item_descr  = $_POST['item_descr']  ?? '';
            Core::backend()->item_url    = $_POST['item_url']    ?? '';
            $item_targetBlank            = isset($_POST['item_targetBlank']) ? (empty($_POST['item_targetBlank']) ? false : true) : false;
            # Traitement
            Core::backend()->step = (!empty($_GET['add']) ? (int) $_GET['add'] : self::STEP_LIST);
            if ((Core::backend()->step > self::STEP_ADD) || (Core::backend()->step < self::STEP_LIST)) {
                Core::backend()->step = self::STEP_LIST;
            }

            if (Core::backend()->step !== self::STEP_LIST) {
                # Récupération libellés des choix
                Core::backend()->item_type_label = isset(Core::backend()->items[Core::backend()->item_type]) ? Core::backend()->items[Core::backend()->item_type][0] : '';

                switch (Core::backend()->step) {
                    case self::STEP_TYPE:
                        // First step, menu item type to be selected
                        Core::backend()->item_type = Core::backend()->item_select = '';

                        break;
                    case self::STEP_SUBTYPE:
                        if (Core::backend()->items[Core::backend()->item_type][1]) {  // @phpstan-ignore-line
                            // Second step (optional), menu item sub-type to be selected
                            Core::backend()->item_select = '';

                            break;
                        }
                    case self::STEP_ATTRIBUTES:
                        // Third step, menu item attributes to be changed or completed if necessary
                        Core::backend()->item_select_label = '';
                        Core::backend()->item_label        = __('Label');
                        Core::backend()->item_descr        = __('Description');
                        Core::backend()->item_url          = Core::backend()->blog_url;
                        switch (Core::backend()->item_type) {
                            case 'home':
                                Core::backend()->item_label = __('Home');
                                Core::backend()->item_descr = Core::blog()->settings->system->static_home ? __('Home page') : __('Recent posts');

                                break;
                            case 'posts':
                                Core::backend()->item_label = __('Posts');
                                Core::backend()->item_descr = __('Recent posts');
                                Core::backend()->item_url .= Core::url()->getURLFor('posts');

                                break;
                            case 'lang':
                                Core::backend()->item_select_label = array_search(Core::backend()->item_select, Core::backend()->langs_combo);
                                Core::backend()->item_label        = Core::backend()->item_select_label;
                                Core::backend()->item_descr        = sprintf(__('Switch to %s language'), Core::backend()->item_select_label);
                                Core::backend()->item_url .= Core::url()->getURLFor('lang', Core::backend()->item_select);

                                break;
                            case 'category':
                                Core::backend()->item_select_label = Core::backend()->categories_label[Core::backend()->item_select];
                                Core::backend()->item_label        = Core::backend()->item_select_label;
                                Core::backend()->item_descr        = __('Recent Posts from this category');
                                Core::backend()->item_url .= Core::url()->getURLFor('category', Core::backend()->item_select);

                                break;
                            case 'archive':
                                Core::backend()->item_select_label = array_search(Core::backend()->item_select, Core::backend()->months_combo);
                                if (Core::backend()->item_select == '-') {
                                    Core::backend()->item_label = __('Archives');
                                    Core::backend()->item_descr = Core::backend()->first_year . (Core::backend()->first_year != Core::backend()->last_year ? ' - ' . Core::backend()->last_year : '');
                                    Core::backend()->item_url .= Core::url()->getURLFor('archive');
                                } else {
                                    Core::backend()->item_label = Core::backend()->item_select_label;
                                    Core::backend()->item_descr = sprintf(__('Posts from %s'), Core::backend()->item_select_label);
                                    Core::backend()->item_url .= Core::url()->getURLFor('archive', substr(Core::backend()->item_select, 0, 4) . '/' . substr(Core::backend()->item_select, -2));
                                }

                                break;
                            case 'pages':
                                Core::backend()->item_select_label = array_search(Core::backend()->item_select, Core::backend()->pages_combo);
                                Core::backend()->item_label        = Core::backend()->item_select_label;
                                Core::backend()->item_descr        = '';
                                Core::backend()->item_url          = Html::stripHostURL(Core::backend()->item_select);

                                break;
                            case 'tags':
                                Core::backend()->item_select_label = array_search(Core::backend()->item_select, Core::backend()->tags_combo);
                                if (Core::backend()->item_select == '-') {
                                    Core::backend()->item_label = __('All tags');
                                    Core::backend()->item_descr = '';
                                    Core::backend()->item_url .= Core::url()->getURLFor('tags');
                                } else {
                                    Core::backend()->item_label = Core::backend()->item_select_label;
                                    Core::backend()->item_descr = sprintf(__('Recent posts for %s tag'), Core::backend()->item_select_label);
                                    Core::backend()->item_url .= Core::url()->getURLFor('tag', Core::backend()->item_select);
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
                                    Core::backend()->item_url,
                                    Core::backend()->item_descr,
                                    Core::backend()->item_label,
                                    Core::backend()->item_select_label,
                                ];
                                Core::behavior()->callBehavior(
                                    'adminSimpleMenuBeforeEdit',
                                    Core::backend()->item_type,
                                    Core::backend()->item_select,
                                    [
                                        &$item_label,
                                        &$item_descr,
                                        &$item_url,
                                        &$item_select_label,
                                    ]
                                );
                                [
                                    Core::backend()->item_url,
                                    Core::backend()->item_descr,
                                    Core::backend()->item_label,
                                    Core::backend()->item_select_label,
                                ] = [
                                    $item_url, $item_descr, $item_label, $item_select_label,
                                ];

                                break;
                        }

                        break;
                    case self::STEP_ADD:
                        // Fourth step, menu item to be added
                        try {
                            if ((Core::backend()->item_label != '') && (Core::backend()->item_url != '')) {
                                // Add new item menu in menu array
                                $menu[] = [
                                    'label'       => Core::backend()->item_label,
                                    'descr'       => Core::backend()->item_descr,
                                    'url'         => Core::backend()->item_url,
                                    'targetBlank' => $item_targetBlank,
                                ];

                                // Save menu in blog settings
                                Core::blog()->settings->system->put('simpleMenu', $menu);
                                Core::blog()->triggerBlog();

                                // All done successfully, return to menu items list
                                Notices::addSuccessNotice(__('Menu item has been successfully added.'));
                                My::redirect();
                            } else {
                                Core::backend()->step              = self::STEP_ATTRIBUTES;
                                Core::backend()->item_select_label = Core::backend()->item_label;
                                Notices::addErrorNotice(__('Label and URL of menu item are mandatory.'));
                            }
                        } catch (Exception $e) {
                            Core::error()->add($e->getMessage());
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
                            Core::blog()->settings->system->put('simpleMenu', $menu);
                            Core::blog()->triggerBlog();

                            // All done successfully, return to menu items list
                            Notices::addSuccessNotice(__('Menu items have been successfully removed.'));
                            My::redirect();
                        } else {
                            throw new Exception(__('No menu items selected.'));
                        }
                    } catch (Exception $e) {
                        Core::error()->add($e->getMessage());
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

                        if (Core::auth()->user_prefs->accessibility->nodragdrop) {
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
                        Core::blog()->settings->system->put('simpleMenu', $menu);
                        Core::blog()->triggerBlog();

                        // All done successfully, return to menu items list
                        Notices::addSuccessNotice(__('Menu items have been successfully updated.'));
                        My::redirect();
                    } catch (Exception $e) {
                        Core::error()->add($e->getMessage());
                    }
                }
            }
        }

        // Store current menu (used in render)
        Core::backend()->current_menu = $menu;

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $head = '';
        if (!Core::auth()->user_prefs->accessibility->nodragdrop) {
            $head .= Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
                My::jsLoad('simplemenu');
        }
        $head .= Page::jsConfirmClose('settings', 'menuitemsappend', 'additem', 'menuitems');

        Page::openModule(Core::backend()->page_title, $head);

        $step_label = '';
        if (Core::backend()->step) {
            switch (Core::backend()->step) {
                case self::STEP_TYPE:
                    $step_label = __('Step #1');

                    break;
                case self::STEP_SUBTYPE:
                    if (Core::backend()->items[Core::backend()->item_type][1]) {
                        $step_label = __('Step #2');

                        break;
                    }
                case self::STEP_ATTRIBUTES:
                    if (Core::backend()->items[Core::backend()->item_type][1]) {
                        $step_label = __('Step #3');
                    } else {
                        $step_label = __('Step #2');
                    }

                    break;
            }
            echo
            Page::breadcrumb(
                [
                    Html::escapeHTML(Core::blog()->name) => '',
                    Core::backend()->page_title          => Core::backend()->getPageURL(),
                    __('Add item')                       => '',
                    $step_label                          => '',
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
                    Html::escapeHTML(Core::blog()->name) => '',
                    Core::backend()->page_title          => '',
                ]
            ) .
            Notices::getNotices();
        }

        if (Core::backend()->step !== self::STEP_LIST) {
            // Formulaire d'ajout d'un item
            switch (Core::backend()->step) {
                case self::STEP_TYPE:

                    // Selection du type d'item

                    echo
                    '<form id="additem" action="' . Core::backend()->getPageURL() . '&amp;add=' . trim((string) self::STEP_SUBTYPE) . '" method="post">' .
                    '<fieldset><legend>' . __('Select type') . '</legend>' .
                    '<p class="field"><label for="item_type" class="classic">' . __('Type of item menu:') . '</label>' .
                    form::combo('item_type', Core::backend()->items_combo) . '</p>' .
                    '<p>' . Core::nonce()->getFormNonce() .
                    '<input type="submit" name="appendaction" value="' . __('Continue...') . '" />' . '</p>' .
                    '</fieldset>' .
                    '</form>';

                    break;

                case self::STEP_SUBTYPE:

                    if (Core::backend()->items[Core::backend()->item_type][1]) {
                        // Choix à faire
                        echo
                        '<form id="additem" action="' . Core::backend()->getPageURL() .
                        '&amp;add=' . trim((string) self::STEP_ATTRIBUTES) . '" method="post">' .
                        '<fieldset><legend>' . Core::backend()->item_type_label . '</legend>';

                        echo match (Core::backend()->item_type) {
                            'lang' => '<p class="field"><label for="item_select" class="classic">' . __('Select language:') . '</label>' .
                                form::combo('item_select', Core::backend()->langs_combo) .
                                '</p>',
                            'category' => '<p class="field"><label for="item_select" class="classic">' . __('Select category:') . '</label>' .
                                form::combo('item_select', Core::backend()->categories_combo) .
                                '</p>',
                            'archive' => '<p class="field"><label for="item_select" class="classic">' . __('Select month (if necessary):') . '</label>' .
                                form::combo('item_select', Core::backend()->months_combo) .
                                '</p>',
                            'pages' => '<p class="field"><label for="item_select" class="classic">' . __('Select page:') . '</label>' .
                                form::combo('item_select', Core::backend()->pages_combo) .
                                '</p>',
                            'tags' => '<p class="field"><label for="item_select" class="classic">' . __('Select tag (if necessary):') . '</label>' .
                                form::combo('item_select', Core::backend()->tags_combo) .
                                '</p>',
                            default => # --BEHAVIOR-- adminSimpleMenuSelect -- string, string
                                # Optional step once Core::backend()->item_type known : should provide a field using 'item_select' as id, included in a <p class="field"></p> and don't forget the <label> ;-)
                                Core::behavior()->callBehavior('adminSimpleMenuSelect', Core::backend()->item_type, 'item_select'),
                        };

                        echo
                        form::hidden('item_type', Core::backend()->item_type) .
                        '<p>' . Core::nonce()->getFormNonce() .
                        '<input type="submit" name="appendaction" value="' . __('Continue...') . '" /></p>' .
                        '</fieldset>' .
                        '</form>';

                        break;
                    }

                case self::STEP_ATTRIBUTES:

                    // Libellé et description

                    echo
                    '<form id="additem" action="' . Core::backend()->getPageURL() . '&amp;add=' . trim((string) self::STEP_ADD) . '" method="post">' .
                    '<fieldset><legend>' . Core::backend()->item_type_label . (Core::backend()->item_select_label != '' ? ' (' . Core::backend()->item_select_label . ')' : '') . '</legend>' .
                    '<p class="field"><label for="item_label" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
                    __('Label of item menu:') . '</label>' .
                    form::field('item_label', 20, 255, [
                        'default'    => Core::backend()->item_label,
                        'extra_html' => 'required placeholder="' . __('Label') . '" lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"',
                    ]) .
                    '</p>' .
                    '<p class="field"><label for="item_descr" class="classic">' .
                    __('Description of item menu:') . '</label>' . form::field(
                        'item_descr',
                        30,
                        255,
                        [
                            'default'    => Core::backend()->item_descr,
                            'extra_html' => 'lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>' .
                    '<p class="field"><label for="item_url" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
                    __('URL of item menu:') . '</label>' .
                    form::field('item_url', 40, 255, [
                        'default'    => Core::backend()->item_url,
                        'extra_html' => 'required placeholder="' . __('URL') . '"',
                    ]) .
                    '</p>' .
                    form::hidden('item_type', Core::backend()->item_type) .
                    form::hidden('item_select', Core::backend()->item_select) .
                    '<p class="field"><label for="item_descr" class="classic">' .
                    __('Open URL on a new tab') . ':</label>' . form::checkbox('item_targetBlank', 'blank') . '</p>' .
                    '<p>' . Core::nonce()->getFormNonce() .
                    '<input type="submit" name="appendaction" value="' . __('Add this item') . '" /></p>' .
                    '</fieldset>' .
                    '</form>';

                    break;
            }
        }

        if (Core::backend()->step === self::STEP_LIST) {
            // Formulaire d'activation

            echo
            '<form id="settings" action="' . Core::backend()->getPageURL() . '" method="post">' .
            '<p>' . form::checkbox('active', 1, Core::backend()->menu_active) .
            '<label class="classic" for="active">' . __('Enable simple menu for this blog') . '</label>' . '</p>' .
            '<p>' . Core::nonce()->getFormNonce() .
            '<input type="submit" name="saveconfig" value="' . __('Save configuration') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '</form>';

            // Liste des items

            echo
            '<form id="menuitemsappend" action="' . Core::backend()->getPageURL() .
            '&amp;add=' . trim((string) self::STEP_TYPE) . '" method="post">' .
            '<p class="top-add">' . Core::nonce()->getFormNonce() .
            '<input class="button add" type="submit" name="appendaction" value="' . __('Add an item') . '" /></p>' .
            '</form>';
        }

        if (count(Core::backend()->current_menu)) {
            if (Core::backend()->step === self::STEP_LIST) {
                echo
                '<form id="menuitems" action="' . Core::backend()->getPageURL() . '" method="post">';
            }

            // Entête table
            echo
            '<div class="table-outer">' .
            '<table class="dragable">' .
            '<caption>' . __('Menu items list') . '</caption>' .
            '<thead>' .
            '<tr>';

            if (Core::backend()->step === self::STEP_LIST) {
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
            '<tbody' . (Core::backend()->step === self::STEP_LIST ? ' id="menuitemslist"' : '') . '>';
            $count = 0;
            foreach (Core::backend()->current_menu as $i => $m) {
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

                if (Core::backend()->step === self::STEP_LIST) {
                    $count++;
                    echo
                    '<td class="handle minimal">' .
                    form::number(['order[' . $i . ']'], [
                        'min'        => 1,
                        'max'        => count(Core::backend()->current_menu),
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
                            'extra_html' => 'lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"',
                        ]
                    ) . '</td>' .
                    '<td class="nowrap">' . form::field(
                        ['items_descr[]', 'imd-' . $i],
                        30,
                        255,
                        [
                            'default'    => Html::escapeHTML($m['descr']),
                            'extra_html' => 'lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"',
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

            if (Core::backend()->step === self::STEP_LIST) {
                echo
                '<div class="two-cols">' .
                '<p class="col">' . form::hidden('im_order', '') . Core::nonce()->getFormNonce() .
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
