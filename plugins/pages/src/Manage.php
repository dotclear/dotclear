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

        App::backend()->page        = empty($_GET['page']) ? 1 : max(1, (int) $_GET['page']);
        App::backend()->nb_per_page = App::backend()->userPref()->getUserFilters('pages', 'nb');

        if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
            App::backend()->nb_per_page = (int) $_GET['nb'];
        }

        $params['limit'] = [((App::backend()->page - 1) * App::backend()->nb_per_page), App::backend()->nb_per_page];

        $params['no_content'] = true;
        $params['order']      = 'post_position ASC, post_title ASC';

        App::backend()->post_list = null;

        try {
            $pages   = App::blog()->getPosts($params);
            $counter = App::blog()->getPosts($params, true);

            App::backend()->post_list = new BackendList($pages, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        // Actions combo box
        App::backend()->pages_actions_page          = new BackendActions(App::backend()->url()->get('admin.plugin'), ['p' => 'pages']);
        App::backend()->pages_actions_page_rendered = null;
        if (App::backend()->pages_actions_page->process()) {
            App::backend()->pages_actions_page_rendered = true;
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

        if (App::backend()->pages_actions_page_rendered) {
            App::backend()->pages_actions_page->render();

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

        if (!App::error()->flag() && App::backend()->post_list) {
            // Show pages
            App::backend()->post_list->display(
                App::backend()->page,
                App::backend()->nb_per_page,
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
                                            ->items(App::backend()->pages_actions_page->getCombo())
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
