<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Dotclear\App;
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
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Plugin\blogroll\Status\Link as StatusLink;
use Exception;

/**
 * @brief   The module manage blogroll process.
 * @ingroup blogroll
 */
class ManageEdit
{
    use TraitProcess;

    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE) && !empty($_REQUEST['edit']) && !empty($_REQUEST['id']));

        if (self::status()) {
            App::backend()->id = Html::escapeHTML(is_numeric($_REQUEST['id']) ? (string) $_REQUEST['id'] : '0');
            App::backend()->rs = null;

            try {
                $blogroll          = new Blogroll(App::blog());
                App::backend()->rs = $blogroll->getLink(App::backend()->id);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            if (!App::error()->flag() && App::backend()->rs instanceof MetaRecord && !App::backend()->rs->isEmpty()) {
                App::backend()->link_title  = App::backend()->rs->link_title;
                App::backend()->link_href   = App::backend()->rs->link_href;
                App::backend()->link_desc   = App::backend()->rs->link_desc;
                App::backend()->link_lang   = App::backend()->rs->link_lang;
                App::backend()->link_xfn    = App::backend()->rs->link_xfn;
                App::backend()->link_status = App::backend()->rs->link_status;
            } else {
                App::backend()->link_title  = '';
                App::backend()->link_href   = '';
                App::backend()->link_desc   = '';
                App::backend()->link_lang   = '';
                App::backend()->link_xfn    = '';
                App::backend()->link_status = StatusLink::ONLINE;
                App::error()->add(__('No such link or title'));
            }
        }

        return self::status();
    }

    public static function process(): bool
    {
        $blogroll = new Blogroll(App::blog());

        // Ensure ID is numeric-string
        App::backend()->id = is_numeric(App::backend()->id) ? (string) App::backend()->id : '0';

        if (App::backend()->rs instanceof MetaRecord && !App::backend()->rs->is_cat && !empty($_POST['edit_link'])) {
            // Update a link

            App::backend()->link_title  = is_string($link_title = $_POST['link_title']) ? $link_title : '';
            App::backend()->link_href   = is_string($link_href = $_POST['link_href']) ? $link_href : '';
            App::backend()->link_desc   = is_string($link_desc = $_POST['link_desc']) ? $link_desc : '';
            App::backend()->link_lang   = is_string($link_lang = $_POST['link_lang']) ? $link_lang : '';
            App::backend()->link_status = is_numeric($link_status = $_POST['link_status']) ? (int) $link_status : StatusLink::ONLINE;

            App::backend()->link_xfn = '';

            if (!empty($_POST['identity']) && is_string($_POST['identity'])) {
                App::backend()->link_xfn .= $_POST['identity'];
            } else {
                if (!empty($_POST['friendship']) && is_string($_POST['friendship'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['friendship'];
                }
                if (!empty($_POST['physical'])) {
                    App::backend()->link_xfn .= ' met';
                }
                if (!empty($_POST['professional']) && is_array($_POST['professional'])) {
                    App::backend()->link_xfn .= ' ' . implode(' ', $_POST['professional']);
                }
                if (!empty($_POST['geographical']) && is_string($_POST['geographical'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['geographical'];
                }
                if (!empty($_POST['family']) && is_string($_POST['family'])) {
                    App::backend()->link_xfn .= ' ' . $_POST['family'];
                }
                if (!empty($_POST['romantic']) && is_array($_POST['romantic'])) {
                    App::backend()->link_xfn .= ' ' . implode(' ', $_POST['romantic']);
                }
            }

            try {
                $blogroll->updateLink(
                    App::backend()->id,
                    App::backend()->link_title,
                    App::backend()->link_href,
                    App::backend()->link_desc,
                    App::backend()->link_lang,
                    trim((string) App::backend()->link_xfn),
                    App::backend()->link_status,
                );
                App::backend()->notices()->addSuccessNotice(__('Link has been successfully updated'));
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

            App::backend()->link_desc = is_string($link_desc = $_POST['link_desc']) ? $link_desc : '';

            try {
                $blogroll->updateCategory(App::backend()->id, App::backend()->link_desc);
                App::backend()->notices()->addSuccessNotice(__('Category has been successfully updated'));
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
        $blogroll = new Blogroll(App::blog());

        // Languages combo
        $links      = $blogroll->getLangs(['order' => 'asc']);
        $lang_combo = App::backend()->combos()->getLangsCombo($links, true, true);

        $head = App::backend()->page()->jsConfirmClose('blogroll_cat', 'blogroll_link');

        // Status combo
        App::backend()->status_combo = (new StatusLink())->combo();

        // Ensure ID is numeric-string
        App::backend()->id = is_numeric(App::backend()->id) ? (string) App::backend()->id : '0';

        /**
         * @var ?MetaRecord $rs
         */
        $rs     = App::backend()->rs;
        $is_cat = $rs?->is_cat;

        $link_title  = is_string($link_title = App::backend()->link_title) ? $link_title : '';
        $link_href   = is_string($link_href = App::backend()->link_href) ? $link_href : '';
        $link_desc   = is_string($link_desc = App::backend()->link_desc) ? $link_desc : '';
        $link_lang   = is_string($link_lang = App::backend()->link_lang) ? $link_lang : '';
        $link_xfn    = is_string($link_xfn = App::backend()->link_xfn) ? trim($link_xfn) : '';
        $link_status = is_numeric($link_status = App::backend()->link_status) ? (int) $link_status : StatusLink::ONLINE;

        if ($is_cat) {
            $cat_title = is_string($cat_title = App::backend()->cat_title) ? $cat_title : '';
        }

        $img_status = (new StatusLink())->image($link_status)->render();

        $user_lang = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : '';

        App::backend()->page()->openModule(My::name(), $head);

        echo
        App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())   => '',
                My::name()                              => App::backend()->getPageURL(),
                ($is_cat ? __('Category') : __('Link')) => '',
            ]
        ) .
        App::backend()->notices()->getNotices();

        echo
            (new Para())->items([
                (new Link())->class('back')->href(App::backend()->getPageURL())->text(__('Return to blogroll')),
            ])
        ->render();

        if (App::backend()->rs instanceof MetaRecord) {
            if ($is_cat) {
                echo (new Form('blogroll_cat'))
                    ->class('fieldset')
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text('h3', __('Edit category'))),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                        (new Para())->items([
                            (new Input('link_desc'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML($link_desc))
                                ->required(true)
                                ->placeholder(__('Title'))
                                ->lang($user_lang)
                                ->spellcheck(true)
                                ->label(
                                    (new Label(
                                        (new Span('*'))->render() . __('Title:'),
                                        Label::INSIDE_TEXT_BEFORE
                                    ))
                                )
                                ->title(__('Required field')),
                        ]),
                        (new Para())->class('link-status')->items([
                            (new Select('link_status'))
                                ->items(App::backend()->status_combo)
                                ->default($link_status)
                                ->label(new Label(__('Category status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
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
                $xfn = explode(' ', $link_xfn);

                echo (new Form('blogroll_link'))
                    ->class('fieldset')
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text('h3', __('Edit link'))),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                        (new Div())->class('two-cols')->items([
                            (new Div())->class('col30')->items([
                                (new Para())->items([
                                    (new Label((new Span('*'))->render() . __('Title:')))
                                        ->class('required')
                                        ->for('link_title'),
                                    (new Input('link_title'))
                                        ->size(30)
                                        ->maxlength(255)
                                        ->value(Html::escapeHTML($link_title))
                                        ->required(true)
                                        ->placeholder(__('Title'))
                                        ->lang($user_lang)
                                        ->spellcheck(true)
                                        ->title(__('Required field')),
                                ]),
                                (new Para())->items([
                                    (new Label((new Span('*'))->render() . __('URL:')))
                                        ->class('required')
                                        ->for('link_href'),
                                    (new Url('link_href'))
                                        ->size(30)
                                        ->maxlength(255)
                                        ->value(Html::escapeHTML($link_href))
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
                                        ->value(Html::escapeHTML($link_desc))
                                        ->lang($user_lang)
                                        ->spellcheck(true),
                                ]),
                                (new Para())->items([
                                    (new Label(__('Language:')))
                                        ->for('link_lang'),
                                    (new Select('link_lang'))
                                        ->items($lang_combo)    // @phpstan-ignore-line variable type is not precise enough
                                        ->default($link_lang),
                                ]),
                                (new Para())->class('link-status')->items([
                                    (new Select('link_status'))
                                        ->items(App::backend()->status_combo)
                                        ->default($link_status)
                                        ->label(new Label(__('Link status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
                                ]),
                            ]),

                            // XFN nightmare
                            (new Div())->class('col70')->items([
                                (new Text('h4', __('XFN information'))),
                                (new Note())
                                    ->class('form-note')
                                    ->text(sprintf(
                                        __('More information on <a href="%s">Wikipedia</a> website'),
                                        'https://en.wikipedia.org/wiki/XHTML_Friends_Network'
                                    )),
                                (new Div())->class('table-outer')->items([
                                    (new Table())->class('noborder')->items([
                                        (new Tr())->class('line')->items([
                                            (new Th())->text(__('_xfn_Me'))->scope('row'),
                                            (new Td())->items([
                                                (new Checkbox(['identity'], ($link_xfn === 'me')))
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

        App::backend()->page()->closeModule();
    }
}
