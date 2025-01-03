<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Exception;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module manage blogrolls process.
 * @ingroup blogroll
 */
class Manage extends Process
{
    private static bool $edit = false;

    public static function init(): bool
    {
        if (self::status(My::checkContext(My::MANAGE))) {
            App::backend()->blogroll = new Blogroll(App::blog());

            if (!empty($_REQUEST['edit']) && !empty($_REQUEST['id'])) {
                self::$edit = ManageEdit::init();
            } else {
                App::backend()->default_tab = '';
                App::backend()->link_title  = '';
                App::backend()->link_href   = '';
                App::backend()->link_desc   = '';
                App::backend()->link_lang   = '';
                App::backend()->cat_title   = '';
                App::backend()->imported    = null;
            }
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (self::$edit) {
            return ManageEdit::process();
        }

        if (!empty($_POST['import_links']) && !empty($_FILES['links_file'])) {
            // Import links - download file

            App::backend()->default_tab = 'import-links';

            try {
                Files::uploadStatus($_FILES['links_file']);
                $ifile = App::config()->cacheRoot() . '/' . md5(uniqid());
                if (!move_uploaded_file($_FILES['links_file']['tmp_name'], $ifile)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }

                try {
                    App::backend()->imported = UtilsImport::loadFile($ifile);
                    @unlink($ifile);
                } catch (Exception $e) {
                    @unlink($ifile);

                    throw $e;
                }

                if (App::backend()->imported === [] || App::backend()->imported === false) {
                    App::backend()->imported = null;

                    throw new Exception(__('Nothing to import'));
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['import_links_do'])) {
            // Import links - import entries

            foreach ($_POST['entries'] as $idx) {
                App::backend()->link_title = Html::escapeHTML($_POST['title'][$idx]);
                App::backend()->link_href  = Html::escapeHTML($_POST['url'][$idx]);
                App::backend()->link_desc  = Html::escapeHTML($_POST['desc'][$idx]);

                try {
                    App::backend()->blogroll->addLink(App::backend()->link_title, App::backend()->link_href, App::backend()->link_desc, '');
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                    App::backend()->default_tab = 'import-links';
                }
            }

            Notices::addSuccessNotice(__('links have been successfully imported.'));
            My::redirect();
        }

        if (!empty($_POST['cancel_import'])) {
            // Cancel import

            App::error()->add(__('Import operation cancelled.'));
            App::backend()->default_tab = 'import-links';
        }

        if (!empty($_POST['add_link'])) {
            // Add link

            App::backend()->link_title = Html::escapeHTML($_POST['link_title']);
            App::backend()->link_href  = Html::escapeHTML($_POST['link_href']);
            App::backend()->link_desc  = Html::escapeHTML($_POST['link_desc']);
            App::backend()->link_lang  = Html::escapeHTML($_POST['link_lang']);

            try {
                App::backend()->blogroll->addLink(App::backend()->link_title, App::backend()->link_href, App::backend()->link_desc, App::backend()->link_lang);

                Notices::addSuccessNotice(__('Link has been successfully created.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
                App::backend()->default_tab = 'add-link';
            }
        }

        if (!empty($_POST['add_cat'])) {
            // Add category

            App::backend()->cat_title = Html::escapeHTML($_POST['cat_title']);

            try {
                App::backend()->blogroll->addCategory(App::backend()->cat_title);
                Notices::addSuccessNotice(__('category has been successfully created.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
                App::backend()->default_tab = 'add-cat';
            }
        }

        if (!empty($_POST['removeaction']) && !empty($_POST['remove'])) {
            // Delete link

            foreach ($_POST['remove'] as $v) {
                try {
                    App::backend()->blogroll->delItem($v);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());

                    break;
                }
            }

            if (!App::error()->flag()) {
                Notices::addSuccessNotice(__('Items have been successfully removed.'));
                My::redirect();
            }
        }

        // Prepare order links

        $order = [];
        if (empty($_POST['links_order']) && !empty($_POST['order'])) {
            $order = $_POST['order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['links_order'])) {
            $order = explode(',', (string) $_POST['links_order']);
        }

        if (!empty($_POST['saveorder']) && $order !== []) {
            // Order links

            foreach ($order as $pos => $l) {
                $pos += 1;

                try {
                    App::backend()->blogroll->updateOrder($l, (string) $pos);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            if (!App::error()->flag()) {
                Notices::addSuccessNotice(__('Items order has been successfully updated'));
                My::redirect();
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (self::$edit) {
            ManageEdit::render();

            return;
        }

        // Languages combo
        $links      = App::backend()->blogroll->getLangs(['order' => 'asc']);
        $lang_combo = Combos::getLangsCombo($links, true);

        // Get links
        $rs = null;

        try {
            $rs = App::backend()->blogroll->getLinks();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        $head = Page::jsConfirmClose('links-form', 'add-link-form', 'add-category-form');
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $head .= Page::jsLoad('js/jquery/jquery-ui.custom.js') .
                Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
                My::jsLoad('dragndrop');
        }
        $head .= Page::jsPageTabs(App::backend()->default_tab);
        $head .= Page::jsJson('blogroll', ['confirm_links_delete' => __('Are you sure you want to delete selected links?')]) .
            My::jsLoad('blogroll');

        Page::openModule(My::name(), $head);

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => '',
            ]
        ) .
        Notices::getNotices();

        // Tab: Links list
        if (!$rs->isEmpty()) {
            $rows = [];
            while ($rs->fetch()) {
                $position = $rs->index() + 1;
                $cols     = [];

                $cols[] = (new Td())
                    ->class(['minimal', App::auth()->prefs()->accessibility->nodragdrop ? '' : 'handle'])
                    ->items([
                        (new Number(['order[' . $rs->link_id . ']'], 1, $rs->count(), (int) $position))
                            ->class('position')
                            ->title(__('position')),
                    ]);
                $cols[] = (new Td())
                    ->class('minimal')
                    ->items([
                        (new Checkbox(['remove[]']))
                            ->value($rs->link_id)
                            ->title(__('select this link')),
                    ]);

                if ($rs->is_cat) {
                    $cols[] = (new Td())
                        ->colspan(5)
                        ->items([
                            (new Para(null, 'strong'))->items([
                                (new Link())
                                    ->href(App::backend()->getPageURL() . '&amp;edit=1&amp;id=' . $rs->link_id)
                                    ->text(Html::escapeHTML($rs->link_desc)),
                            ]),
                        ]);
                } else {
                    $cols[] = (new Td())
                        ->items([
                            (new Link())
                                ->href(App::backend()->getPageURL() . '&amp;edit=1&amp;id=' . $rs->link_id)
                                ->text(Html::escapeHTML($rs->link_title)),
                        ]);
                    $cols[] = (new Td())
                        ->items([
                            (new Text(null, Html::escapeHTML($rs->link_desc))),
                        ]);
                    $cols[] = (new Td())
                        ->items([
                            (new Text(null, Html::escapeHTML($rs->link_href))),
                        ]);
                    $cols[] = (new Td())
                        ->items([
                            (new Text(null, Html::escapeHTML($rs->link_lang))),
                        ]);
                }

                $rows[] = (new Tr('l_' . $rs->link_id))
                    ->class('line')
                    ->cols($cols);
            }

            $table = (new Table())
                ->class('dragable')
                ->items([
                    (new Thead())->rows([
                        (new Tr())->cols([
                            (new Th())->colspan(3)->text(__('Title')),
                            (new Th())->text(__('Description')),
                            (new Th())->text(__('URL')),
                            (new Th())->text(__('Lang')),
                        ]),
                    ]),
                    (new Tbody('links-list'))->rows(
                        $rows,
                    ),
                ]);

            $item = (new Form('links-form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields([
                    (new Div())
                        ->class('table-outer')
                        ->items([
                            $table,
                        ]),
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Para())
                                ->class(['col', 'checkboxes-helpers']),
                            (new Para())
                                ->class(['col', 'right'])
                                ->items([
                                    (new Submit(['removeaction','remove-action'], __('Delete selected links')))
                                        ->class('delete'),
                                ]),
                        ]),
                    (new Para())
                        ->class(['form-buttons', 'clear'])
                        ->items([
                            ...My::hiddenFields(),
                            (new Hidden('links_order', '')),
                            (new Submit(['saveorder'], __('Save order'))),
                            (new Button(['back'], __('Back')))
                                ->class(['go-back', 'reset', 'hidden-if-no-js']),
                        ]),
                ]);
        } else {
            // No links nor categories
            $item = (new Div())->items([
                (new Para())->items([
                    (new Text(null, __('The link list is empty'))),
                ]),
            ]);
        }

        echo (new Div('main-list'))
            ->class('multi-part')
            ->title(My::name())
            ->items([
                $item,
            ])
        ->render();

        // Tab: Add a link

        echo (new Div('add-link'))
            ->class('multi-part')
            ->title(__('Add a link'))
            ->items([
                (new Form('add-link-form'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text('h3', __('Add a new link'))),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Para())->items([
                            (new Label((new Text('span', '*'))->render() . __('Title:')))
                                ->class('required')
                                ->for('link_title'),
                            (new Input('link_title'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML(App::backend()->link_title))
                                ->required(true)
                                ->placeholder(__('Title'))
                                ->lang(App::auth()->getInfo('user_lang'))
                                ->spellcheck(true)
                                ->title(__('Required field')),
                        ]),
                        (new Para())->items([
                            (new Label((new Text('span', '*'))->render() . __('URL:')))
                                ->class('required')
                                ->for('link_href'),
                            (new Url('link_href'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML(App::backend()->link_href))
                                ->required(true)
                                ->placeholder(__('URL'))
                                ->title(__('Required field')),
                        ]),
                        (new Para())->items([
                            (new Label(__('Description:')))
                                ->for('link_desc'),
                            (new Input('link_desc'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML(App::backend()->link_desc))
                                ->lang(App::auth()->getInfo('user_lang'))
                                ->spellcheck(true),
                        ]),
                        (new Para())->items([
                            (new Label(__('Language:')))
                                ->for('link_lang'),
                            (new Select('link_lang'))
                                ->items($lang_combo)
                                ->default(App::backend()->link_lang),
                        ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                ...My::hiddenFields(),
                                (new Submit(['add_link'], __('Save'))),
                                (new Button('back'))
                                    ->class(['go-back', 'reset', 'hidden-if-no-js'])
                                    ->value(__('Back')),
                            ]),
                    ]),
            ])
        ->render();

        // Tab: Add a category

        echo (new Div('add-cat'))
            ->class('multi-part')
            ->title(__('Add a category'))
            ->items([
                (new Form('add-link-form'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text('h3', __('Add a new category'))),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Para())->items([
                            (new Input('cat_title'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML(App::backend()->cat_title))
                                ->required(true)
                                ->placeholder(__('Title'))
                                ->lang(App::auth()->getInfo('user_lang'))
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        (new Text('span', '*'))->render() . __('Title:'),
                                        Label::INSIDE_TEXT_BEFORE
                                    ))
                                )
                                ->title(__('Required field')),
                        ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                ...My::hiddenFields(),
                                (new Submit(['add_cat'], __('Save'))),
                                (new Button('back'))
                                    ->class(['go-back', 'reset', 'hidden-if-no-js'])
                                    ->value(__('Back')),
                            ]),
                    ]),
            ])
        ->render();

        // Tab: Import links

        if (App::backend()->imported === null) {
            $form = (new Form('import-links-form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->enctype('multipart/form-data')
                ->fields([
                    (new Text('h3', __('Import links'))),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                    (new Para())->items([
                        (new File('links_file'))
                            ->required(true)
                            ->label(
                                (new Label(
                                    (new Text('span', '*'))->render() . __('OPML or XBEL File:'),
                                    Label::INSIDE_TEXT_BEFORE
                                ))
                            )
                            ->title(__('Required field')),
                    ]),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            ...My::hiddenFields(),
                            (new Submit(['import_links'], __('Import'))),
                            (new Button('back'))
                                ->class(['go-back', 'reset', 'hidden-if-no-js'])
                                ->value(__('Back')),
                        ]),
                ]);
        } else {
            $fields = [];
            if (empty(App::backend()->imported)) {
                $fields[] = (new Para())->items([
                    (new Text(null, __('Nothing to import'))),
                    ...My::hiddenFields(),
                ]);
            } else {
                $rows = [];
                $i    = 0;
                foreach (App::backend()->imported as $entry) {
                    $url   = Html::escapeHTML((string) $entry->link);
                    $title = Html::escapeHTML((string) $entry->title);
                    $desc  = Html::escapeHTML((string) $entry->desc);

                    $rows[] = (new Tr())
                        ->items([
                            (new Td())->class('minimal')->items([
                                (new Checkbox(['entries[]']))->value($i),
                            ]),
                            (new Td())->items([
                                (new Link())->href($url)->text($title),
                                (new Hidden(['url[' . $i . ']'], $url)),
                                (new Hidden(['title[' . $i . ']'], $title)),
                            ]),
                            (new Td())->items([
                                (new Text(null, $desc)),
                                (new Hidden(['desc[' . $i . ']'], $desc)),
                            ]),
                        ]);
                    $i++;
                }

                $fields[] = (new Table())
                    ->class(['clear', 'maximal'])
                    ->items([
                        (new Thead())
                            ->rows([
                                (new Tr())
                                    ->cols([
                                        (new Th())->colspan(2)->text(__('Title')),
                                        (new Th())->text(__('Description')),
                                    ]),
                            ]),
                        (new Tbody())
                            ->rows($rows),
                    ]);

                $fields[] = (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Para())
                            ->class(['col', 'checkboxes-helpers']),
                        (new Para())
                            ->class(['col', 'right', 'form-buttons'])
                            ->items([
                                ...My::hiddenFields(),
                                (new Submit(['cancel_import'], __('Cancel')))
                                    ->class('reset'),
                                (new Submit(['import_links_do'], __('Import'))),
                            ]),
                    ]);
            }
            $form = (new Form('import-links-form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields([
                    (new Text('h3', __('Import links'))),
                    ...$fields,
                ]);
        }

        echo (new Div('import-links'))
            ->class('multi-part')
            ->title(__('Import links'))
            ->items([
                $form,
            ])
        ->render();

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
