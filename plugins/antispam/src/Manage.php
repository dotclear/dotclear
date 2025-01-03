<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup antispam
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Antispam::initFilters();

        App::backend()->filters     = Antispam::$filters->getFilters();
        App::backend()->page_name   = My::name();
        App::backend()->filter_gui  = false;
        App::backend()->default_tab = null;
        App::backend()->filter      = null;

        try {
            // Show filter configuration GUI
            if (!empty($_GET['f'])) {
                if (!isset(App::backend()->filters[$_GET['f']])) {
                    throw new Exception(__('Filter does not exist.'));
                }

                if (!App::backend()->filters[$_GET['f']]->hasGUI()) {
                    throw new Exception(__('Filter has no user interface.'));
                }

                App::backend()->filter     = App::backend()->filters[$_GET['f']];
                App::backend()->filter_gui = App::backend()->filter->gui(App::backend()->filter->guiURL() ?: '');
            }

            // Remove all spam
            if (!empty($_POST['delete_all'])) {
                $ts = isset($_POST['ts']) ? (int) $_POST['ts'] : null;
                $ts = Date::str('%Y-%m-%d %H:%M:%S', $ts, App::blog()->settings()->system->blog_timezone);

                Antispam::delAllSpam($ts);

                Notices::addSuccessNotice(__('Spam comments have been successfully deleted.'));
                My::redirect();
            }

            // Update filters
            if (isset($_POST['filters_upd'])) {
                /**
                 * @var        array<int|string, array{0:bool, 1:int, 2:bool}>
                 */
                $filters_opt = [];
                $i           = 0;
                foreach (App::backend()->filters as $filter_id => $filter_id) {
                    $filters_opt[$filter_id] = [false, $i, false];
                    $i++;
                }

                // Enable active filters
                if (isset($_POST['filters_active']) && is_array($_POST['filters_active'])) {
                    foreach ($_POST['filters_active'] as $filter_id) {
                        $filters_opt[$filter_id][0] = true;
                    }
                }

                // Order filters
                if (!empty($_POST['f_order']) && empty($_POST['filters_order'])) {
                    $order = $_POST['f_order'];
                    asort($order);
                    $order = array_keys($order);
                } elseif (!empty($_POST['filters_order'])) {
                    $order = explode(',', trim((string) $_POST['filters_order'], ','));
                }

                if (isset($order)) {
                    foreach ($order as $i => $filter_id) {
                        $filters_opt[$filter_id][1] = $i;
                    }
                }

                // Set auto delete flag
                if (isset($_POST['filters_auto_del']) && is_array($_POST['filters_auto_del'])) {
                    foreach ($_POST['filters_auto_del'] as $filter_id) {
                        $filters_opt[$filter_id][2] = true;
                    }
                }

                Antispam::$filters->saveFilterOpts($filters_opt);

                Notices::addSuccessNotice(__('Filters configuration has been successfully saved.'));
                My::redirect();
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $title = (App::backend()->filter_gui !== false ?
            sprintf(__('%s configuration'), App::backend()->filter->name) . ' - ' :
            '' . App::backend()->page_name);

        $head = Page::jsPageTabs(App::backend()->default_tab);
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $head .= Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js');
        }
        $head .= Page::jsJson('antispam', ['confirm_spam_delete' => __('Are you sure you want to delete all spams?')]) .
            My::jsLoad('antispam') .
            My::cssLoad('style');

        Page::openModule($title, $head);

        if (App::backend()->filter_gui !== false) {
            // Display filter GUI
            echo
            Page::breadcrumb(
                [
                    __('Plugins')                                                        => '',
                    App::backend()->page_name                                            => App::backend()->getPageURL(),
                    sprintf(__('%s filter configuration'), App::backend()->filter->name) => '',
                ]
            ) .
            Notices::getNotices();

            echo (new Para())
                ->items([
                    (new Link('back'))->href(App::backend()->getPageURL())->class('back')->text(__('Back to filters list')),
                ])
            ->render();

            echo
            App::backend()->filter_gui;

            if (App::backend()->filter->help) {
                Page::helpBlock(App::backend()->filter->help);
            }
        } else {
            // Display list of filters
            echo
            Page::breadcrumb(
                [
                    __('Plugins')             => '',
                    App::backend()->page_name => '',
                ]
            ) .
            Notices::getNotices();

            // Information
            $spam_count      = Antispam::countSpam();
            $published_count = Antispam::countPublishedComments();
            $moderationTTL   = (int) My::settings()->antispam_moderation_ttl;

            $action = [];
            if ($spam_count > 0) {
                $action = [
                    (new Para())->items([
                        (new Submit(['delete_all'], __('Delete all spams')))->class('delete'),
                        (new Hidden('ts', (string) time())),
                        App::nonce()->formNonce(),
                    ]),
                ];
            }
            $note = [];
            if ($moderationTTL >= 0) {
                $note = [
                    (new Para())->class('form-note')->items([
                        new Text(
                            null,
                            sprintf(__('All spam comments older than %s day(s) will be automatically deleted.'), $moderationTTL)
                        ),
                        new Text(
                            null,
                            sprintf(
                                __('You can modify this duration in the %s'),
                                (new Link())->href(App::backend()->url()->get('admin.blog.pref') . '#params.antispam_params')->text(__('Blog settings'))->render()
                            )
                        ),
                    ]),
                ];
            }
            $infos = [
                (new Fieldset())
                ->legend(new Legend(__('Information')))
                ->items([
                    (new Ul())
                        ->class([
                            'spaminfo',
                            'clear',     // Needed because of float left on above legend
                        ])
                        ->items([
                            (new Li())->class('spamcount')->items([
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => '-2']))
                                    ->text(__('Junk comments:')),
                                (new Text('strong', ' ' . $spam_count)),
                            ]),
                            (new Li())->class('hamcount')->items([
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => '1']))
                                    ->text(__('Published comments:')),
                                (new Text(null, ' ' . $published_count)),
                            ]),
                        ]),
                    ...$action,
                    ...$note,
                ]),
            ];
            $group = $spam_count > 0 ? (new Form('info'))->action(App::backend()->getPageURL())->method('post') : (new Div('info'));
            echo $group
                ->items($infos)
            ->render();

            // Filters
            if (!empty($_GET['upd'])) {
                Notices::success(__('Filters configuration has been successfully saved.'));
            }

            $rows = [];
            $i    = 1;
            foreach (App::backend()->filters as $fid => $f) {
                if ($f->hasGUI()) {
                    $gui_link = (new Link())
                        ->href(Html::escapeHTML($f->guiURL()))
                        ->title(__('Filter configuration'))
                        ->text(
                            (new Img('images/edit.svg'))
                                ->alt(__('Filter configuration'))
                                ->class(['mark', 'mark-edit', 'light-only'])
                            ->render() .
                            (new Img('images/edit-dark.svg'))
                                ->alt(__('Filter configuration'))
                                ->class(['mark', 'mark-edit', 'dark-only'])
                            ->render()
                        );
                } else {
                    $gui_link = (new Text(null, '&nbsp;'));
                }

                $rows[] = (new Tr('f_' . $fid))
                    ->class(['line', $f->active ? '' : ' offline'])
                    ->items([
                        (new Td())
                            ->class(App::auth()->prefs()->accessibility->nodragdrop ? '' : 'handle')
                            ->items([
                                (new Number(['f_order[' . $fid . ']'], 1, count(App::backend()->filters), $i))
                                    ->class('position')
                                    ->title(__('position')),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Checkbox(['filters_active[]'], $f->active))
                                    ->value($fid)
                                    ->title(__('Active')),
                            ]),
                        (new Td())
                            ->class('nowrap')
                            ->items([
                                (new Checkbox(['filters_auto_del[]'], $f->auto_delete))
                                    ->value($fid)
                                    ->title(__('Auto Del.')),
                            ]),
                        (new Th())
                            ->class('nowrap')
                            ->scope('row')
                            ->text($f->name),
                        (new Td())
                            ->class('maximal')
                            ->text($f->description),
                        (new Td())
                            ->class('status')
                            ->items([$gui_link]),
                    ])
                ;
                $i++;
            }

            echo (new Form('filters-list-form'))
                ->action(App::backend()->getPageURL())
                ->method('post')
                ->fields([
                    (new Fieldset())->legend(new Legend(__('Available spam filters')))->items([
                        (new Div())->class('table-outer')->items([
                            (new Table())
                                ->class(['filters', 'dragable'])
                                ->thead((new Thead())->rows([
                                    (new Th())->text(__('Order')),
                                    (new Th())->text(__('Active')),
                                    (new Th())->text(__('Auto Del.')),
                                    (new Th())->class('nowrap')->text(__('Filter name')),
                                    (new Th())->colspan(2)->text(__('Description')),
                                ]))
                                ->tbody((new Tbody('filters-list'))->rows($rows)),
                        ]),
                        (new Para())->class('form-buttons')->items([
                            (new Hidden('filters_order', '')),
                            (new Submit(['filters_upd'], __('Save'))),
                            (new Button('back', __('Back')))->class(['go-back', 'reset', 'hidden-if-no-js']),
                            App::nonce()->formNonce(),
                        ]),
                    ]),
                ])
            ->render();

            // Syndication
            if (App::config()->adminUrl() !== '') {
                $ham_feed = App::blog()->url() . App::url()->getURLFor(
                    'hamfeed',
                    Antispam::getUserCode()
                );
                $spam_feed = App::blog()->url() . App::url()->getURLFor(
                    'spamfeed',
                    Antispam::getUserCode()
                );

                echo (new Div())->class('fieldset')->items([
                    (new Text('h3', __('Syndication'))),
                    (new Ul())->class('spaminfo')->items([
                        (new Li())->class('feed')->items([
                            (new Link())->href($spam_feed)->text(__('Junk comments RSS feed')),
                        ]),
                        (new Li())->class('feed')->items([
                            (new Link())->href($ham_feed)->text(__('Published comments RSS feed')),
                        ]),
                    ]),
                ])
                ->render();
            }

            Page::helpBlock('antispam', 'antispam-filters');
        }

        Page::closeModule();
    }
}
