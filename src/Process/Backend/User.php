<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @since 2.27 Before as admin/user.php
 *
 * @todo switch Helper/Html/Form/...
 */
class User extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        App::backend()->page_title = __('New user');

        App::backend()->user_id          = '';
        App::backend()->user_super       = '';
        App::backend()->user_status      = '';
        App::backend()->user_change_pwd  = '';
        App::backend()->user_name        = '';
        App::backend()->user_firstname   = '';
        App::backend()->user_displayname = '';
        App::backend()->user_email       = '';
        App::backend()->user_url         = '';
        App::backend()->user_lang        = App::auth()->getInfo('user_lang');
        App::backend()->user_tz          = App::auth()->getInfo('user_tz');
        App::backend()->user_post_status = App::status()->post()::PENDING;

        App::backend()->user_options = App::users()->userDefaults();

        App::backend()->user_profile_mails = '';
        App::backend()->user_profile_urls  = '';

        # Formaters combo
        App::backend()->formaters_combo = Combos::getFormatersCombo();

        # Posts status combo !
        App::backend()->status_combo = App::status()->post()->combo();

        # Language codes
        App::backend()->lang_combo = Combos::getAdminLangsCombo();

        # Get user if we have an ID
        if (!empty($_REQUEST['id'])) {
            try {
                App::backend()->rs = App::users()->getUser($_REQUEST['id']);

                App::backend()->user_id          = App::backend()->rs->user_id;
                App::backend()->user_super       = App::backend()->rs->user_super;
                App::backend()->user_status      = App::backend()->rs->user_status;
                App::backend()->user_change_pwd  = App::backend()->rs->user_change_pwd;
                App::backend()->user_name        = App::backend()->rs->user_name;
                App::backend()->user_firstname   = App::backend()->rs->user_firstname;
                App::backend()->user_displayname = App::backend()->rs->user_displayname;
                App::backend()->user_email       = App::backend()->rs->user_email;
                App::backend()->user_url         = App::backend()->rs->user_url;
                App::backend()->user_lang        = App::backend()->rs->user_lang;
                App::backend()->user_tz          = App::backend()->rs->user_tz;
                App::backend()->user_post_status = App::backend()->rs->user_post_status;

                App::backend()->user_options = array_merge(App::backend()->user_options, App::backend()->rs->options());

                $user_prefs = App::userPreferences()->createFromUser(App::backend()->user_id, 'profile');

                App::backend()->user_profile_mails = $user_prefs->profile->mails;
                App::backend()->user_profile_urls  = $user_prefs->profile->urls;

                App::backend()->page_title = App::backend()->user_id;
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (isset($_POST['user_name'])) {
            // Add or update user

            try {
                if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                $cur = App::auth()->openUserCursor();

                $cur->user_id          = $_POST['user_id'];
                $cur->user_super       = App::backend()->user_super = empty($_POST['user_super']) ? 0 : 1;
                $cur->user_status      = App::backend()->user_status = App::status()->user()->level((int) $_POST['user_status']);
                $cur->user_name        = App::backend()->user_name = Html::escapeHTML($_POST['user_name']);
                $cur->user_firstname   = App::backend()->user_firstname = Html::escapeHTML($_POST['user_firstname']);
                $cur->user_displayname = App::backend()->user_displayname = Html::escapeHTML($_POST['user_displayname']);
                $cur->user_email       = App::backend()->user_email = Html::escapeHTML($_POST['user_email']);
                $cur->user_url         = App::backend()->user_url = Html::escapeHTML($_POST['user_url']);
                $cur->user_lang        = App::backend()->user_lang = Html::escapeHTML($_POST['user_lang']);
                $cur->user_tz          = App::backend()->user_tz = Html::escapeHTML($_POST['user_tz']);
                $cur->user_post_status = App::backend()->user_post_status = (int) $_POST['user_post_status'];

                if (App::backend()->user_id && $cur->user_id == App::auth()->userID() && App::auth()->isSuperAdmin()) {
                    // force super_user to true if current user
                    $cur->user_super = App::backend()->user_super = true;
                }
                if (App::backend()->user_id && $cur->user_id == App::auth()->userID()) {
                    // force user_status to 1 if current user
                    $cur->user_status = App::backend()->user_status = true;
                }
                if (App::auth()->allowPassChange()) {
                    $cur->user_change_pwd = empty($_POST['user_change_pwd']) ? 0 : 1;
                }

                if (!empty($_POST['new_pwd'])) {
                    if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                        throw new Exception(__("Passwords don't match"));
                    }
                    $cur->user_pwd = $_POST['new_pwd'];
                }

                $user_options = App::backend()->user_options;

                $user_options['post_format'] = Html::escapeHTML($_POST['user_post_format']);
                $user_options['edit_size']   = (int) $_POST['user_edit_size'];

                if ($user_options['edit_size'] < 1) {
                    $user_options['edit_size'] = 10;
                }

                App::backend()->user_options = $user_options;

                $cur->user_options = new ArrayObject(App::backend()->user_options);

                if (App::backend()->user_id) {
                    // Update user

                    # --BEHAVIOR-- adminBeforeUserUpdate -- Cursor, string
                    App::behavior()->callBehavior('adminBeforeUserUpdate', $cur, App::backend()->user_id);

                    $new_id = App::users()->updUser(App::backend()->user_id, $cur);

                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = $urls = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                    }
                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                    }

                    $user_prefs = App::userPreferences()->createFromUser($new_id, 'profile');
                    $user_prefs->profile->put('mails', $mails, 'string');
                    $user_prefs->profile->put('urls', $urls, 'string');

                    # --BEHAVIOR-- adminAfterUserUpdate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterUserUpdate', $cur, $new_id);

                    if (App::backend()->user_id == App::auth()->userID() && App::backend()->user_id != $new_id) {
                        App::session()->destroy();
                    }

                    Notices::addSuccessNotice(__('User has been successfully updated.'));
                    App::backend()->url()->redirect('admin.user', ['id' => $new_id]);
                } else {
                    // Add user

                    if (App::users()->getUsers(['user_id' => $cur->user_id], true)->f(0) > 0) {
                        throw new Exception(sprintf(__('User "%s" already exists.'), Html::escapeHTML($cur->user_id)));
                    }

                    # --BEHAVIOR-- adminBeforeUserCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforeUserCreate', $cur);

                    $new_id = App::users()->addUser($cur);

                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = $urls = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                    }
                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                    }
                    $user_prefs = App::userPreferences()->createFromUser($new_id, 'profile');
                    $user_prefs->profile->put('mails', $mails, 'string');
                    $user_prefs->profile->put('urls', $urls, 'string');

                    # --BEHAVIOR-- adminAfterUserCreate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterUserCreate', $cur, $new_id);

                    Notices::addSuccessNotice(__('User has been successfully created.'));

                    if (!$cur->user_super) {
                        Notices::addWarningNotice(__('User has no permission, he will not be able to login yet. See below to add some.'));
                    }
                    if (App::status()->user()->isRestricted((int) $cur->user_status)) {
                        Notices::addWarningNotice(__('User is disabled, he will not be able to login yet.'));
                    }
                    if (!empty($_POST['saveplus'])) {
                        App::backend()->url()->redirect('admin.user');
                    } else {
                        App::backend()->url()->redirect('admin.user', ['id' => $new_id]);
                    }
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            App::backend()->page_title,
            Page::jsConfirmClose('user-form') .
            Page::jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            Page::jsLoad('js/pwstrength.js') .
            Page::jsLoad('js/_user.js') .
            # --BEHAVIOR-- adminUserHeaders --
            App::behavior()->callBehavior('adminUserHeaders'),
            Page::breadcrumb(
                [
                    __('System')               => '',
                    __('Users')                => App::backend()->url()->get('admin.users'),
                    App::backend()->page_title => '',
                ]
            )
        );

        if (!empty($_GET['upd'])) {
            Notices::success(__('User has been successfully updated.'));
        }

        if (!empty($_GET['add'])) {
            Notices::success(__('User has been successfully created.'));
        }

        $super_disabled = App::backend()->user_super && App::backend()->user_id === App::auth()->userID();

        echo (new Form('user-form'))
            ->method('post')
            ->action(App::backend()->url()->get('admin.user'))
            ->fields([
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h3', __('User profile'))),
                                (new Note())
                                    ->class('form-note')
                                    ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                                (new Para())
                                    ->items([
                                        (new Input('user_id'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_id))
                                            ->required(true)
                                            ->placeholder(__('Login'))
                                            ->autocomplete('username')
                                            ->extra('aria-describedby="user_id_help user_id_warning"')
                                            ->label((new Label((new Text('span', '*'))->render() . __('User ID:'), Label::OL_TF))->class('required')),
                                    ]),
                                (new Note('user_id_help'))
                                    ->class(['form-note', 'info'])
                                    ->text(__('At least 2 characters using letters, numbers or symbols.')),
                                App::backend()->user_id === App::auth()->userID() ?
                                    (new Note('user_id_warning'))
                                        ->class('warning')
                                        ->text(__('Warning:') . ' ' . __('If you change your username, you will have to log in again.')) :
                                    (new None()),
                                (new Para())
                                    ->items([
                                        App::backend()->user_id ?
                                            (new Password('new_pwd'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->autocomplete('new-password')
                                                ->label((new Label(__('New password:'), Label::OL_TF))) :
                                            (new Password('new_pwd'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->required(true)
                                                ->placeholder(__('Password'))
                                                ->autocomplete('new-password')
                                                ->extra('aria-describedby="new_pwd_help"')
                                                ->label((new Label((new Text('span', '*'))->render() . __('Password:'), Label::OL_TF))->class('required')),
                                    ]),
                                (new Note('new_pwd_help'))
                                    ->class(['form-note', 'info'])
                                    ->text(__('Password must contain at least 6 characters.')),
                                (new Para())
                                    ->items([
                                        App::backend()->user_id ?
                                            (new Password('new_pwd_c'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->autocomplete('new-password')
                                                ->label((new Label(__('Confirm password:'), Label::OL_TF))) :
                                            (new Password('new_pwd_c'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->required(true)
                                                ->placeholder(__('Password'))
                                                ->autocomplete('new-password')
                                                ->label((new Label((new Text('span', '*'))->render() . __('Confirm password:'), Label::OL_TF))->class('required')),
                                    ]),
                                App::auth()->allowPassChange() ?
                                    (new Para())
                                        ->items([
                                            (new Checkbox('user_change_pwd', (bool) App::backend()->user_change_pwd))
                                                ->value('1')
                                                ->label((new Label(__('Password change required to connect'), Label::IL_FT))),
                                        ]) :
                                    (new None()),
                                (new Para())
                                    ->items([
                                        (new Checkbox($super_disabled ? 'user_super_off' : 'user_super', (bool) App::backend()->user_super))
                                            ->value('1')
                                            ->disabled($super_disabled)
                                            ->label((new Label(__('Super administrator'), Label::IL_FT))),
                                    ]),
                                (new Para())
                                    ->items([
                                        App::backend()->user_id !== App::auth()->userID() ?
                                        (new Select('user_status'))
                                            ->items(App::status()->user()->combo())
                                            ->default(App::backend()->user_status)
                                            ->label((new Label(__('Status:'), Label::OL_TF))) :
                                        (new Hidden(['user_status', App::backend()->user_status])),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_name'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_name))
                                            ->autocomplete('family-name')
                                            ->label((new Label(__('Last Name:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_firstname'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_firstname))
                                            ->autocomplete('given-name')
                                            ->label((new Label(__('First Name:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_displayname'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_displayname))
                                            ->autocomplete('nickname')
                                            ->label((new Label(__('Display name:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Email('user_email'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_email))
                                            ->autocomplete('email')
                                            ->extra('aria-describedby="user_email_help"')
                                            ->label((new Label(__('Email:'), Label::OL_TF))),
                                    ]),
                                (new Note('user_email_help'))
                                    ->class('form-note')
                                    ->text(__('Mandatory for password recovering procedure.')),
                                (new Para())
                                    ->items([
                                        (new Input('user_profile_mails'))
                                            ->size(50)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_profile_mails))
                                            ->label((new Label(__('Alternate emails (comma separated list):'), Label::OL_TF))),
                                    ]),
                                (new Note('sanitize_emails'))
                                    ->class(['form-note', 'info'])
                                    ->text(__('Invalid emails will be automatically removed from list.')),
                                (new Para())
                                    ->items([
                                        (new Url('user_url'))
                                            ->size(30)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_url))
                                            ->autocomplete('url')
                                            ->label((new Label(__('URL:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_profile_urls'))
                                            ->size(50)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->user_profile_urls))
                                            ->label((new Label(__('Alternate URLs (comma separated list):'), Label::OL_TF))),
                                    ]),
                                (new Note('sanitize_urls'))
                                    ->class(['form-note', 'info'])
                                    ->text(__('Invalid URLs will be automatically removed from list.')),
                            ]),
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h3', __('Options'))),
                                (new Text('h4', __('Interface'))),
                                (new Para())
                                    ->items([
                                        (new Select('user_lang'))
                                            ->items(App::backend()->lang_combo)
                                            ->default(App::backend()->user_lang)
                                            ->label((new Label(__('Language:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Select('user_tz'))
                                            ->items(Date::getZones(true, true))
                                            ->default(App::backend()->user_tz)
                                            ->label((new Label(__('Timezone:'), Label::OL_TF))),
                                    ]),
                                (new Text('h4', __('Edition'))),
                                (new Para())
                                    ->items([
                                        (new Select('user_post_format'))
                                            ->items(App::backend()->formaters_combo)
                                            ->default(App::backend()->user_options['post_format'])
                                            ->label((new Label(__('Preferred format:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Select('user_post_status'))
                                            ->items(App::backend()->status_combo)
                                            ->default(App::backend()->user_post_status)
                                            ->label((new Label(__('Default entry status:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number('user_edit_size', 10, 999, (int) App::backend()->user_options['edit_size']))
                                            ->label((new Label(__('Entry edit field height:'), Label::OL_TF))),
                                    ]),
                                (new Text('h4', __('Miscellaneous'))),
                                (new Capture(
                                    App::behavior()->callBehavior(...),
                                    [
                                        'adminUserForm',
                                        App::backend()->rs ?? null,
                                    ]
                                )),
                            ]),
                    ]),
                (new Para())
                    ->class(['clear', 'vertical-separator'])
                    ->items([
                        (new Password('your_pwd'))
                            ->size(20)
                            ->maxlength(255)
                            ->required(true)
                            ->placeholder(__('Password'))
                            ->autocomplete('current-password')
                            ->label((new Label((new Text('span', '*'))->render() . __('Your password:'), Label::OL_TF))->class('required')),
                    ]),
                (new Para())
                    ->class(['clear', 'form-buttons'])
                    ->items([
                        (new Submit('save', __('Save')))
                            ->accesskey('s'),
                        App::backend()->user_id ?
                            (new None()) :
                            (new Submit('saveplus', __('Save and create another'))),
                        App::backend()->user_id ?
                            (new Hidden('id', App::backend()->user_id)) :
                            (new None()),
                        $super_disabled ?
                            (new Hidden(['user_super'], App::backend()->user_super)) :
                            (new None()),
                        (new Button('go-back', __('Back')))
                            ->class(['go-back', 'reset', 'hidden-if-no-js']),
                        App::nonce()->formNonce(),
                    ]),
            ])
        ->render();

        if (App::backend()->user_id) {
            $permissions_list = (new None());
            if (!App::backend()->user_super) {
                $permissions = App::users()->getUserPermissions(App::backend()->user_id);
                $perm_types  = App::auth()->getPermissionsTypes();
                if ($permissions === []) {
                    $permissions_list = (new Note())
                        ->text(__('No permissions so far.'));
                } else {
                    $permissions_list_items = [];
                    $index                  = 1;
                    foreach ($permissions as $k => $v) {
                        if ((is_countable($v['p']) ? count($v['p']) : 0) > 0) {
                            $permissions_types = function (array $p) use ($perm_types) {
                                foreach (array_keys($p) as $v) {
                                    if (isset($perm_types[$v])) {
                                        yield (new Li())
                                            ->text(__($perm_types[$v]));
                                    }
                                }
                            };
                            $permissions_list_items[] = (new Form('perm-block-' . $index))
                                ->method('post')
                                ->action(App::backend()->url()->get('admin.user.actions'))
                                ->class('perm-block')
                                ->fields([
                                    (new Para())
                                        ->separator(' ')
                                        ->class('blog-perm')
                                        ->items([
                                            (new Text(null, __('Blog:'))),
                                            (new Link())
                                                ->href(App::backend()->url()->get('admin.blog', ['id' => Html::escapeHTML($k)]))
                                                ->text(Html::escapeHTML($v['name'])),
                                            (new Text(null, '(' . Html::escapeHTML($k) . ')')),
                                        ]),
                                    (new Ul())
                                        ->class('ul-perm')
                                        ->items([
                                            ... $permissions_types($v['p']),
                                        ]),
                                    (new Para())
                                        ->class('add-perm')
                                        ->items([
                                            (new Submit('change-perm' . $index, __('Change permissions')))
                                                ->class('reset'),
                                            (new Hidden(['redir'], App::backend()->url()->get('admin.user', ['id' => App::backend()->user_id]))),
                                            (new Hidden(['action'], 'perms')),
                                            (new Hidden(['users[]'], App::backend()->user_id)),
                                            (new Hidden(['blogs[]'], $k)),
                                            App::nonce()->formNonce(),
                                        ]),
                                ]);
                        }
                    }
                    $permissions_list = (new Set())
                        ->items($permissions_list_items);
                }
            }

            echo (new Set())
                ->items([
                    (new Div())
                        ->class(['clear', 'fieldset'])
                        ->items([
                            (new Text('h3', __('Permissions'))),
                            !App::backend()->user_super ?
                                (new Set())
                                    ->items([
                                        (new Form('user_permissions'))
                                            ->method('post')
                                            ->action(App::backend()->url()->get('admin.user.actions'))
                                            ->fields([
                                                (new Submit('add_perm', __('Add new permissions'))),
                                                (new Hidden(['redir'], App::backend()->url()->get('admin.user', ['id' => App::backend()->user_id]))),
                                                (new Hidden(['action'], 'blogs')),
                                                (new Hidden(['users[]'], App::backend()->user_id)),
                                                App::nonce()->formNonce(),
                                            ]),
                                        $permissions_list,
                                    ]) :
                                (new Note())
                                    ->text(sprintf(
                                        __('%s is super admin (all rights on all blogs).'),
                                        (new Text('strong', App::backend()->user_id))->render()
                                    )),
                        ]),
                    (new Div())
                        ->class(['clear', 'fieldset'])
                        ->items([
                            (new Text('h3', __('Direct links'))),
                            (new Para())
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->url()->get('admin.posts', ['user_id' => App::backend()->user_id]))
                                        ->text(__('List of posts')),
                                ]),
                            App::backend()->user_email || App::backend()->user_url ?
                            (new Para())
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->url()->get(
                                            'admin.comments',
                                            [
                                                'email' => App::backend()->user_email,
                                                'site'  => App::backend()->user_url,
                                            ]
                                        ))
                                        ->text(__('List of comments')),
                                ]) :
                            (new None()),
                        ]),
                ])
            ->render();
        }

        Page::helpBlock('core_user');
        Page::close();
    }
}
