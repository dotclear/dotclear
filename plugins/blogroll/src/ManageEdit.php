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
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module manage blogroll process.
 * @ingroup blogroll
 */
class ManageEdit extends Process
{
    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE) && !empty($_REQUEST['edit']) && !empty($_REQUEST['id']));

        if (self::status()) {
            App::backend()->id = Html::escapeHTML($_REQUEST['id']);

            App::backend()->rs = null;

            try {
                App::backend()->rs = App::backend()->blogroll->getLink(App::backend()->id);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            if (!App::error()->flag() && App::backend()->rs->isEmpty()) {
                App::backend()->link_title = '';
                App::backend()->link_href  = '';
                App::backend()->link_desc  = '';
                App::backend()->link_lang  = '';
                App::backend()->link_xfn   = '';
                App::error()->add(__('No such link or title'));
            } else {
                App::backend()->link_title = App::backend()->rs->link_title;
                App::backend()->link_href  = App::backend()->rs->link_href;
                App::backend()->link_desc  = App::backend()->rs->link_desc;
                App::backend()->link_lang  = App::backend()->rs->link_lang;
                App::backend()->link_xfn   = App::backend()->rs->link_xfn;
            }
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (App::backend()->rs instanceof MetaRecord && !App::backend()->rs->is_cat && !empty($_POST['edit_link'])) {
            // Update a link

            App::backend()->link_title = Html::escapeHTML($_POST['link_title']);
            App::backend()->link_href  = Html::escapeHTML($_POST['link_href']);
            App::backend()->link_desc  = Html::escapeHTML($_POST['link_desc']);
            App::backend()->link_lang  = Html::escapeHTML($_POST['link_lang']);

            App::backend()->link_xfn = '';

            if (!empty($_POST['identity'])) {
                App::backend()->link_xfn .= $_POST['identity'];
            } else {
                if (!empty($_POST['friendship'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['friendship'];
                }
                if (!empty($_POST['physical'])) {
                    App::backend()->link_xfn .= ' met';
                }
                if (!empty($_POST['professional'])) {
                    App::backend()->link_xfn .= ' ' . implode(' ', $_POST['professional']);
                }
                if (!empty($_POST['geographical'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['geographical'];
                }
                if (!empty($_POST['family'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['family'];
                }
                if (!empty($_POST['romantic'])) {
                    App::backend()->link_xfn .= ' ' . implode(' ', $_POST['romantic']);
                }
            }

            try {
                App::backend()->blogroll->updateLink(
                    App::backend()->id,
                    App::backend()->link_title,
                    App::backend()->link_href,
                    App::backend()->link_desc,
                    App::backend()->link_lang,
                    trim((string) App::backend()->link_xfn)
                );
                Notices::addSuccessNotice(__('Link has been successfully updated'));
                My::redirect([
                    'edit' => 1,    // Used by Manage
                    'id'   => App::backend()->id,
                ]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (App::backend()->rs instanceof MetaRecord && App::backend()->rs->is_cat && !empty($_POST['edit_cat'])) {
            // Update a category

            App::backend()->link_desc = Html::escapeHTML($_POST['link_desc']);

            try {
                App::backend()->blogroll->updateCategory(App::backend()->id, App::backend()->link_desc);
                Notices::addSuccessNotice(__('Category has been successfully updated'));
                My::redirect([
                    'edit' => 1,    // Used by Manage
                    'id'   => App::backend()->id,
                ]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        // Languages combo
        $links      = App::backend()->blogroll->getLangs(['order' => 'asc']);
        $lang_combo = Combos::getLangsCombo($links, true);

        $head = Page::jsConfirmClose('blogroll_cat', 'blogroll_link');

        Page::openModule(My::name(), $head);

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())                      => '',
                My::name()                                                 => App::backend()->getPageURL(),
                (App::backend()->rs->is_cat ? __('Category') : __('Link')) => '',
            ]
        ) .
        Notices::getNotices();

        echo
            (new Para())->items([
                (new Link())->class('back')->href(App::backend()->getPageURL())->text(__('Return to blogroll')),
            ])
        ->render();

        if (App::backend()->rs instanceof MetaRecord) {
            if (App::backend()->rs->is_cat) {
                echo (new Form('blogroll_cat'))
                    ->class('fieldset')
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text('h3', __('Edit category'))),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Para())->items([
                            (new Input('link_desc'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML(App::backend()->link_desc))
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
                        (new Para())->items([
                            ...My::hiddenFields(),
                            (new Hidden('edit', '1')),    // Used by Manage
                            (new Hidden('id', App::backend()->id)),
                            (new Submit(['edit_cat'], __('Save'))),
                        ]),
                    ])
                ->render();
            } else {
                // Extract xfn items
                $xfn = explode(' ', App::backend()->link_xfn);

                echo (new Form('blogroll_link'))
                    ->class('fieldset')
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text('h3', __('Edit link'))),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Div())->class('two-cols')->items([
                            (new Div())->class('col30')->items([
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
                            ]),

                            // XFN nightmare
                            (new Div())->class('col70')->items([
                                (new Text('h4', __('XFN information'))),
                                (new Note())
                                    ->class('form-note')
                                    ->text(__('More information on <a href="https://en.wikipedia.org/wiki/XHTML_Friends_Network">Wikipedia</a> website')),
                                (new Div())->class('table-outer')->items([
                                    (new Table())->class('noborder')->items([
                                        (new Tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Me'))->scope('row'),
                                            (new Td())->items([
                                                (new Checkbox(['identity'], (App::backend()->link_xfn === 'me')))
                                                    ->value('me')
                                                    ->label((new Label(__('_xfn_Another link for myself'), Label::INSIDE_TEXT_AFTER))),
                                            ]),
                                        ]),
                                        (new tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Friendship'))->scope('row'),
                                            (new Td())->items([
                                                (new Radio(['friendship'], in_array('contact', $xfn)))
                                                    ->value('contact')
                                                    ->label((new Label(__('_xfn_Contact'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(['friendship'], in_array('acquaintance', $xfn)))
                                                    ->value('acquaintance')
                                                    ->label((new Label(__('_xfn_Acquaintance'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(['friendship'], in_array('friend', $xfn)))
                                                    ->value('friend')
                                                    ->label((new Label(__('_xfn_Friend'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(
                                                    ['friendship'],
                                                    !in_array('contact', $xfn) && !in_array('acquaintance', $xfn) && !in_array('friend', $xfn)
                                                ))
                                                    ->value('')
                                                    ->label((new Label(__('None'), Label::INSIDE_TEXT_AFTER))),
                                            ]),
                                        ]),
                                        (new Tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Physical'))->scope('row'),
                                            (new Td())->items([
                                                (new Checkbox(['physical'], in_array('met', $xfn)))
                                                    ->value('met')
                                                    ->label((new Label(__('_xfn_Met'), Label::INSIDE_TEXT_AFTER))),
                                            ]),
                                        ]),
                                        (new Tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Professional'))->scope('row'),
                                            (new Td())->items([
                                                (new Checkbox(['professional[]'], in_array('co-worker', $xfn)))
                                                    ->value('co-worker')
                                                    ->label((new Label(__('_xfn_Co-worker'), Label::INSIDE_TEXT_AFTER))),
                                                (new Checkbox(['professional[]'], in_array('colleague', $xfn)))
                                                    ->value('colleague')
                                                    ->label((new Label(__('_xfn_Colleague'), Label::INSIDE_TEXT_AFTER))),
                                            ]),
                                        ]),
                                        (new tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Geographical'))->scope('row'),
                                            (new Td())->items([
                                                (new Radio(['geographical'], in_array('co-resident', $xfn)))
                                                    ->value('co-resident')
                                                    ->label((new Label(__('_xfn_Co-resident'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(['geographical'], in_array('neighbor', $xfn)))
                                                    ->value('neighbor')
                                                    ->label((new Label(__('_xfn_Neighbor'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(
                                                    ['geographical'],
                                                    !in_array('co-resident', $xfn) && !in_array('neighbor', $xfn)
                                                ))
                                                    ->value('')
                                                    ->label((new Label(__('None'), Label::INSIDE_TEXT_AFTER))),
                                            ]),
                                        ]),
                                        (new tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Family'))->scope('row'),
                                            (new Td())->items([
                                                (new Radio(['family'], in_array('child', $xfn)))
                                                    ->value('child')
                                                    ->label((new Label(__('_xfn_Child'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(['family'], in_array('parent', $xfn)))
                                                    ->value('parent')
                                                    ->label((new Label(__('_xfn_Parent'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(['family'], in_array('sibling', $xfn)))
                                                    ->value('sibling')
                                                    ->label((new Label(__('_xfn_Sibling'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(['family'], in_array('spouse', $xfn)))
                                                    ->value('spouse')
                                                    ->label((new Label(__('_xfn_Spouse'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(['family'], in_array('kin', $xfn)))
                                                    ->value('kin')
                                                    ->label((new Label(__('_xfn_Kin'), Label::INSIDE_TEXT_AFTER))),
                                                (new Radio(
                                                    ['family'],
                                                    !in_array('child', $xfn) && !in_array('parent', $xfn) && !in_array('sibling', $xfn) && !in_array('spouse', $xfn) && !in_array('kin', $xfn)
                                                ))
                                                    ->value('')
                                                    ->label((new Label(__('None'), Label::INSIDE_TEXT_AFTER))),
                                            ]),
                                        ]),
                                        (new tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Romantic'))->scope('row'),
                                            (new Td())->items([
                                                (new Checkbox(['romantic[]'], in_array('muse', $xfn)))
                                                    ->value('muse')
                                                    ->label((new Label(__('_xfn_Muse'), Label::INSIDE_TEXT_AFTER))),
                                                (new Checkbox(['romantic[]'], in_array('crush', $xfn)))
                                                    ->value('crush')
                                                    ->label((new Label(__('_xfn_Crush'), Label::INSIDE_TEXT_AFTER))),
                                                (new Checkbox(['romantic[]'], in_array('date', $xfn)))
                                                    ->value('date')
                                                    ->label((new Label(__('_xfn_Date'), Label::INSIDE_TEXT_AFTER))),
                                                (new Checkbox(['romantic[]'], in_array('sweetheart', $xfn)))
                                                    ->value('sweetheart')
                                                    ->label((new Label(__('_xfn_Sweetheart'), Label::INSIDE_TEXT_AFTER))),
                                            ]),
                                        ]),
                                    ]),
                                ]),
                            ]),
                        ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                ...My::hiddenFields(),
                                (new Hidden('edit', '1')),    // Used by Manage
                                (new Hidden('id', App::backend()->id)),
                                (new Submit(['edit_link'], __('Save'))),
                            ]),
                    ])
                ->render();
            }
        }

        Page::closeModule();
    }
}
