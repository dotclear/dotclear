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
use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup simpleMenu
 *
 * @phpstan-import-type TSimpleMenuItem from MenuItem
 */
class Manage
{
    use TraitProcess;

    // Local constants

    private const STEP_LIST       = 0;
    private const STEP_TYPE       = 1;
    private const STEP_SUBTYPE    = 2;
    private const STEP_ATTRIBUTES = 3;
    private const STEP_ADD        = 4;

    // Local properties (static to be persistant between call of init, render and process methods)

    private static SimpleMenu $simple_menu;
    private static bool $menu_active;

    /**
     * Current step (see possible constant values above)
     */
    private static int $step;

    /**
     * Current type of menu item
     */
    private static string $item_type;

    /**
     * Current label of type of menu item
     */
    private static string $item_type_label;

    /**
     * Additional choice identifier, if any
     */
    private static string $item_select;

    /**
     * Additional choice label, if any
     */
    private static string $item_select_label;

    /**
     * Current label for menu item
     */
    private static string $item_label;

    /**
     * Current description for menu item
     */
    private static string $item_descr;

    /**
     * Current URL for menu item
     */
    private static string $item_url;

    /**
     * Current data attribute for menu item
     */
    private static string $item_data;

    /**
     * List of Option to use with category select element
     *
     * @var Option[] $categories_combo
     */
    private static array $categories_combo;

    /**
     * List of label for each category URL
     *
     * @var array<string, string> $categories_label
     */
    private static array $categories_label;

    /**
     * List of languages
     *
     * @var array<array-key, OptGroup|Option> $langs_combo
     */
    private static array $langs_combo;

    /**
     * List of langs (code) used in blog
     *
     * @var array<string, string> $langs_used
     */
    private static array $langs_used;

    /**
     * List of archive months
     *
     * @var array<array-key, OptGroup|Option> $months_combo
     */
    private static array $months_combo;

    /**
     * List of archive months
     *
     * @var array<string, string> $months_values
     */
    private static array $months_values;

    /**
     * First year of archive
     */
    private static int $first_year;

    /**
     * Last year of archive
     */
    private static int $last_year;

    /**
     * List of available pages
     *
     * @var array<string, string> $pages_combo
     */
    private static array $pages_combo;

    /**
     * List of available pages
     *
     * @var array<string, string> $tags_combo
     */
    private static array $tags_combo;

    /**
     * List of available type of simple menu item
     *
     * @var array<string, string> $items_combo
     */
    private static array $items_combo;

    /**
     * Definition of available types of simple menu item
     *
     * The key is type (string)
     *
     * The value is an array of two elements:
     *
     * - a label for select item,
     * - a flag set to true if there is an additional choice to do
     *
     * @var array<string, array{0: string, 1: bool}> $items
     */
    private static array $items;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    private static function setup(): void
    {
        // Init static properties if necessary

        if (!isset(self::$simple_menu)) {
            // Get current menu definition setting
            self::$simple_menu = SimpleMenu::load(My::WORKSPACE, My::SETTING_MENU);
        }

        if (!isset(self::$menu_active)) {
            // Get current menu activation setting
            self::$menu_active = App::blog()->settings()->get(My::WORKSPACE)->getBool(My::SETTING_ACTIVE, false);
        }

        if (!isset(self::$categories_combo)) {
            // Get list of categories
            $rs = App::blog()->getCategories(['post_type' => 'post']);

            self::$categories_combo = App::backend()->combos()->getCategoriesCombo($rs, false, true);

            $rs->moveStart();
            while ($rs->fetch()) {
                $cat_url   = $rs->strField('cat_url');
                $cat_title = $rs->strField('cat_title');
                if ($cat_url !== '' && $cat_title !== '') {
                    self::$categories_label[$cat_url] = Html::escapeHTML($cat_title);
                }
            }
        }

        if (!isset(self::$langs_combo)) {
            // Get list of languages
            self::$langs_used = [];

            $rs = App::blog()->getLangs([
                'order' => 'asc',
            ]);

            /**
             * @var array<string, string> language code, language full label including code
             */
            $all_langs = App::lang()->getISOcodes();
            while ($rs->fetch()) {
                $post_lang = $rs->strField('post_lang');
                if ($post_lang !== '') {
                    self::$langs_used[$post_lang] = $all_langs[$post_lang] ?? $post_lang;
                }
            }

            $rs->moveStart();
            self::$langs_combo = App::backend()->combos()->getLangsCombo($rs, false, true);
        }

        if (!isset(self::$months_combo)) {
            // Get list of archive months
            $rs = App::blog()->getDates([
                'type' => 'month',
            ]);

            self::$months_values = array_merge(
                [__('All months') => '-'],
                App::backend()->combos()->getDatesCombo($rs)
            );

            self::$months_combo = [
                (new Option(__('All months'), '-')),
                ... App::backend()->combos()->getDatesCombo($rs, true),
            ];

            self::$first_year = 0;
            self::$last_year  = 0;
            while ($rs->fetch()) {
                $year = is_numeric($year = $rs->year()) ? (int) $year : 0;

                if ((self::$first_year === 0) || ($year < self::$first_year)) {
                    self::$first_year = $year;
                }

                if ((self::$last_year === 0) || ($year > self::$last_year)) {
                    self::$last_year = $year;
                }
            }
        }

        if (!isset(self::$pages_combo) && App::plugins()->moduleExists('pages')) {
            // Get list of page
            self::$pages_combo = [];

            $rs = App::blog()->getPosts(['post_type' => 'page']);
            while ($rs->fetch()) {
                $page_title = $rs->strField('post_title');
                $page_url   = $rs->getURL();
                if ($page_title !== '' && $page_url !== '') {
                    self::$pages_combo[$page_title] = $page_url;
                }
            }
        }

        if (!isset(self::$tags_combo) && App::plugins()->moduleExists('tags')) {
            // Get list of tags
            self::$tags_combo = [];

            self::$tags_combo[__('All tags')] = '-';

            $rs = App::meta()->getMetadata(['meta_type' => 'tag']);
            while ($rs->fetch()) {
                $meta_id = $rs->strField('meta_id');
                if ($meta_id !== '') {
                    self::$tags_combo[$meta_id] = $meta_id;
                }
            }
        }

        if (!isset(self::$items_combo)) {
            // Prepare list of menu item type

            /**
             * Liste des types d'item de menu
             *
             * @var        ArrayObject<string, array{string, bool}>
             */
            $items = new ArrayObject();

            $items['home'] = [__('Home'), false];

            if (App::blog()->settings()->get('system')->getBool('static_home')) {
                $items['posts'] = [__('Posts'), false];
            }

            if (count(self::$langs_used) > 1) {
                $items['lang'] = [__('Language'), true];
            }

            if (self::$categories_combo !== []) {
                $items['category'] = [__('Category'), true];
            }

            if (count(self::$months_combo) > 1) {
                $items['archive'] = [__('Archive'), true];
            }

            if (isset(self::$pages_combo)) {
                $items['pages'] = [__('Page'), true];
            }

            if (isset(self::$tags_combo) && count(self::$tags_combo) > 1) {
                $items['tags'] = [__('Tags'), true];
            }

            # --BEHAVIOR-- adminSimpleMenuAddType -- ArrayObject<array-key, array{string, bool}>
            # Should add an item to $items[<id>] as an [<label>,<optional step (true or false)>]
            App::behavior()->callBehavior('adminSimpleMenuAddType', $items);

            $items['special'] = [__('User defined'), false];

            self::$items_combo = [];
            foreach ($items as $type => $value) {
                self::$items_combo[$value[0]] = $type;
            }

            self::$items = $items->getArrayCopy();
        }
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        self::setup();

        // Saving new configuration
        self::$item_type       = '';
        self::$item_type_label = '';

        self::$item_select       = '';
        self::$item_select_label = '';
        self::$item_label        = '';
        self::$item_descr        = '';
        self::$item_url          = '';
        self::$item_data         = '';

        $item_targetBlank = false;
        $item_disabled    = false;

        $nodragndrop = App::auth()->prefs()->get('accessibility')->getBool('nodragdrop', false);

        self::$step = self::STEP_LIST;
        if (!empty($_POST['saveconfig'])) {
            try {
                self::$menu_active = !empty($_POST['active']);
                App::blog()->settings()->get(My::WORKSPACE)->put(My::SETTING_ACTIVE, self::$menu_active, App::blogWorkspace()::NS_BOOL);
                App::blog()->triggerBlog();

                // All done successfully, return to menu items list
                App::backend()->notices()->addSuccessNotice(__('Configuration successfully updated.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        } else {
            // Get posted parameters
            self::$item_type   = isset($_POST['item_type'])   && is_string($item_type = $_POST['item_type']) ? $item_type : '';
            self::$item_select = isset($_POST['item_select']) && is_string($item_select = $_POST['item_select']) ? $item_select : '';
            self::$item_label  = isset($_POST['item_label'])  && is_string($item_label = $_POST['item_label']) ? $item_label : '';
            self::$item_descr  = isset($_POST['item_descr'])  && is_string($item_descr = $_POST['item_descr']) ? $item_descr : '';
            self::$item_url    = isset($_POST['item_url'])    && is_string($item_url = $_POST['item_url']) ? $item_url : '';
            self::$item_data   = isset($_POST['item_data'])   && is_string($item_data = $_POST['item_data']) ? $item_data : '';

            $item_targetBlank = isset($_POST['item_targetBlank']) && !empty($_POST['item_targetBlank']);
            $item_disabled    = isset($_POST['item_disabled'])    && !empty($_POST['item_disabled']);

            // Cleanup current values
            self::$item_data = Html::clean(self::$item_data);

            // Processing

            // Get current step if any
            self::$step = isset($_GET['add']) && is_numeric($step = $_GET['add']) ? (int) $step : self::STEP_LIST;
            if ((self::$step > self::STEP_ADD) || (self::$step < self::STEP_LIST)) {
                self::$step = self::STEP_LIST;
            }

            if (self::$step !== self::STEP_LIST) {
                // Get current choice label
                self::$item_type_label = isset(self::$items[self::$item_type]) ? self::$items[self::$item_type][0] : '';

                switch (self::$step) {
                    case self::STEP_TYPE:
                        // First step, menu item type to be selected
                        self::$item_type   = '';
                        self::$item_select = '';

                        break;
                    case self::STEP_SUBTYPE:
                        if (self::$items[self::$item_type][1]) {
                            // Second step (optional), menu item sub-type to be selected
                            self::$item_select = '';

                            break;
                        }
                        // Continue to attributes step (there is no sub-type step)
                    case self::STEP_ATTRIBUTES:
                        // Third step, menu item attributes to be changed or completed if necessary
                        self::$item_select_label = '';
                        self::$item_label        = __('Label');
                        self::$item_descr        = __('Description');
                        self::$item_url          = Html::stripHostURL(App::blog()->url());
                        switch (self::$item_type) {
                            case 'home':
                                self::$item_label = __('Home');
                                self::$item_descr = App::blog()->settings()->get('system')->getBool('static_home') ? __('Home page') : __('Recent posts');

                                break;
                            case 'posts':
                                self::$item_label = __('Posts');
                                self::$item_descr = __('Recent posts');
                                self::$item_url .= App::url()->getURLFor('posts');

                                break;
                            case 'lang':
                                if (array_key_exists(self::$item_select, self::$langs_used)) {
                                    self::$item_select_label = self::$langs_used[self::$item_select];
                                    self::$item_label        = self::$item_select_label;
                                    self::$item_descr        = sprintf(__('Switch to %s language'), self::$item_select_label);
                                    self::$item_url .= App::url()->getURLFor('lang', self::$item_select);
                                }

                                break;
                            case 'category':
                                self::$item_select_label = self::$categories_label[self::$item_select];
                                self::$item_label        = self::$item_select_label;
                                self::$item_descr        = __('Recent Posts from this category');
                                self::$item_url .= App::url()->getURLFor('category', self::$item_select);

                                break;
                            case 'archive':
                                $month = array_search(self::$item_select, self::$months_values, true);
                                if ($month !== false) {
                                    self::$item_select_label = $month;
                                    if (self::$item_select === '-') {
                                        self::$item_label = __('Archives');
                                        self::$item_descr = self::$first_year . (self::$first_year !== self::$last_year ? ' - ' . self::$last_year : '');
                                        self::$item_url .= App::url()->getURLFor('archive');
                                    } else {
                                        self::$item_label = self::$item_select_label;
                                        self::$item_descr = sprintf(__('Posts from %s'), self::$item_select_label);
                                        self::$item_url .= App::url()->getURLFor('archive', substr(self::$item_select, 0, 4) . '/' . substr(self::$item_select, -2));
                                    }
                                }

                                break;
                            case 'pages':
                                $page = array_search(self::$item_select, self::$pages_combo, true);
                                if ($page !== false) {
                                    self::$item_select_label = $page;
                                    self::$item_label        = self::$item_select_label;
                                    self::$item_descr        = '';
                                    self::$item_url          = Html::stripHostURL(self::$item_select);
                                }

                                break;
                            case 'tags':
                                $tag = array_search(self::$item_select, self::$tags_combo, true);
                                if ($tag !== false) {
                                    self::$item_select_label = $tag;
                                    if (self::$item_select === '-') {
                                        self::$item_label = __('All tags');
                                        self::$item_descr = '';
                                        self::$item_url .= App::url()->getURLFor('tags');
                                    } else {
                                        self::$item_label = self::$item_select_label;
                                        self::$item_descr = sprintf(__('Recent posts for %s tag'), self::$item_select_label);
                                        self::$item_url .= App::url()->getURLFor('tag', self::$item_select);
                                    }
                                }

                                break;
                            case 'special':
                                break;
                            default:
                                # --BEHAVIOR-- adminSimpleMenuBeforeEdit - string, string, string[]
                                # Should modify if necessary $item_label, $item_descr and $item_url
                                # Should set if necessary $item_select_label (displayed on further admin step only)
                                [
                                    $item_url, $item_descr, $item_label, $item_select_label
                                ] = [
                                    self::$item_url,
                                    self::$item_descr,
                                    self::$item_label,
                                    self::$item_select_label,
                                ];
                                App::behavior()->callBehavior(
                                    'adminSimpleMenuBeforeEdit',
                                    self::$item_type,
                                    self::$item_select,
                                    [
                                        &$item_label,
                                        &$item_descr,
                                        &$item_url,
                                        &$item_select_label,
                                    ]
                                );
                                [
                                    self::$item_url,
                                    self::$item_descr,
                                    self::$item_label,
                                    self::$item_select_label,
                                ] = [
                                    $item_url, $item_descr, $item_label, $item_select_label,
                                ];

                                break;
                        }

                        break;
                    case self::STEP_ADD:
                        // Fourth step, menu item to be added
                        try {
                            if ((self::$item_label !== '') && (self::$item_url !== '')) {
                                self::$simple_menu->menu()->add(new MenuItem(
                                    self::$item_label,
                                    self::$item_descr,
                                    self::$item_url,
                                    $item_targetBlank,
                                    self::$item_data,
                                    $item_disabled
                                ));

                                // Save menu in blog settings
                                self::$simple_menu->save(My::WORKSPACE, My::SETTING_MENU);
                                App::blog()->triggerBlog();

                                // All done successfully, return to menu items list
                                App::backend()->notices()->addSuccessNotice(__('Menu item has been successfully added.'));
                                My::redirect();
                            } else {
                                self::$step              = self::STEP_ATTRIBUTES;
                                self::$item_select_label = self::$item_label;
                                App::backend()->notices()->addErrorNotice(__('Label and URL of menu item are mandatory.'));
                            }
                        } catch (Exception $e) {
                            App::error()->add($e->getMessage());
                        }

                        break;
                }
            } else {
                if (!empty($_POST['removeaction'])) {
                    // Remove selected menu items
                    try {
                        if (!empty($_POST['items_selected']) && is_array($_POST['items_selected'])) {
                            // $_POST['items_selected'] contains string indices of selected menu item
                            $list = [];
                            foreach ($_POST['items_selected'] as $v) {
                                if (is_numeric($v)) {
                                    $list[] = (int) $v;
                                }
                            }

                            // Sort list of indices in reverse order
                            rsort($list);

                            // Then delete selected items from menu
                            foreach ($list as $index) {
                                self::$simple_menu->menu()->remove($index);
                            }

                            // Save menu in blog settings
                            self::$simple_menu->save(My::WORKSPACE, My::SETTING_MENU);
                            App::blog()->triggerBlog();

                            // All done successfully, return to menu items list
                            App::backend()->notices()->addSuccessNotice(__('Menu items have been successfully removed.'));
                            My::redirect();
                        } else {
                            throw new Exception(__('No menu items selected.'));
                        }
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }
                }

                if (!empty($_POST['updateaction'])) {
                    // Update menu items
                    try {
                        /**
                         * @var array<int, TSimpleMenuItem> $new_menu
                         */
                        $new_menu = [];

                        if (is_array($_POST['items_label'])
                            && is_array($_POST['items_descr'])
                            && is_array($_POST['items_url'])
                            && is_array($_POST['items_data'])
                        ) {
                            $count = count($_POST['items_label']);
                            for ($i = 0; $i < $count; $i++) {
                                $label = is_string($label = $_POST['items_label'][$i]) ? $label : '';
                                // @phpstan-ignore offsetAccess.nonOffsetAccessible (false positive)
                                $description = is_string($description = $_POST['items_descr'][$i]) ? $description : '';
                                // @phpstan-ignore offsetAccess.nonOffsetAccessible (false positive)
                                $url = is_string($url = $_POST['items_url'][$i]) ? $url : '';
                                // @phpstan-ignore offsetAccess.nonOffsetAccessible (false positive)
                                $data = is_string($data = $_POST['items_data'][$i]) ? $data : '';

                                $new_menu[] = [
                                    'label'       => $label,
                                    'descr'       => $description,
                                    'url'         => $url,
                                    'targetBlank' => false,
                                    'data'        => Html::clean($data),
                                    'disabled'    => false,
                                ];
                            }

                            // Order list of items according on $_POST['order'] if given

                            if (isset($_POST['order']) && is_array($_POST['order']) && !$nodragndrop) {
                                // Cope with drag'n'drop
                                $ordered_menu = $new_menu;
                                $count        = count($_POST['order']);
                                for ($i = 0; $i < $count; $i++) {
                                    $position = is_numeric($position = $_POST['order'][$i]) ? (int) $position : 0;
                                    if ($position >= 0) {
                                        $ordered_menu[$i] = $new_menu[$position - 1];
                                    }
                                }
                                $new_menu = $ordered_menu;
                            }

                            // Get target blank options
                            if (isset($_POST['items_targetBlank'])
                                && is_array($_POST['items_targetBlank'])
                                && isset($_POST['items_id'])
                                && is_array($_POST['items_id'])
                            ) {
                                $counter = count($_POST['items_targetBlank']);
                                for ($i = 0; $i < $counter; $i++) {
                                    $index = $_POST['items_targetBlank'][$i];
                                    $id    = (int) array_search($index, $_POST['items_id'], true);

                                    $new_menu[$id]['targetBlank'] = true;
                                }
                            }

                            // Get disabled options
                            if (isset($_POST['items_disabled'])
                                && is_array($_POST['items_disabled'])
                                && isset($_POST['items_id'])
                                && is_array($_POST['items_id'])
                            ) {
                                $counter = count($_POST['items_disabled']);
                                for ($i = 0; $i < $counter; $i++) {
                                    $index = $_POST['items_disabled'][$i];
                                    $id    = (int) array_search($index, $_POST['items_id'], true);

                                    $new_menu[$id]['disabled'] = true;
                                }
                            }

                            if (isset($_POST['order']) && is_array($_POST['order']) && $nodragndrop) {
                                // Order menu items using input positions

                                // Sanitize new orders
                                $order = $_POST['order'];
                                asort($order);
                                $order = array_keys($order);

                                // Order menu
                                $ordered_menu = $new_menu;
                                $count        = count($order);
                                for ($i = 0; $i < $count; $i++) {
                                    $position                = $order[$i];
                                    $ordered_menu[$position] = $new_menu[$i];
                                }
                                $new_menu = $ordered_menu;
                            }
                        }

                        // Create a new Menu from given data
                        $menu = new Menu();
                        foreach ($new_menu as $item) {
                            $menu->add(new MenuItem(
                                $item['label']       ?? '',
                                $item['descr']       ?? '',
                                $item['url']         ?? '',
                                $item['targetBlank'] ?? false,
                                $item['data']        ?? '',
                                $item['disabled']    ?? false
                            ));
                        }
                        self::$simple_menu = new SimpleMenu($menu);

                        // Save menu in blog settings
                        self::$simple_menu->save(My::WORKSPACE, My::SETTING_MENU);
                        App::blog()->triggerBlog();

                        // All done successfully, return to menu items list
                        App::backend()->notices()->addSuccessNotice(__('Menu items have been successfully updated.'));
                        My::redirect();
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }
                }
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $page_title = __('Simple menu');
        $blog_name  = Html::escapeHTML(App::blog()->name());
        $user_lang  = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : '';

        /**
         * @var string[]
         */
        $head = [];

        $head[] = App::backend()->page()->jsJson('simplemenu', [
            'confirm_items_delete' => __('Are you sure you want to remove selected menu items?'),
        ]);
        $head[] = My::jsLoad('simplemenu');
        $head[] = App::backend()->page()->jsConfirmClose('settings', 'menuitemsappend', 'additem', 'menuitems');

        if (!App::auth()->prefs()->get('accessibility')->getBool('nodragdrop')) {
            $head[] = App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js');
            $head[] = App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js');
            $head[] = My::jsLoad('dragndrop');
        }

        App::backend()->page()->openModule($page_title, implode('', $head));

        $step_label = '';
        if (self::$step !== self::STEP_LIST) {
            switch (self::$step) {
                case self::STEP_TYPE:
                    $step_label = __('Step #1');

                    break;
                case self::STEP_SUBTYPE:
                    if (self::$items[self::$item_type][1]) {
                        $step_label = __('Step #2');

                        break;
                    }
                case self::STEP_ATTRIBUTES:
                    $step_label = self::$items[self::$item_type][1] ? __('Step #3') : __('Step #2');

                    break;
            }
            echo
            App::backend()->page()->breadcrumb(
                [
                    $blog_name     => '',
                    $page_title    => App::backend()->getPageURL(),
                    __('Add item') => '',
                    $step_label    => '',
                ],
                [
                    'hl_pos' => -2,
                ]
            ) .
            App::backend()->notices()->getNotices();
        } else {
            echo
            App::backend()->page()->breadcrumb(
                [
                    $blog_name  => '',
                    $page_title => '',
                ]
            ) .
            App::backend()->notices()->getNotices();
        }

        if (self::$step !== self::STEP_LIST) {
            // New item form
            switch (self::$step) {
                case self::STEP_TYPE:

                    // Type of item selection

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
                                                ->items(self::$items_combo)
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

                    if (self::$items[self::$item_type][1]) {
                        // Additional choice to do

                        $choice = match (self::$item_type) {
                            'lang' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(self::$langs_combo)
                                        ->label(new Label(__('Select language:'), Label::OL_TF)),
                                ]),
                            'category' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(self::$categories_combo)
                                        ->label(new Label(__('Select category:'), Label::OL_TF)),
                                ]),
                            'archive' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(self::$months_combo)
                                        ->label(new Label(__('Select month (if necessary):'), Label::OL_TF)),
                                ]),
                            'pages' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(self::$pages_combo)
                                        ->label(new Label(__('Select page:'), Label::OL_TF)),
                                ]),
                            'tags' => (new Para())->class('field')
                                ->items([
                                    (new Select('item_select'))
                                        ->items(self::$tags_combo)
                                        ->label(new Label(__('Select tag (if necessary):'), Label::OL_TF)),
                                ]),
                            default => # --BEHAVIOR-- adminSimpleMenuSelect -- string, string
                                # Optional step once self::$item_type is known: should provide a field using 'item_select' as id, included in a <p class="field"></p> and don't forget the <label> ;-)
                                (new Text(null, App::behavior()->callBehavior('adminSimpleMenuSelect', self::$item_type, 'item_select'))),
                        };

                        echo (new Form('additem'))
                            ->method('post')
                            ->action(App::backend()->getPageURL() . '&add=' . trim((string) self::STEP_ATTRIBUTES))
                            ->fields([
                                (new Fieldset())
                                    ->legend(new Legend(self::$item_type_label))
                                    ->fields([
                                        $choice,
                                        (new Para())
                                            ->class('form-buttons')
                                            ->items([
                                                ...My::hiddenFields(),
                                                (new Hidden('item_type', self::$item_type)),
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
                                ->legend(new Legend(self::$item_type_label . (self::$item_select_label !== '' ? ' (' . self::$item_select_label . ')' : '')))
                                ->fields([
                                    (new Note())
                                        ->class('form-note')
                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Input('item_label'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->value(self::$item_label)
                                                ->required(true)
                                                ->placeholder(__('Label'))
                                                ->lang($user_lang)
                                                ->spellcheck(true)
                                                ->label(
                                                    (new Label(
                                                        (new Span('*'))->render() . __('Label of item menu:'),
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
                                                ->value(self::$item_descr)
                                                ->lang($user_lang)
                                                ->spellcheck(true)
                                                ->label(new Label(__('Description of item menu:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Input('item_url'))
                                                ->size(40)
                                                ->maxlength(255)
                                                ->value(self::$item_url)
                                                ->required(true)
                                                ->placeholder(__('URL'))
                                                ->lang($user_lang)
                                                ->spellcheck(true)
                                                ->label(
                                                    (new Label(
                                                        (new Span('*'))->render() . __('URL of item menu:'),
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
                                        ->class('field')
                                        ->items([
                                            (new Input('item_data'))
                                                ->size(40)
                                                ->maxlength(255)
                                                ->value(self::$item_data)
                                                ->label(new Label(__('Custom data of item menu:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->class('field')
                                        ->items([
                                            (new Checkbox('item_disabled'))
                                                ->value('off')
                                                ->label(new Label(__('Disabled'), Label::OL_FT)),
                                        ]),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            ...My::hiddenFields(),
                                            (new Hidden('item_type', self::$item_type)),
                                            (new Hidden('item_select', self::$item_select)),
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

        if (self::$step === self::STEP_LIST) {
            // Formulaire d'activation

            echo (new Form('settings'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('active', self::$menu_active))
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
                        ->class('new-stuff')
                        ->items([
                            ...My::hiddenFields(),
                            (new Submit(['appendaction'], __('Add an item')))
                                ->class(['button', 'add']),
                        ]),
                ])
            ->render();
        }

        if (count(self::$simple_menu->menu()) > 0) {
            // Prepare list

            // Entête table
            $headers = [];
            if (self::$step === self::STEP_LIST) {
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
                (new Th())->scope('col')->text(__('Custom data')),
                (new Th())->scope('col')->text(__('Disabled')),
            ]);

            $rows  = [];
            $count = 0;
            foreach (self::$simple_menu->menu() as $i => $menu_item) {
                $cols = [];

                // targetBlank may not exists as this value has been added after this plugin creation.
                if ($menu_item->getTargetBlank()) {
                    $target_blank     = true;
                    $target_blank_str = 'X';
                } else {
                    $target_blank     = false;
                    $target_blank_str = '';
                }

                // disabled may not exists as this value has been added after this plugin creation.
                if ($menu_item->getDisabled()) {
                    $disabled     = true;
                    $disabled_str = 'X';
                } else {
                    $disabled     = false;
                    $disabled_str = '';
                }

                if (self::$step === self::STEP_LIST) {
                    $count++;
                    $cols = [
                        (new Td())
                            ->class(['minimal', App::auth()->prefs()->get('accessibility')->getBool('nodragdrop') ? '' : 'handle'])
                            ->items([
                                (new Number(['order[' . $i . ']'], 1, count(self::$simple_menu->menu()), $count))
                                    ->class('position')
                                    ->title(sprintf(__('position of %s'), Html::escapeHTML($menu_item->getLabel()))),
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
                                    ->required(true)
                                    ->value(Html::escapeHTML($menu_item->getLabel()))
                                    ->lang($user_lang)
                                    ->spellcheck(true),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Input(['items_descr[]', 'imd-' . $i]))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($menu_item->getDescription()))
                                    ->lang($user_lang)
                                    ->spellcheck(true),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Input(['items_url[]', 'imu-' . $i]))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->required(true)
                                    ->value(Html::escapeHTML($menu_item->getUrl())),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Checkbox(['items_targetBlank[]', 'imtb-' . $i], $target_blank))
                                    ->value($i),
                                (new Hidden(['items_id[]', 'imid-' . $i], (string) $i)),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Input(['items_data[]', 'imdata-' . $i]))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($menu_item->getData())),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Checkbox(['items_disabled[]', 'imtd-' . $i], $disabled))
                                    ->value($i),
                            ]),
                    ];
                } else {
                    $cols = [
                        (new Td())
                            ->class('nowrap')
                            ->text(Html::escapeHTML($menu_item->getLabel())),
                        (new Td())
                            ->class('nowrap')
                            ->text(Html::escapeHTML($menu_item->getDescription())),
                        (new Td())
                            ->class('nowrap')
                            ->text(Html::escapeHTML($menu_item->getUrl())),
                        (new Td())
                            ->class('nowrap')
                            ->text($target_blank_str),
                        (new Td())
                            ->class('nowrap')
                            ->text(Html::escapeHTML($menu_item->getData())),
                        (new Td())
                            ->class('nowrap')
                            ->text($disabled_str),
                    ];
                }

                $rows[] = (new Tr())
                    ->id('l_' . $i)
                    ->class(array_filter([
                        'line',
                        $disabled ? 'offline' : '',
                    ]))
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
                            ->id(self::$step === self::STEP_LIST ? 'menuitemslist' : '')
                            ->rows($rows)),
                ]);

            // Display form/list

            echo (self::$step === self::STEP_LIST ?
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

        App::backend()->page()->helpBlock('simpleMenu');

        App::backend()->page()->closeModule();
    }
}
