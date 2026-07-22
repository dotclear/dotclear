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
use Dotclear\Database\MetaRecord;
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
use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Schema\Status\User as StatusUser;
use Exception;

/**
 * @since 2.27 Before as admin/user.php
 */
class User
{
    use TraitProcess;

    protected static MetaRecord $rs;

    protected static string $user_id;

    protected static bool $user_super;

    protected static int $user_status;

    protected static bool $user_change_pwd;

    protected static string $user_name;

    protected static string $user_firstname;

    protected static string $user_displayname;

    protected static string $user_email;

    protected static string $user_url;

    protected static string $user_lang;

    protected static string $user_tz;

    protected static int $user_post_status;

    protected static string $user_profile_mails;

    protected static string $user_profile_urls;

    /**
     * @var array{
     *      edit_size: int,
     *      post_format: string,
     *      editor: array<string, string>,
     *      enable_wysiwyg: bool,
     *      toolbar_bottom: bool,
     *      ...<string, mixed>
     * } $user_options
     */
    protected static array $user_options;

    protected static string $page_title;

    public static function init(): bool
    {
        App::backend()->page()->checkSuper();

        self::$page_title = __('New user');

        self::$user_id          = '';
        self::$user_super       = false;
        self::$user_status      = 0;
        self::$user_change_pwd  = false;
        self::$user_name        = '';
        self::$user_firstname   = '';
        self::$user_displayname = '';
        self::$user_email       = '';
        self::$user_url         = '';
        self::$user_lang        = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : 'en';
        self::$user_tz          = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';
        self::$user_post_status = App::status()->post()::PENDING;

        self::$user_options = App::users()->userDefaults();

        self::$user_profile_mails = '';
        self::$user_profile_urls  = '';

        # Get user if we have an ID
        if (!empty($_REQUEST['id']) && is_string($_REQUEST['id'])) {
            try {
                // @phpstan-ignore argument.type (false positive, why the previous is_string() is not memorized?)
                self::$rs = App::users()->getUser($_REQUEST['id']);

                self::$user_id          = self::$rs->strField('user_id');
                self::$user_super       = self::$rs->boolField('user_super');
                self::$user_status      = self::$rs->intField('user_status');
                self::$user_change_pwd  = self::$rs->boolField('user_change_pwd');
                self::$user_name        = self::$rs->strField('user_name');
                self::$user_firstname   = self::$rs->strField('user_firstname');
                self::$user_displayname = self::$rs->strField('user_displayname');
                self::$user_email       = self::$rs->strField('user_email');
                self::$user_url         = self::$rs->strField('user_url');
                self::$user_lang        = self::$rs->strField('user_lang');
                self::$user_tz          = self::$rs->strField('user_tz');
                self::$user_post_status = self::$rs->intField('user_post_status');

                /**
                 * @var array{
                 *      edit_size: int,
                 *      post_format: string,
                 *      editor: array<string, string>,
                 *      enable_wysiwyg: bool,
                 *      toolbar_bottom: bool,
                 *      ...<string, mixed>
                 * } $options
                 */
                $options = self::$rs->options();

                self::$user_options = array_merge(self::$user_options, $options);

                $user_prefs = App::userPreferences()->createFromUser(self::$user_id, 'profile');

                self::$user_profile_mails = $user_prefs->get('profile')->getStr('mails', false);
                self::$user_profile_urls  = $user_prefs->get('profile')->getStr('urls', false);

                self::$page_title = self::$user_id;
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (isset($_POST['user_name'])) {
            // Post data helpers
            $_Bool = fn (string $name): bool => !empty($_POST[$name]);
            $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
            $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

            // Add or update user

            try {
                $your_pwd = $_Str('your_pwd');
                if ($your_pwd === '' || !App::auth()->checkPassword($your_pwd)) {
                    throw new Exception(__('Password verification failed.'));
                }

                $cur = App::auth()->openUserCursor();

                self::$user_super       = $_Bool('user_super');
                self::$user_status      = App::status()->user()->level($_Int('user_status'));
                self::$user_name        = Html::escapeHTML($_Str('user_name'));
                self::$user_firstname   = Html::escapeHTML($_Str('user_firstname'));
                self::$user_displayname = Html::escapeHTML($_Str('user_displayname'));
                self::$user_email       = Html::escapeHTML($_Str('user_email'));
                self::$user_url         = Html::escapeHTML($_Str('user_url'));
                self::$user_lang        = Html::escapeHTML($_Str('user_lang'));
                self::$user_tz          = Html::escapeHTML($_Str('user_tz'));
                self::$user_post_status = $_Int('user_post_status');

                $cur->user_id = $_Str('user_id');

                $cur->user_super       = (int) self::$user_super;
                $cur->user_status      = self::$user_status;
                $cur->user_name        = self::$user_name;
                $cur->user_firstname   = self::$user_firstname;
                $cur->user_displayname = self::$user_displayname;
                $cur->user_email       = self::$user_email;
                $cur->user_url         = self::$user_url;
                $cur->user_lang        = self::$user_lang;
                $cur->user_tz          = self::$user_tz;
                $cur->user_post_status = self::$user_post_status;

                if (self::$user_id !== '' && $cur->user_id == App::auth()->userID() && App::auth()->isSuperAdmin()) {
                    // force super_user to true if current user
                    $cur->user_super  = true;
                    self::$user_super = true;
                }

                if (self::$user_id !== '' && $cur->user_id === App::auth()->userID()) {
                    // force user_status to enabled if current user
                    $cur->user_status  = StatusUser::ENABLED;
                    self::$user_status = StatusUser::ENABLED;
                }

                if (App::auth()->allowPassChange()) {
                    $cur->user_change_pwd = $_Bool('user_change_pwd');
                }

                $new_pwd = $_Str('new_pwd');
                if ($new_pwd !== '') {
                    $new_pwd_c = $_Str('new_pwd_c');
                    if ($new_pwd !== $new_pwd_c) {
                        throw new Exception(__("Passwords don't match"));
                    }

                    $cur->user_pwd = $new_pwd;
                }

                $user_options = self::$user_options;

                $user_options['post_format'] = Html::escapeHTML($_Str('user_post_format'));
                $user_options['edit_size']   = $_Int('user_edit_size');

                if ($user_options['edit_size'] < 1) {
                    $user_options['edit_size'] = 10;
                }

                self::$user_options = $user_options;

                $cur->user_options = new ArrayObject(self::$user_options);

                if (self::$user_id !== '') {
                    // Update user

                    # --BEHAVIOR-- adminBeforeUserUpdate -- Cursor, string
                    App::behavior()->callBehavior('adminBeforeUserUpdate', $cur, self::$user_id);

                    $new_id = App::users()->updUser(self::$user_id, $cur);
                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = '';
                    $urls  = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map(trim(...), explode(',', $_Str('user_profile_mails'))), FILTER_VALIDATE_EMAIL)));
                    }

                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map(trim(...), explode(',', $_Str('user_profile_urls'))), FILTER_VALIDATE_URL)));
                    }

                    $user_prefs = App::userPreferences()->createFromUser($new_id, 'profile');
                    $user_prefs->get('profile')->put('mails', $mails, App::userWorkspace()::WS_STRING);
                    $user_prefs->get('profile')->put('urls', $urls, App::userWorkspace()::WS_STRING);

                    # --BEHAVIOR-- adminAfterUserUpdate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterUserUpdate', $cur, $new_id);

                    if (self::$user_id === App::auth()->userID() && self::$user_id !== $new_id) {
                        App::session()->destroy();
                    }

                    App::backend()->notices()->addSuccessNotice(__('User profile has been successfully updated.'));
                    App::backend()->url()->redirect('admin.user', ['id' => $new_id]);
                } else {
                    // Add user

                    if (App::users()->getUsers(['user_id' => $cur->user_id], true)->cardinal() > 0) {
                        throw new Exception(sprintf(__('User "%s" already exists.'), Html::escapeHTML($cur->user_id)));
                    }

                    # --BEHAVIOR-- adminBeforeUserCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforeUserCreate', $cur);

                    $new_id = App::users()->addUser($cur);
                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = '';
                    $urls  = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map(trim(...), explode(',', $_Str('user_profile_mails'))), FILTER_VALIDATE_EMAIL)));
                    }

                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map(trim(...), explode(',', $_Str('user_profile_urls'))), FILTER_VALIDATE_URL)));
                    }

                    $user_prefs = App::userPreferences()->createFromUser($new_id, 'profile');
                    $user_prefs->get('profile')->put('mails', $mails, App::userWorkspace()::WS_STRING);
                    $user_prefs->get('profile')->put('urls', $urls, App::userWorkspace()::WS_STRING);

                    # --BEHAVIOR-- adminAfterUserCreate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterUserCreate', $cur, $new_id);

                    App::backend()->notices()->addSuccessNotice(__('User has been successfully created.'));

                    if (!$cur->user_super) {
                        App::backend()->notices()->addWarningNotice(__('User has no permission, he will not be able to login yet. See below to add some.'));
                    }

                    if (App::status()->user()->isRestricted((int) $cur->user_status)) {
                        App::backend()->notices()->addWarningNotice(__('User is disabled, he will not be able to login yet.'));
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
        App::backend()->page()->open(
            self::$page_title,
            App::backend()->page()->jsConfirmClose('user-form') .
            App::backend()->page()->jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            App::backend()->page()->jsLoad('js/pwstrength.js') .
            App::backend()->page()->jsLoad('js/_user.js') .
            # --BEHAVIOR-- adminUserHeaders --
            App::behavior()->callBehavior('adminUserHeaders'),
            App::backend()->page()->breadcrumb(
                [
                    __('System')      => '',
                    __('Users')       => App::backend()->url()->get('admin.users'),
                    self::$page_title => '',
                ]
            )
        );

        if (!empty($_GET['upd'])) {
            App::backend()->notices()->success(__('User profile has been successfully updated.'));
        }

        if (!empty($_GET['add'])) {
            App::backend()->notices()->success(__('User has been successfully created.'));
        }

        $super_disabled = self::$user_super && self::$user_id === App::auth()->userID();

        $zones = [];
        foreach (Date::getZones(true, true) as $key => $value) {
            $zones[] = (new Optgroup($key))
                ->items(array_map(fn ($key, $val): Option => new Option($key, $val), array_keys($value), array_values($value)));
        }

        $statuses = [];
        foreach (App::status()->user()->combo() as $key => $value) {
            $statuses[] = new Option($key, $value);
        }

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
                                    ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                                (new Para())
                                    ->items([
                                        (new Input('user_id'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(self::$user_id))
                                            ->required(true)
                                            ->placeholder(__('Login'))
                                            ->autocomplete(self::$user_id !== '' ? 'username' : 'off')
                                            ->translate(false)
                                            ->extra('aria-describedby="user_id_help user_id_warning"')
                                            ->label((new Label((new Span('*'))->render() . __('User ID:'), Label::OL_TF))->class('required')),
                                    ]),
                                (new Note('user_id_help'))
                                    ->class(['form-note', 'info'])
                                    ->text(__('At least 2 characters using letters, numbers or symbols.')),
                                self::$user_id === App::auth()->userID() ?
                                    (new Note('user_id_warning'))
                                        ->class('warning')
                                        ->text(__('Warning:') . ' ' . __('If you change your username, you will have to log in again.')) :
                                    (new None()),
                                (new Para())
                                    ->items([
                                        self::$user_id !== '' ?
                                            (new Password('new_pwd'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->autocomplete('new-password')
                                                ->translate(false)
                                                ->label((new Label(__('New password:'), Label::OL_TF))) :
                                            (new Password('new_pwd'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->required(true)
                                                ->placeholder(__('Password'))
                                                ->autocomplete('new-password')
                                                ->translate(false)
                                                ->extra('aria-describedby="new_pwd_help"')
                                                ->label((new Label((new Span('*'))->render() . __('Password:'), Label::OL_TF))->class('required')),
                                    ]),
                                (new Note('new_pwd_help'))
                                    ->class(['form-note', 'info'])
                                    ->text(__('Password must contain at least 6 characters.')),
                                (new Para())
                                    ->items([
                                        self::$user_id !== '' ?
                                            (new Password('new_pwd_c'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->autocomplete('new-password')
                                                ->translate(false)
                                                ->label((new Label(__('Confirm password:'), Label::OL_TF))) :
                                            (new Password('new_pwd_c'))
                                                ->size(20)
                                                ->maxlength(255)
                                                ->class('pw-strength')
                                                ->required(true)
                                                ->placeholder(__('Password'))
                                                ->autocomplete('new-password')
                                                ->translate(false)
                                                ->label((new Label((new Span('*'))->render() . __('Confirm password:'), Label::OL_TF))->class('required')),
                                    ]),
                                App::auth()->allowPassChange() ?
                                    (new Para())
                                        ->items([
                                            (new Checkbox('user_change_pwd', self::$user_change_pwd))
                                                ->value('1')
                                                ->label((new Label(__('Password change required to connect'), Label::IL_FT))),
                                        ]) :
                                    (new None()),
                                (new Para())
                                    ->items([
                                        (new Checkbox($super_disabled ? 'user_super_off' : 'user_super', self::$user_super))
                                            ->value('1')
                                            ->disabled($super_disabled)
                                            ->label((new Label(__('Super administrator'), Label::IL_FT))),
                                    ]),
                                (new Para())
                                    ->items([
                                        self::$user_id !== App::auth()->userID() ?
                                        (new Select('user_status'))
                                            ->items($statuses)
                                            ->default(self::$user_status)
                                            ->label((new Label(__('Status:'), Label::OL_TF))) :
                                        (new Hidden(['user_status', (string) self::$user_status])),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_name'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(self::$user_name))
                                            ->autocomplete('family-name')
                                            ->translate(false)
                                            ->label((new Label(__('Last Name:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_firstname'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(self::$user_firstname))
                                            ->autocomplete('given-name')
                                            ->translate(false)
                                            ->label((new Label(__('First Name:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_displayname'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(self::$user_displayname))
                                            ->autocomplete('nickname')
                                            ->translate(false)
                                            ->label((new Label(__('Display name:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Email('user_email'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(self::$user_email))
                                            ->autocomplete('email')
                                            ->extra('aria-describedby="user_email_help"')
                                            ->translate(false)
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
                                            ->value(Html::escapeHTML(self::$user_profile_mails))
                                            ->translate(false)
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
                                            ->value(Html::escapeHTML(self::$user_url))
                                            ->autocomplete('url')
                                            ->translate(false)
                                            ->label((new Label(__('URL:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Input('user_profile_urls'))
                                            ->size(50)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(self::$user_profile_urls))
                                            ->translate(false)
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
                                            ->items(App::backend()->combos()->getAdminLangsCombo())
                                            ->default(self::$user_lang)
                                            ->translate(false)
                                            ->label((new Label(__('Language:'), Label::OL_TF))),
                                    ]),
                                (new Note())
                                    ->class(['form-note', 'info'])
                                    ->text(__('Languages other than French and English have been automatically translated. If you spot any incorrect translations, please let us know.')),
                                (new Para())
                                    ->items([
                                        (new Select('user_tz'))
                                            ->items($zones)
                                            ->default(self::$user_tz)
                                            ->label((new Label(__('Timezone:'), Label::OL_TF))),
                                    ]),
                                (new Text('h4', __('Edition'))),
                                (new Para())
                                    ->items([
                                        (new Select('user_post_format'))
                                            ->items(App::backend()->combos()->getFormatersCombo())
                                            ->default(self::$user_options['post_format'])
                                            ->label((new Label(__('Preferred format:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Select('user_post_status'))
                                            ->items(App::status()->post()->combo())
                                            ->default(self::$user_post_status)
                                            ->label((new Label(__('Default entry status:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number('user_edit_size', 10, 999, (int) self::$user_options['edit_size']))
                                            ->label((new Label(__('Entry edit field height:'), Label::OL_TF))),
                                    ]),
                                (new Text('h4', __('Miscellaneous'))),
                                (new Capture(
                                    App::behavior()->callBehavior(...),
                                    [
                                        'adminUserForm',
                                        self::$rs ?? null,
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
                            ->label((new Label((new Span('*'))->render() . __('Your administrator password:'), Label::OL_TF))->class('required')),
                    ]),
                (new Para())
                    ->class(['clear', 'form-buttons'])
                    ->items([
                        (new Submit('save', __('Save')))
                            ->accesskey('s'),
                        self::$user_id !== '' ?
                            (new None()) :
                            (new Submit('saveplus', __('Save and create another'))),
                        self::$user_id !== '' ?
                            (new Hidden('id', self::$user_id)) :
                            (new None()),
                        $super_disabled ?
                            (new Hidden(['user_super'], (string) self::$user_super)) :
                            (new None()),
                        (new Button('go-back', __('Back')))
                            ->class(['go-back', 'reset', 'hidden-if-no-js']),
                        App::nonce()->formNonce(),
                    ]),
            ])
        ->render();

        if (self::$user_id !== '') {
            $permissions_list = (new None());
            if (!self::$user_super) {
                $permissions = App::users()->getUserPermissions(self::$user_id);
                $perm_types  = App::auth()->getPermissionsTypes();
                if ($permissions === []) {
                    $permissions_list = (new Note())
                        ->text(__('No permissions so far.'));
                } else {
                    $permissions_list_items = [];
                    $index                  = 1;    // Used for field/form IDs
                    foreach ($permissions as $k => $v) {
                        $name = is_string($name = $v['name']) ? $name : '';

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
                                            ->text(Html::escapeHTML($name)),
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
                                        (new Submit('change-perm-' . $index, __('Change permissions')))
                                            ->class('reset'),
                                        (new Hidden(['redir'], App::backend()->url()->get('admin.user', ['id' => self::$user_id]))),
                                        (new Hidden(['action'], 'perms')),
                                        (new Hidden(['users[]'], self::$user_id)),
                                        (new Hidden(['blogs[]'], (string) $k)),
                                        App::nonce()->formNonce(),
                                    ]),
                            ]);
                        $index++;
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
                            self::$user_super ?
                                (new Note())
                                    ->text(sprintf(
                                        __('%s is super admin (all rights on all blogs).'),
                                        (new Strong(self::$user_id))->render()
                                    )) :
                                (new Set())
                                    ->items([
                                        (new Form('user_permissions'))
                                            ->method('post')
                                            ->action(App::backend()->url()->get('admin.user.actions'))
                                            ->fields([
                                                (new Submit('add_perm', __('Add new permissions'))),
                                                (new Hidden(['redir'], App::backend()->url()->get('admin.user', ['id' => self::$user_id]))),
                                                (new Hidden(['redir_label'], __('Back to user profile'))),
                                                (new Hidden(['action'], 'blogs')),
                                                (new Hidden(['users[]'], self::$user_id)),
                                                App::nonce()->formNonce(),
                                            ]),
                                        $permissions_list,
                                    ]),

                        ]),
                    (new Div())
                        ->class(['clear', 'fieldset'])
                        ->items([
                            (new Text('h3', __('Direct links'))),
                            (new Para())
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->url()->get('admin.posts', ['user_id' => self::$user_id]))
                                        ->text(__('List of posts')),
                                ]),
                            self::$user_email !== '' || self::$user_url !== '' ?
                            (new Para())
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->url()->get(
                                            'admin.comments',
                                            [
                                                'email' => self::$user_email,
                                                'site'  => self::$user_url,
                                            ]
                                        ))
                                        ->text(__('List of comments')),
                                ]) :
                            (new None()),
                        ]),
                ])
            ->render();
        }

        App::backend()->page()->helpBlock('core_user');
        App::backend()->page()->close();
    }
}
