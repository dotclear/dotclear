<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module backend manage pages process.
 * @ingroup pages
 */
class Manage
{
    use TraitProcess;

    // Local static properties

    /**
     * Current page of pages list
     */
    private static int $page;

    /**
     * Maximum number of line in each page of pages list
     */
    private static int $nb_per_page;

    /**
     * Instance of backend actions
     */
    private static BackendActions $actions;

    /**
     * Have the current backend actions been rendered?
     */
    private static bool $actions_rendered;

    /**
     * Instance of pages list
     */
    private static BackendList $post_list;

    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(($_REQUEST['act'] ?? 'list') === 'page' ? ManagePage::init() : true);
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (($_REQUEST['act'] ?? 'list') === 'page') {
            return ManagePage::process();
        }

        $params = [
            'post_type' => 'page',
        ];

        self::$page = isset($_GET['page']) && is_numeric($page = $_GET['page']) ? (int) $page : 1;

        $nb_per_page_filter = App::backend()->userPref()->getUserFilterNb('pages') ?? 30;

        self::$nb_per_page = isset($_GET['nb']) && is_numeric($nb_per_page = $_GET['nb']) ? (int) $nb_per_page : $nb_per_page_filter;

        $params['limit'] = [((self::$page - 1) * self::$nb_per_page), self::$nb_per_page];

        $params['no_content'] = true;
        $params['order']      = 'post_position ASC, post_title ASC';

        try {
            $pages   = App::blog()->getPosts($params);
            $counter = App::blog()->getPosts($params, true);

            self::$post_list = new BackendList($pages, $counter->cardinal());
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        // Actions combo box
        self::$actions          = new BackendActions(App::backend()->url()->get('admin.plugin'), ['p' => 'pages']);
        self::$actions_rendered = false;
        if (self::$actions->process()) {
            self::$actions_rendered = true;
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (($_REQUEST['act'] ?? 'list') === 'page') {
            ManagePage::render();

            return;
        }

        if (self::$actions_rendered) {
            self::$actions->render();

            return;
        }

        $head = '';
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $head = App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js') .
            App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js');
        }

        App::backend()->page()->openModule(
            __('Pages'),
            $head .
            App::backend()->page()->jsJson('pages_list', ['confirm_delete_posts' => __('Are you sure you want to delete selected pages?')]) .
            My::jsLoad('list')
        );

        echo
        App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => '',
            ]
        ) .
        App::backend()->notices()->getNotices();

        if (!empty($_GET['upd'])) {
            App::backend()->notices()->success(__('Selected pages have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            App::backend()->notices()->success(__('Selected pages have been successfully deleted.'));
        } elseif (!empty($_GET['reo'])) {
            App::backend()->notices()->success(__('Selected pages have been successfully reordered.'));
        }

        echo (new Para())
            ->class('new-stuff')
            ->items([
                (new Link())
                    ->class(['button', 'add'])
                    ->href(App::backend()->getPageURL() . '&act=page')
                    ->text(__('New page')),
            ])
        ->render();

        if (!App::error()->flag() && self::$post_list->getCount() > 0) {
            // Show pages
            self::$post_list->display(
                self::$page,
                self::$nb_per_page,
                (new Form('form-entries'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text(null, '%s')), // List of pages
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items(self::$actions->getCombo())
                                            ->label((new Label(__('Selected pages action:'), Label::OUTSIDE_TEXT_BEFORE))->class('classic')),
                                        (new Submit('do-action', __('ok'))),
                                    ]),
                            ]),
                        (new Note())
                            ->class(['form-note', 'hidden-if-js', 'clear'])
                            ->text(__('To rearrange pages order, change number at the begining of the line, then click on “Save pages order” button.')),
                        (new Note())
                            ->class(['form-note', 'hidden-if-no-js', 'clear'])
                            ->text(__('To rearrange pages order, move items by drag and drop, then click on “Save pages order” button.')),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                ...My::hiddenFields(),
                                (new Hidden(['post_type'], 'page')),
                                (new Hidden(['act'], 'list')),
                                (new Submit(['reorder'], __('Save pages order'))),
                                (new Button(['back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']),
                            ]),
                    ])
                ->render()
            );
        }
        App::backend()->page()->helpBlock(My::id());

        App::backend()->page()->closeModule();
    }
}
