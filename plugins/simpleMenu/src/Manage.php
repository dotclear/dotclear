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
use dcAdminCombos;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

class Manage extends dcNsProcess
{
    // Local constants

    private const STEP_LIST       = 0;
    private const STEP_TYPE       = 1;
    private const STEP_SUBTYPE    = 2;
    private const STEP_ATTRIBUTES = 3;
    private const STEP_ADD        = 4;

    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->admin->page_title = __('Simple menu');

        # Url du blog
        dcCore::app()->admin->blog_url = Html::stripHostURL(dcCore::app()->blog->url);

        # Liste des catégories
        $categories_label                      = [];
        $rs                                    = dcCore::app()->blog->getCategories(['post_type' => 'post']);
        dcCore::app()->admin->categories_combo = dcAdminCombos::getCategoriesCombo($rs, false, true);
        $rs->moveStart();
        while ($rs->fetch()) {
            $categories_label[$rs->cat_url] = Html::escapeHTML($rs->cat_title);
        }
        dcCore::app()->admin->categories_label = $categories_label;

        # Liste des langues utilisées
        dcCore::app()->admin->langs_combo = dcAdminCombos::getLangscombo(
            dcCore::app()->blog->getLangs(['order' => 'asc'])
        );

        # Liste des mois d'archive
        $rs                                = dcCore::app()->blog->getDates(['type' => 'month']);
        dcCore::app()->admin->months_combo = array_merge(
            [__('All months') => '-'],
            dcAdminCombos::getDatesCombo($rs)
        );

        dcCore::app()->admin->first_year = dcCore::app()->admin->last_year = 0;
        while ($rs->fetch()) {
            if ((dcCore::app()->admin->first_year == 0) || ($rs->year() < dcCore::app()->admin->first_year)) {
                dcCore::app()->admin->first_year = $rs->year();
            }

            if ((dcCore::app()->admin->last_year == 0) || ($rs->year() > dcCore::app()->admin->last_year)) {
                dcCore::app()->admin->last_year = $rs->year();
            }
        }
        unset($rs);

        # Liste des pages -- Doit être pris en charge plus tard par le plugin ?
        $pages_combo = [];

        try {
            $rs = dcCore::app()->blog->getPosts(['post_type' => 'page']);
            while ($rs->fetch()) {
                $pages_combo[$rs->post_title] = $rs->getURL();
            }
            unset($rs);
        } catch (Exception $e) {
        }
        dcCore::app()->admin->pages_combo = $pages_combo;

        # Liste des tags -- Doit être pris en charge plus tard par le plugin ?
        $tags_combo = [];

        try {
            $rs                         = dcCore::app()->meta->getMetadata(['meta_type' => 'tag']);
            $tags_combo[__('All tags')] = '-';
            while ($rs->fetch()) {
                $tags_combo[$rs->meta_id] = $rs->meta_id;
            }
            unset($rs);
        } catch (Exception $e) {
        }
        dcCore::app()->admin->tags_combo = $tags_combo;

        # Liste des types d'item de menu
        $items         = new ArrayObject();
        $items['home'] = new ArrayObject([__('Home'), false]);

        if (dcCore::app()->blog->settings->system->static_home) {
            $items['posts'] = new ArrayObject([__('Posts'), false]);
        }

        if ((is_countable(dcCore::app()->admin->langs_combo) ? count(dcCore::app()->admin->langs_combo) : 0) > 1) {
            $items['lang'] = new ArrayObject([__('Language'), true]);
        }
        if (is_countable(dcCore::app()->admin->categories_combo) ? count(dcCore::app()->admin->categories_combo) : 0) {
            $items['category'] = new ArrayObject([__('Category'), true]);
        }
        if (count(dcCore::app()->admin->months_combo) > 1) {
            $items['archive'] = new ArrayObject([__('Archive'), true]);
        }
        if (dcCore::app()->plugins->moduleExists('pages') && count(dcCore::app()->admin->pages_combo)) {
            $items['pages'] = new ArrayObject([__('Page'), true]);
        }
        if (dcCore::app()->plugins->moduleExists('tags') && count(dcCore::app()->admin->tags_combo) > 1) {
            $items['tags'] = new ArrayObject([__('Tags'), true]);
        }

        # --BEHAVIOR-- adminSimpleMenuAddType -- ArrayObject
        # Should add an item to $items[<id>] as an [<label>,<optional step (true or false)>]
        dcCore::app()->callBehavior('adminSimpleMenuAddType', $items);

        $items['special'] = new ArrayObject([__('User defined'), false]);

        $items_combo = [];
        foreach ($items as $k => $v) {
            $items_combo[$v[0]] = $k;
        }

        dcCore::app()->admin->items       = $items;
        dcCore::app()->admin->items_combo = $items_combo;

        # Lecture menu existant
        dcCore::app()->admin->menu = dcCore::app()->blog->settings->system->get('simpleMenu');
        if (!is_array(dcCore::app()->admin->menu)) {
            dcCore::app()->admin->menu = [];
        }

        # Récupération état d'activation du menu
        dcCore::app()->admin->menu_active = (bool) dcCore::app()->blog->settings->system->simpleMenu_active;

        // Saving new configuration
        dcCore::app()->admin->item_type         = '';
        dcCore::app()->admin->item_select       = '';
        dcCore::app()->admin->item_select_label = '';
        dcCore::app()->admin->item_label        = '';
        dcCore::app()->admin->item_descr        = '';
        dcCore::app()->admin->item_url          = '';
        dcCore::app()->admin->item_type_label   = '';

        $item_targetBlank = false;

        // Get current menu
        $menu = dcCore::app()->admin->menu;

        dcCore::app()->admin->step = self::STEP_LIST;
        if (!empty($_POST['saveconfig'])) {
            try {
                dcCore::app()->admin->menu_active = (empty($_POST['active'])) ? false : true;
                dcCore::app()->blog->settings->system->put('simpleMenu_active', dcCore::app()->admin->menu_active, 'boolean');
                dcCore::app()->blog->triggerBlog();

                // All done successfully, return to menu items list
                dcPage::addSuccessNotice(__('Configuration successfully updated.'));
                Http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        } else {
            # Récupération paramètres postés
            dcCore::app()->admin->item_type   = $_POST['item_type']   ?? '';
            dcCore::app()->admin->item_select = $_POST['item_select'] ?? '';
            dcCore::app()->admin->item_label  = $_POST['item_label']  ?? '';
            dcCore::app()->admin->item_descr  = $_POST['item_descr']  ?? '';
            dcCore::app()->admin->item_url    = $_POST['item_url']    ?? '';
            $item_targetBlank                 = isset($_POST['item_targetBlank']) ? (empty($_POST['item_targetBlank']) ? false : true) : false;
            # Traitement
            dcCore::app()->admin->step = (!empty($_GET['add']) ? (int) $_GET['add'] : self::STEP_LIST);
            if ((dcCore::app()->admin->step > self::STEP_ADD) || (dcCore::app()->admin->step < self::STEP_LIST)) {
                dcCore::app()->admin->step = self::STEP_LIST;
            }

            if (dcCore::app()->admin->step !== self::STEP_LIST) {
                # Récupération libellés des choix
                dcCore::app()->admin->item_type_label = isset(dcCore::app()->admin->items[dcCore::app()->admin->item_type]) ? dcCore::app()->admin->items[dcCore::app()->admin->item_type][0] : '';

                switch (dcCore::app()->admin->step) {
                    case self::STEP_TYPE:
                        // First step, menu item type to be selected
                        dcCore::app()->admin->item_type = dcCore::app()->admin->item_select = '';

                        break;
                    case self::STEP_SUBTYPE:
                        if (dcCore::app()->admin->items[dcCore::app()->admin->item_type][1]) {  // @phpstan-ignore-line
                            // Second step (optional), menu item sub-type to be selected
                            dcCore::app()->admin->item_select = '';

                            break;
                        }
                    case self::STEP_ATTRIBUTES:
                        // Third step, menu item attributes to be changed or completed if necessary
                        dcCore::app()->admin->item_select_label = '';
                        dcCore::app()->admin->item_label        = __('Label');
                        dcCore::app()->admin->item_descr        = __('Description');
                        dcCore::app()->admin->item_url          = dcCore::app()->admin->blog_url;
                        switch (dcCore::app()->admin->item_type) {
                            case 'home':
                                dcCore::app()->admin->item_label = __('Home');
                                dcCore::app()->admin->item_descr = dcCore::app()->blog->settings->system->static_home ? __('Home page') : __('Recent posts');

                                break;
                            case 'posts':
                                dcCore::app()->admin->item_label = __('Posts');
                                dcCore::app()->admin->item_descr = __('Recent posts');
                                dcCore::app()->admin->item_url .= dcCore::app()->url->getURLFor('posts');

                                break;
                            case 'lang':
                                dcCore::app()->admin->item_select_label = array_search(dcCore::app()->admin->item_select, dcCore::app()->admin->langs_combo);
                                dcCore::app()->admin->item_label        = dcCore::app()->admin->item_select_label;
                                dcCore::app()->admin->item_descr        = sprintf(__('Switch to %s language'), dcCore::app()->admin->item_select_label);
                                dcCore::app()->admin->item_url .= dcCore::app()->url->getURLFor('lang', dcCore::app()->admin->item_select);

                                break;
                            case 'category':
                                dcCore::app()->admin->item_select_label = dcCore::app()->admin->categories_label[dcCore::app()->admin->item_select];
                                dcCore::app()->admin->item_label        = dcCore::app()->admin->item_select_label;
                                dcCore::app()->admin->item_descr        = __('Recent Posts from this category');
                                dcCore::app()->admin->item_url .= dcCore::app()->url->getURLFor('category', dcCore::app()->admin->item_select);

                                break;
                            case 'archive':
                                dcCore::app()->admin->item_select_label = array_search(dcCore::app()->admin->item_select, dcCore::app()->admin->months_combo);
                                if (dcCore::app()->admin->item_select == '-') {
                                    dcCore::app()->admin->item_label = __('Archives');
                                    dcCore::app()->admin->item_descr = dcCore::app()->admin->first_year . (dcCore::app()->admin->first_year != dcCore::app()->admin->last_year ? ' - ' . dcCore::app()->admin->last_year : '');
                                    dcCore::app()->admin->item_url .= dcCore::app()->url->getURLFor('archive');
                                } else {
                                    dcCore::app()->admin->item_label = dcCore::app()->admin->item_select_label;
                                    dcCore::app()->admin->item_descr = sprintf(__('Posts from %s'), dcCore::app()->admin->item_select_label);
                                    dcCore::app()->admin->item_url .= dcCore::app()->url->getURLFor('archive', substr(dcCore::app()->admin->item_select, 0, 4) . '/' . substr(dcCore::app()->admin->item_select, -2));
                                }

                                break;
                            case 'pages':
                                dcCore::app()->admin->item_select_label = array_search(dcCore::app()->admin->item_select, dcCore::app()->admin->pages_combo);
                                dcCore::app()->admin->item_label        = dcCore::app()->admin->item_select_label;
                                dcCore::app()->admin->item_descr        = '';
                                dcCore::app()->admin->item_url          = Html::stripHostURL(dcCore::app()->admin->item_select);

                                break;
                            case 'tags':
                                dcCore::app()->admin->item_select_label = array_search(dcCore::app()->admin->item_select, dcCore::app()->admin->tags_combo);
                                if (dcCore::app()->admin->item_select == '-') {
                                    dcCore::app()->admin->item_label = __('All tags');
                                    dcCore::app()->admin->item_descr = '';
                                    dcCore::app()->admin->item_url .= dcCore::app()->url->getURLFor('tags');
                                } else {
                                    dcCore::app()->admin->item_label = dcCore::app()->admin->item_select_label;
                                    dcCore::app()->admin->item_descr = sprintf(__('Recent posts for %s tag'), dcCore::app()->admin->item_select_label);
                                    dcCore::app()->admin->item_url .= dcCore::app()->url->getURLFor('tag', dcCore::app()->admin->item_select);
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
                                    dcCore::app()->admin->item_url,
                                    dcCore::app()->admin->item_descr,
                                    dcCore::app()->admin->item_label,
                                    dcCore::app()->admin->item_select_label,
                                ];
                                dcCore::app()->callBehavior(
                                    'adminSimpleMenuBeforeEdit',
                                    dcCore::app()->admin->item_type,
                                    dcCore::app()->admin->item_select,
                                    [
                                        &$item_label,
                                        &$item_descr,
                                        &$item_url,
                                        &$item_select_label,
                                    ]
                                );
                                [
                                    dcCore::app()->admin->item_url,
                                    dcCore::app()->admin->item_descr,
                                    dcCore::app()->admin->item_label,
                                    dcCore::app()->admin->item_select_label,
                                ] = [
                                    $item_url, $item_descr, $item_label, $item_select_label,
                                ];

                                break;
                        }

                        break;
                    case self::STEP_ADD:
                        // Fourth step, menu item to be added
                        try {
                            if ((dcCore::app()->admin->item_label != '') && (dcCore::app()->admin->item_url != '')) {
                                // Add new item menu in menu array
                                $menu[] = [
                                    'label'       => dcCore::app()->admin->item_label,
                                    'descr'       => dcCore::app()->admin->item_descr,
                                    'url'         => dcCore::app()->admin->item_url,
                                    'targetBlank' => $item_targetBlank,
                                ];

                                // Save menu in blog settings
                                dcCore::app()->blog->settings->system->put('simpleMenu', $menu);
                                dcCore::app()->blog->triggerBlog();

                                // All done successfully, return to menu items list
                                dcPage::addSuccessNotice(__('Menu item has been successfully added.'));
                                Http::redirect(dcCore::app()->admin->getPageURL());
                            } else {
                                dcCore::app()->admin->step              = self::STEP_ATTRIBUTES;
                                dcCore::app()->admin->item_select_label = dcCore::app()->admin->item_label;
                                dcPage::addErrorNotice(__('Label and URL of menu item are mandatory.'));
                            }
                        } catch (Exception $e) {
                            dcCore::app()->error->add($e->getMessage());
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
                            dcCore::app()->blog->settings->system->put('simpleMenu', $menu);
                            dcCore::app()->blog->triggerBlog();

                            // All done successfully, return to menu items list
                            dcPage::addSuccessNotice(__('Menu items have been successfully removed.'));
                            Http::redirect(dcCore::app()->admin->getPageURL());
                        } else {
                            throw new Exception(__('No menu items selected.'));
                        }
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
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

                        if (dcCore::app()->auth->user_prefs->accessibility->nodragdrop) {
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
                        dcCore::app()->blog->settings->system->put('simpleMenu', $menu);
                        dcCore::app()->blog->triggerBlog();

                        // All done successfully, return to menu items list
                        dcPage::addSuccessNotice(__('Menu items have been successfully updated.'));
                        Http::redirect(dcCore::app()->admin->getPageURL());
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
                    }
                }
            }
        }

        // Store current menu (used in render)
        dcCore::app()->admin->menu = $menu;

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $head = '';
        if (!dcCore::app()->auth->user_prefs->accessibility->nodragdrop) {
            $head .= dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
                dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
                My::jsLoad('simplemenu.js');
        }
        $head .= dcPage::jsConfirmClose('settings', 'menuitemsappend', 'additem', 'menuitems');

        dcPage::openModule(dcCore::app()->admin->page_title, $head);

        $step_label = '';
        if (dcCore::app()->admin->step) {
            switch (dcCore::app()->admin->step) {
                case self::STEP_TYPE:
                    $step_label = __('Step #1');

                    break;
                case self::STEP_SUBTYPE:
                    if (dcCore::app()->admin->items[dcCore::app()->admin->item_type][1]) {
                        $step_label = __('Step #2');

                        break;
                    }
                case self::STEP_ATTRIBUTES:
                    if (dcCore::app()->admin->items[dcCore::app()->admin->item_type][1]) {
                        $step_label = __('Step #3');
                    } else {
                        $step_label = __('Step #2');
                    }

                    break;
            }
            echo
            dcPage::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    dcCore::app()->admin->page_title            => dcCore::app()->admin->getPageURL(),
                    __('Add item')                              => '',
                    $step_label                                 => '',
                ],
                [
                    'hl_pos' => -2,
                ]
            ) .
            dcPage::notices();
        } else {
            echo
            dcPage::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    dcCore::app()->admin->page_title            => '',
                ]
            ) .
            dcPage::notices();
        }

        if (dcCore::app()->admin->step !== self::STEP_LIST) {
            // Formulaire d'ajout d'un item
            switch (dcCore::app()->admin->step) {
                case self::STEP_TYPE:

                    // Selection du type d'item

                    echo
                    '<form id="additem" action="' . dcCore::app()->admin->getPageURL() . '&amp;add=' . trim((string) self::STEP_SUBTYPE) . '" method="post">' .
                    '<fieldset><legend>' . __('Select type') . '</legend>' .
                    '<p class="field"><label for="item_type" class="classic">' . __('Type of item menu:') . '</label>' .
                    form::combo('item_type', dcCore::app()->admin->items_combo) . '</p>' .
                    '<p>' . dcCore::app()->formNonce() .
                    '<input type="submit" name="appendaction" value="' . __('Continue...') . '" />' . '</p>' .
                    '</fieldset>' .
                    '</form>';

                    break;

                case self::STEP_SUBTYPE:

                    if (dcCore::app()->admin->items[dcCore::app()->admin->item_type][1]) {
                        // Choix à faire
                        echo
                        '<form id="additem" action="' . dcCore::app()->admin->getPageURL() .
                        '&amp;add=' . trim((string) self::STEP_ATTRIBUTES) . '" method="post">' .
                        '<fieldset><legend>' . dcCore::app()->admin->item_type_label . '</legend>';
                        switch (dcCore::app()->admin->item_type) {
                            case 'lang':
                                echo
                                '<p class="field"><label for="item_select" class="classic">' . __('Select language:') . '</label>' .
                                form::combo('item_select', dcCore::app()->admin->langs_combo) .
                                '</p>';

                                break;
                            case 'category':
                                echo
                                '<p class="field"><label for="item_select" class="classic">' . __('Select category:') . '</label>' .
                                form::combo('item_select', dcCore::app()->admin->categories_combo) .
                                '</p>';

                                break;
                            case 'archive':
                                echo
                                '<p class="field"><label for="item_select" class="classic">' . __('Select month (if necessary):') . '</label>' .
                                form::combo('item_select', dcCore::app()->admin->months_combo) .
                                '</p>';

                                break;
                            case 'pages':
                                echo
                                '<p class="field"><label for="item_select" class="classic">' . __('Select page:') . '</label>' .
                                form::combo('item_select', dcCore::app()->admin->pages_combo) .
                                '</p>';

                                break;
                            case 'tags':
                                echo
                                '<p class="field"><label for="item_select" class="classic">' . __('Select tag (if necessary):') . '</label>' .
                                form::combo('item_select', dcCore::app()->admin->tags_combo) .
                                '</p>';

                                break;
                            default:
                                echo
                                # --BEHAVIOR-- adminSimpleMenuSelect -- string, string
                                # Optional step once dcCore::app()->admin->item_type known : should provide a field using 'item_select' as id, included in a <p class="field"></p> and don't forget the <label> ;-)
                                dcCore::app()->callBehavior('adminSimpleMenuSelect', dcCore::app()->admin->item_type, 'item_select');
                        }
                        echo
                        form::hidden('item_type', dcCore::app()->admin->item_type) .
                        '<p>' . dcCore::app()->formNonce() .
                        '<input type="submit" name="appendaction" value="' . __('Continue...') . '" /></p>' .
                        '</fieldset>' .
                        '</form>';

                        break;
                    }

                case self::STEP_ATTRIBUTES:

                    // Libellé et description

                    echo
                    '<form id="additem" action="' . dcCore::app()->admin->getPageURL() . '&amp;add=' . trim((string) self::STEP_ADD) . '" method="post">' .
                    '<fieldset><legend>' . dcCore::app()->admin->item_type_label . (dcCore::app()->admin->item_select_label != '' ? ' (' . dcCore::app()->admin->item_select_label . ')' : '') . '</legend>' .
                    '<p class="field"><label for="item_label" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
                    __('Label of item menu:') . '</label>' .
                    form::field('item_label', 20, 255, [
                        'default'    => dcCore::app()->admin->item_label,
                        'extra_html' => 'required placeholder="' . __('Label') . '" lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                    ]) .
                    '</p>' .
                    '<p class="field"><label for="item_descr" class="classic">' .
                    __('Description of item menu:') . '</label>' . form::field(
                        'item_descr',
                        30,
                        255,
                        [
                            'default'    => dcCore::app()->admin->item_descr,
                            'extra_html' => 'lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>' .
                    '<p class="field"><label for="item_url" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
                    __('URL of item menu:') . '</label>' .
                    form::field('item_url', 40, 255, [
                        'default'    => dcCore::app()->admin->item_url,
                        'extra_html' => 'required placeholder="' . __('URL') . '"',
                    ]) .
                    '</p>' .
                    form::hidden('item_type', dcCore::app()->admin->item_type) .
                    form::hidden('item_select', dcCore::app()->admin->item_select) .
                    '<p class="field"><label for="item_descr" class="classic">' .
                    __('Open URL on a new tab') . ':</label>' . form::checkbox('item_targetBlank', 'blank') . '</p>' .
                    '<p>' . dcCore::app()->formNonce() .
                    '<input type="submit" name="appendaction" value="' . __('Add this item') . '" /></p>' .
                    '</fieldset>' .
                    '</form>';

                    break;
            }
        }

        if (dcCore::app()->admin->step === self::STEP_LIST) {
            // Formulaire d'activation

            echo
            '<form id="settings" action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .
            '<p>' . form::checkbox('active', 1, dcCore::app()->admin->menu_active) .
            '<label class="classic" for="active">' . __('Enable simple menu for this blog') . '</label>' . '</p>' .
            '<p>' . dcCore::app()->formNonce() .
            '<input type="submit" name="saveconfig" value="' . __('Save configuration') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            '</p>' .
            '</form>';

            // Liste des items

            echo
            '<form id="menuitemsappend" action="' . dcCore::app()->admin->getPageURL() .
            '&amp;add=' . trim((string) self::STEP_TYPE) . '" method="post">' .
            '<p class="top-add">' . dcCore::app()->formNonce() .
            '<input class="button add" type="submit" name="appendaction" value="' . __('Add an item') . '" /></p>' .
            '</form>';
        }

        if (is_countable(dcCore::app()->admin->menu) ? count(dcCore::app()->admin->menu) : 0) {
            if (dcCore::app()->admin->step === self::STEP_LIST) {
                echo
                '<form id="menuitems" action="' . dcCore::app()->admin->getPageURL() . '" method="post">';
            }

            // Entête table
            echo
            '<div class="table-outer">' .
            '<table class="dragable">' .
            '<caption>' . __('Menu items list') . '</caption>' .
            '<thead>' .
            '<tr>';

            if (dcCore::app()->admin->step === self::STEP_LIST) {
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
            '<tbody' . (dcCore::app()->admin->step === self::STEP_LIST ? ' id="menuitemslist"' : '') . '>';
            $count = 0;
            foreach (dcCore::app()->admin->menu as $i => $m) {
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

                if (dcCore::app()->admin->step === self::STEP_LIST) {
                    $count++;
                    echo
                    '<td class="handle minimal">' .
                    form::number(['order[' . $i . ']'], [
                        'min'        => 1,
                        'max'        => is_countable(dcCore::app()->admin->menu) ? count(dcCore::app()->admin->menu) : 0,
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
                            'extra_html' => 'lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                        ]
                    ) . '</td>' .
                    '<td class="nowrap">' . form::field(
                        ['items_descr[]', 'imd-' . $i],
                        30,
                        255,
                        [
                            'default'    => Html::escapeHTML($m['descr']),
                            'extra_html' => 'lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
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

            if (dcCore::app()->admin->step === self::STEP_LIST) {
                echo
                '<div class="two-cols">' .
                '<p class="col">' . form::hidden('im_order', '') . dcCore::app()->formNonce() .
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

        dcPage::helpBlock('simpleMenu');

        dcPage::closeModule();
    }
}
