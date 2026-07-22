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

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since   2.27 Before as admin/users_actions.php
 */
class UsersActions
{
    use TraitProcess;

    /**
     * List of user ID
     *
     * @var string[]
     */
    protected static array $users = [];

    /**
     * List of blog ID
     *
     * @var string[]
     */
    protected static array $blogs = [];

    protected static string $action;

    protected static string $redir;

    public static function init(): bool
    {
        App::backend()->page()->checkSuper();

        $users = [];
        if (!empty($_POST['users']) && is_array($_POST['users'])) {
            foreach ($_POST['users'] as $user_id) {
                if (is_string($user_id) && App::users()->userExists($user_id)) {
                    $users[] = $user_id;
                }
            }
        }

        self::$users = $users;

        $blogs = [];
        if (!empty($_POST['blogs']) && is_array($_POST['blogs'])) {
            foreach ($_POST['blogs'] as $blog_id) {
                if (is_string($blog_id) && App::blogs()->blogExists($blog_id)) {
                    $blogs[] = $blog_id;
                }
            }
        }

        self::$blogs = $blogs;

        return self::status(true);
    }

    public static function process(): bool
    {
        self::$action = '';
        self::$redir  = '';

        if (!empty($_POST['action'])
            && is_string($_POST['action'])
            && !empty($_POST['users'])
        ) {
            self::$action = $_POST['action'];

            if (isset($_POST['redir']) && is_string($_POST['redir']) && !str_contains($_POST['redir'], '://')) {
                self::$redir = $_POST['redir'];
            } else {
                self::$redir = App::backend()->url()->get('admin.users', [
                    'q'      => $_POST['q']      ?? '',
                    'status' => $_POST['status'] ?? '',
                    'sortby' => $_POST['sortby'] ?? '',
                    'order'  => $_POST['order']  ?? '',
                    'page'   => $_POST['page']   ?? '',
                    'nb'     => $_POST['nb']     ?? '',
                ], '&');
            }

            if (self::$users === []) {
                App::error()->add(__('No blog or user given.'));
            }

            # --BEHAVIOR-- adminUsersActions -- array<int,string>, array<int,string>, string, string
            App::behavior()->callBehavior('adminUsersActions', self::$users, self::$blogs, self::$action, self::$redir);

            if (App::status()->user()->has(self::$action) && self::$users !== []) {
                switch (App::status()->user()->level(self::$action)) {
                    // Enable users
                    case App::status()->user()::ENABLED:
                        foreach (self::$users as $user_id) {
                            try {
                                # --BEHAVIOR-- adminBeforeUserEnable -- string
                                App::behavior()->callBehavior('adminBeforeUserEnable', $user_id);

                                $cur              = App::auth()->openUserCursor();
                                $cur->user_status = App::status()->user()::ENABLED;
                                App::users()->updUser($user_id, $cur);
                            } catch (Exception $exception) {
                                App::error()->add($exception->getMessage());
                            }
                        }

                        if (!App::error()->flag()) {
                            App::backend()->notices()->addSuccessNotice(__(
                                'User has been successfully enabled.',
                                'Users has been successfully enabled.',
                                count(self::$users)
                            ));
                            Http::redirect(self::$redir);
                        }

                        break;

                        // Disable users
                    case App::status()->user()::DISABLED:
                        foreach (self::$users as $user_id) {
                            try {
                                if ($user_id === App::auth()->userID()) {
                                    throw new Exception(__('You cannot disable yourself.'));
                                }

                                # --BEHAVIOR-- adminBeforeUserDisable -- string
                                App::behavior()->callBehavior('adminBeforeUserDisable', $user_id);

                                $cur              = App::auth()->openUserCursor();
                                $cur->user_status = App::status()->user()::DISABLED;
                                App::users()->updUser($user_id, $cur);
                            } catch (Exception $exception) {
                                App::error()->add($exception->getMessage());
                            }
                        }

                        if (!App::error()->flag()) {
                            App::backend()->notices()->addSuccessNotice(__(
                                'User has been successfully disabled.',
                                'Users has been successfully disabled.',
                                count(self::$users)
                            ));
                            Http::redirect(self::$redir);
                        }

                        break;
                }
            }

            if (self::$action === 'deleteuser' && self::$users !== []) {
                // Delete users
                foreach (self::$users as $user_id) {
                    try {
                        if ($user_id === App::auth()->userID()) {
                            throw new Exception(__('You cannot delete yourself.'));
                        }

                        # --BEHAVIOR-- adminBeforeUserDelete -- string
                        App::behavior()->callBehavior('adminBeforeUserDelete', $user_id);

                        App::users()->delUser($user_id);
                    } catch (Exception $exception) {
                        App::error()->add($exception->getMessage());
                    }
                }

                if (!App::error()->flag()) {
                    App::backend()->notices()->addSuccessNotice(__(
                        'User has been successfully deleted.',
                        'Users has been successfully deleted.',
                        count(self::$users)
                    ));
                    Http::redirect(self::$redir);
                }
            }

            if (self::$action === 'updateperm' && self::$users !== [] && self::$blogs !== []) {
                // Update users perms
                try {
                    $your_pwd = isset($_POST['your_pwd']) && is_string($your_pwd = $_POST['your_pwd']) ? $your_pwd : '';
                    if ($your_pwd === '' || !App::auth()->checkPassword($your_pwd)) {
                        throw new Exception(__('Password verification failed.'));
                    }

                    foreach (self::$users as $user_id) {
                        foreach (self::$blogs as $blog_id) {
                            $set_perms = [];

                            if (isset($_POST['perm'])
                                && is_array($_POST['perm'])
                                && !empty($_POST['perm'][$blog_id])
                                && is_array($_POST['perm'][$blog_id])
                            ) {
                                foreach ($_POST['perm'][$blog_id] as $perm_id => $value) {
                                    if ($value) {
                                        $set_perms[(string) $perm_id] = true;
                                    }
                                }
                            }

                            App::users()->setUserBlogPermissions($user_id, $blog_id, $set_perms, true);
                        }
                    }
                } catch (Exception $exception) {
                    App::error()->add($exception->getMessage());
                }

                if (!App::error()->flag()) {
                    App::backend()->notices()->addSuccessNotice(__(
                        'User has been successfully updated.',
                        'Users has been successfully updated.',
                        count(self::$users)
                    ));
                    Http::redirect(self::$redir);
                }
            }
        }

        return true;
    }

    public static function render(): void
    {
        // Post data helpers
        $_Int = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        if (self::$users !== [] && self::$blogs === [] && self::$action === 'blogs') {
            $breadcrumb = App::backend()->page()->breadcrumb(
                [
                    __('System')      => '',
                    __('Users')       => App::backend()->url()->get('admin.users'),
                    __('Permissions') => '',
                ]
            );
        } else {
            $breadcrumb = App::backend()->page()->breadcrumb(
                [
                    __('System')  => '',
                    __('Users')   => App::backend()->url()->get('admin.users'),
                    __('Actions') => '',
                ]
            );
        }

        App::backend()->page()->open(
            __('Users'),
            App::backend()->page()->jsLoad('js/_users_actions.js') .
            # --BEHAVIOR-- adminUsersActionsHeaders --
            App::behavior()->callBehavior('adminUsersActionsHeaders'),
            $breadcrumb
        );

        if (self::$action === '') {
            App::backend()->page()->close();
            dotclear_exit();
        }

        $hiddens = [];
        foreach (self::$users as $user_id) {
            $hiddens[] = (new Hidden(['users[]'], $user_id));
        }

        if (isset($_POST['redir']) && is_string($_POST['redir']) && !str_contains($_POST['redir'], '://')) {
            $hiddens[] = (new Hidden(['redir'], Html::escapeURL($_Str('redir'))));
        } else {
            $page = $_Int('page');
            $nb   = $_Int('nb');

            $hiddens[] = (new Hidden(['q'], Html::escapeHTML($_Str('q'))));
            $hiddens[] = (new Hidden(['sortby'], $_Str('sortby')));
            $hiddens[] = (new Hidden(['order'], $_Str('order')));
            $hiddens[] = (new Hidden(['page'], $page === 0 ? '' : (string) $page));
            $hiddens[] = (new Hidden(['nb'], $nb === 0 ? '' : (string) $nb));
        }

        $label = isset($_POST['redir_label']) && is_string($label = $_POST['redir_label']) ? $label : __('Back to user profile');
        $redir = self::$redir;

        echo (new Para())
            ->items([
                (new Link())
                    ->href(Html::escapeURL($redir))
                    ->text($label)
                    ->class('back'),
            ])
        ->render();

        # --BEHAVIOR-- adminUsersActionsContent -- string, string, array<int, Hidden>
        App::behavior()->callBehavior('adminUsersActionsContentV2', self::$action, $hiddens);

        if (self::$users !== [] && self::$blogs === [] && self::$action === 'blogs') {
            // Blog list where to set permissions

            $rs      = null;
            $nb_blog = 0;

            try {
                $rs      = App::blogs()->getBlogs();
                $nb_blog = $rs->count();
            } catch (Exception) {
                // Ignore exceptions
            }

            $users = [];
            foreach (self::$users as $user_id) {
                $users[] = (new Link())
                    ->href(App::backend()->url()->get('admin.user', ['id' => $user_id]))
                    ->text($user_id);
            }

            echo (new Note())
                ->text(sprintf(
                    __('Choose one or more blogs to which you want to give permissions to users %s.'),
                    implode(', ', array_map(fn (Link $user): string => $user->render(), $users))
                ))
            ->render();

            if ($nb_blog === 0 || !$rs instanceof MetaRecord) {
                echo (new Para())
                    ->items([
                        (new Strong(__('No blog'))),
                    ])
                ->render();
            } else {
                $lines = function (MetaRecord $rs) {
                    while ($rs->fetch()) {
                        yield (new Tr())
                            ->class('line')
                            ->cols([
                                (new Td())
                                    ->class('nowrap')
                                    ->items([
                                        (new Checkbox(['blogs[]']))
                                            ->value($rs->strField('blog_id'))
                                            ->title(__('select') . ' ' . $rs->strField('blog_id')),
                                    ]),
                                (new Td())
                                    ->class('nowrap')
                                    ->text($rs->strField('blog_id')),
                                (new Td())
                                    ->class('maximal')
                                    ->text(Html::escapeHTML($rs->strField('blog_name'))),
                                (new Td())
                                    ->class('nowrap')
                                    ->items([
                                        (new Link())
                                            ->href(Html::escapeHTML($rs->strField('blog_url')))
                                            ->class('outgoing')
                                            ->separator(' ')
                                            ->items([
                                                (new Text(null, Html::escapeHTML($rs->strField('blog_url')))),
                                                (new Img('images/outgoing-link.svg'))
                                                    ->alt(''),
                                            ]),
                                    ]),
                                (new Td())
                                    ->class(['nowrap', 'count'])
                                    ->text((string) App::blogs()->countBlogPosts($rs->strField('blog_id'))),
                                (new Td())
                                    ->class('status')
                                    ->items([
                                        App::status()->blog()->image((int) $rs->intField('blog_status')),
                                    ]),
                            ]);
                    }
                };

                echo (new Form('form-blogs'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.user.actions'))
                    ->fields([
                        (new Div())
                            ->class(['table-outer', 'clear'])
                            ->items([
                                (new Table())
                                    ->thead((new Thead())
                                        ->rows([
                                            (new Th())
                                                ->class('nowrap')
                                                ->colspan(2)
                                                ->text(__('Blog ID')),
                                            (new Th())
                                                ->class('nowrap')
                                                ->text(__('Blog name')),
                                            (new Th())
                                                ->class('nowrap')
                                                ->text(__('URL')),
                                            (new Th())
                                                ->class(['nowrap', 'count'])
                                                ->text(__('Entries')),
                                            (new Th())
                                                ->class('nowrap')
                                                ->text(__('Status')),
                                        ]))
                                    ->tbody((new Tbody())
                                        ->rows([
                                            ... $lines($rs),
                                        ])),
                            ]),
                        (new Para())
                            ->class('checkboxes-helpers'),
                        (new Para())
                            ->class(['form-buttons'])
                            ->items([
                                App::nonce()->formNonce(),
                                ...$hiddens,
                                (new Hidden(['action'], 'perms')),
                                (new Submit('do-action', __('Set permissions'))),
                            ]),
                    ])
                ->render();
            }
        } elseif (self::$blogs !== [] && self::$users !== [] && self::$action === 'perms') {
            // Permissions list for each selected blogs

            /*
             * @var array<string, array{name: mixed, url: mixed, p: array<string, bool>}>
             */
            $user_perm = [];
            if (count(self::$users) === 1) {
                // Display actual permissions if there is only one user concerned
                $user_perm = App::users()->getUserPermissions(self::$users[0]);
            }

            $unknown_perms = [];

            $users = [];
            foreach (self::$users as $user_id) {
                $users[] = (new Link())
                    ->href(App::backend()->url()->get('admin.user', ['id' => $user_id]))
                    ->text($user_id);
            }

            echo (new Note())
                ->text(sprintf(
                    __('You are about to change permissions on the following blogs for users %s.'),
                    implode(', ', array_map(fn (Link $user): string => $user->render(), $users))
                ))
            ->render();

            $blogs = [];
            foreach (self::$blogs as $blog_id) {
                $unknown_perms = $user_perm;
                $permissions   = [];
                $unknowns      = [];
                foreach (App::auth()->getPermissionsTypes() as $perm_id => $perm) {
                    $checked = false;
                    if (count(self::$users) === 1) {
                        // Display actual permissions if there is only one user concerned
                        $checked = isset($user_perm[$blog_id]['p'][$perm_id]) && $user_perm[$blog_id]['p'][$perm_id];
                    }

                    if (isset($unknown_perms[$blog_id]['p'][$perm_id])) {
                        unset($unknown_perms[$blog_id]['p'][$perm_id]);
                    }

                    $permissions[] = (new Para())
                        ->items([
                            (new Checkbox(['perm[' . Html::escapeHTML($blog_id) . '][' . Html::escapeHTML($perm_id) . ']', 'perm' . Html::escapeHTML($blog_id) . Html::escapeHTML($perm_id)], $checked))
                                ->value(1)
                                ->label(new Label(__($perm), Label::IL_FT, 'perm' . Html::escapeHTML($blog_id) . Html::escapeHTML($perm_id))),
                        ]);
                }

                if (isset($unknown_perms[$blog_id])) {
                    foreach (array_keys($unknown_perms[$blog_id]['p']) as $perm_id) {
                        $checked = false;
                        if (count(self::$users) === 1) {
                            // Display actual permissions if there is only one user concerned
                            $checked = isset($user_perm[$blog_id]['p'][$perm_id]) && $user_perm[$blog_id]['p'][$perm_id];
                        }

                        $unknowns[] = (new Para())
                            ->items([
                                (new Checkbox(['perm[' . Html::escapeHTML($blog_id) . '][' . Html::escapeHTML($perm_id) . ']', 'perm' . Html::escapeHTML($blog_id) . Html::escapeHTML($perm_id)], $checked))
                                    ->value(1)
                                    ->label(new Label(sprintf(__('[%s] (unreferenced permission)'), $perm_id), Label::IL_FT, 'perm' . Html::escapeHTML($blog_id) . Html::escapeHTML($perm_id))),
                            ]);
                    }
                }

                $blogs[] = (new Set())
                    ->items([
                        (new Text('h3'))
                            ->separator(' ')
                            ->items([
                                (new Text(null, __('Blog:'))),
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.blog', ['id' => Html::escapeHTML($blog_id)]))
                                    ->text(Html::escapeHTML($blog_id)),
                                (new Hidden(['blogs[]'], (string) $blog_id)),
                            ]),
                        ... $permissions,
                        ... $unknowns,
                    ]);
            }

            echo (new Form('permissions-form'))
                ->method('post')
                ->action(App::backend()->url()->get('admin.user.actions'))
                ->fields([
                    ... $blogs,
                    (new Para())
                        ->class('checkboxes-helpers'),
                    (new Fieldset())
                        ->legend(new Legend(__('Validate permissions')))
                        ->fields([
                            (new Note())
                                ->class('form-note')
                                ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                            (new Para())
                                ->items([
                                    (new Password('your_pwd'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->required(true)
                                            ->placeholder(__('Password'))
                                            ->autocomplete('current-password')
                                            ->label((new Label(
                                                (new Span('*'))->render() . __('Your administrator password:'),
                                                Label::OL_TF
                                            ))
                                            ->class('required')),
                                ]),
                            (new Para())
                                ->class(['form-buttons'])
                                ->items([
                                    App::nonce()->formNonce(),
                                    ...$hiddens,
                                    (new Hidden(['action'], 'updateperm')),
                                    (new Submit('do-action', __('Save')))
                                        ->accesskey('s'),
                                ]),
                        ]),
                ])
            ->render();
        }

        App::backend()->page()->helpBlock('core_users');
        App::backend()->page()->close();
    }
}
