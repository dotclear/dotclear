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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
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
use Exception;

/**
 * @since   2.27 Before as admin/users_actions.php
 */
class UsersActions extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        $users = [];
        if (!empty($_POST['users']) && is_array($_POST['users'])) {
            foreach ($_POST['users'] as $u) {
                if (App::users()->userExists($u)) {
                    $users[] = $u;
                }
            }
        }
        App::backend()->users = $users;

        $blogs = [];
        if (!empty($_POST['blogs']) && is_array($_POST['blogs'])) {
            foreach ($_POST['blogs'] as $b) {
                if (App::blogs()->blogExists($b)) {
                    $blogs[] = $b;
                }
            }
        }
        App::backend()->blogs = $blogs;

        return self::status(true);
    }

    public static function process(): bool
    {
        App::backend()->action = null;
        App::backend()->redir  = null;

        if (!empty($_POST['action']) && !empty($_POST['users'])) {
            App::backend()->action = $_POST['action'];

            if (isset($_POST['redir']) && !str_contains((string) $_POST['redir'], '://')) {
                App::backend()->redir = $_POST['redir'];
            } else {
                App::backend()->redir = App::backend()->url()->get('admin.users', [
                    'q'      => $_POST['q']      ?? '',
                    'status' => $_POST['status'] ?? '',
                    'sortby' => $_POST['sortby'] ?? '',
                    'order'  => $_POST['order']  ?? '',
                    'page'   => $_POST['page']   ?? '',
                    'nb'     => $_POST['nb']     ?? '',
                ], '&');
            }

            if (empty(App::backend()->users)) {
                App::error()->add(__('No blog or user given.'));
            }

            # --BEHAVIOR-- adminUsersActions -- array<int,string>, array<int,string>, string, string
            App::behavior()->callBehavior('adminUsersActions', App::backend()->users, App::backend()->blogs, App::backend()->action, App::backend()->redir);

            if (App::status()->user()->has(App::backend()->action) && !empty(App::backend()->users)) {
                switch (App::status()->user()->level(App::backend()->action)) {
                    // Enable users
                    case App::status()->user()::ENABLED:
                        foreach (App::backend()->users as $u) {
                            try {
                                # --BEHAVIOR-- adminBeforeUserEnable -- string
                                App::behavior()->callBehavior('adminBeforeUserEnable', $u);

                                $cur              = App::auth()->openUserCursor();
                                $cur->user_status = App::status()->user()::ENABLED;
                                App::users()->updUser($u, $cur);
                            } catch (Exception $e) {
                                App::error()->add($e->getMessage());
                            }
                        }
                        if (!App::error()->flag()) {
                            Notices::addSuccessNotice(__('User has been successfully enabled.'));
                            Http::redirect(App::backend()->redir);
                        }

                        break;

                        // Disable users
                    case App::status()->user()::DISABLED:
                        foreach (App::backend()->users as $u) {
                            try {
                                if ($u == App::auth()->userID()) {
                                    throw new Exception(__('You cannot disable yourself.'));
                                }

                                # --BEHAVIOR-- adminBeforeUserDisable -- string
                                App::behavior()->callBehavior('adminBeforeUserDisable', $u);

                                $cur              = App::auth()->openUserCursor();
                                $cur->user_status = App::status()->user()::DISABLED;
                                App::users()->updUser($u, $cur);
                            } catch (Exception $e) {
                                App::error()->add($e->getMessage());
                            }
                        }
                        if (!App::error()->flag()) {
                            Notices::addSuccessNotice(__('User has been successfully deleted.'));
                            Http::redirect(App::backend()->redir);
                        }

                        break;
                }
            }

            if (App::backend()->action == 'deleteuser' && !empty(App::backend()->users)) {
                // Delete users
                foreach (App::backend()->users as $u) {
                    try {
                        if ($u == App::auth()->userID()) {
                            throw new Exception(__('You cannot delete yourself.'));
                        }

                        # --BEHAVIOR-- adminBeforeUserDelete -- string
                        App::behavior()->callBehavior('adminBeforeUserDelete', $u);

                        App::users()->delUser($u);
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }
                }
                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('User has been successfully deleted.'));
                    Http::redirect(App::backend()->redir);
                }
            }

            if (App::backend()->action == 'updateperm' && !empty(App::backend()->users) && !empty(App::backend()->blogs)) {
                // Update users perms
                try {
                    if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                        throw new Exception(__('Password verification failed'));
                    }

                    foreach (App::backend()->users as $u) {
                        foreach (App::backend()->blogs as $b) {
                            $set_perms = [];

                            if (!empty($_POST['perm'][$b])) {
                                foreach ($_POST['perm'][$b] as $perm_id => $v) {
                                    if ($v) {
                                        $set_perms[(string) $perm_id] = true;
                                    }
                                }
                            }

                            App::users()->setUserBlogPermissions($u, $b, $set_perms, true);
                        }
                    }
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('User has been successfully updated.'));
                    Http::redirect(App::backend()->redir);
                }
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!empty(App::backend()->users) && empty(App::backend()->blogs) && App::backend()->action == 'blogs') {
            $breadcrumb = Page::breadcrumb(
                [
                    __('System')      => '',
                    __('Users')       => App::backend()->url()->get('admin.users'),
                    __('Permissions') => '',
                ]
            );
        } else {
            $breadcrumb = Page::breadcrumb(
                [
                    __('System')  => '',
                    __('Users')   => App::backend()->url()->get('admin.users'),
                    __('Actions') => '',
                ]
            );
        }

        Page::open(
            __('Users'),
            Page::jsLoad('js/_users_actions.js') .
            # --BEHAVIOR-- adminUsersActionsHeaders --
            App::behavior()->callBehavior('adminUsersActionsHeaders'),
            $breadcrumb
        );

        if (App::backend()->action === null) {
            Page::close();
            exit;
        }

        $hiddens = [];
        foreach (App::backend()->users as $u) {
            $hiddens[] = (new Hidden(['users[]'], $u));
        }

        if (isset($_POST['redir']) && !str_contains((string) $_POST['redir'], '://')) {
            $hiddens[] = (new Hidden(['redir'], Html::escapeURL($_POST['redir'])));
        } else {
            $hiddens[] = (new Hidden(['q'], Html::escapeHTML($_POST['q'] ?? '')));
            $hiddens[] = (new Hidden(['sortby'], $_POST['sortby'] ?? ''));
            $hiddens[] = (new Hidden(['order'], $_POST['order'] ?? ''));
            $hiddens[] = (new Hidden(['page'], $_POST['page'] ?? ''));
            $hiddens[] = (new Hidden(['nb'], $_POST['nb'] ?? ''));
        }

        $label = $_POST['redir_label'] ?? __('Back to user profile');

        echo (new Para())
            ->items([
                (new Link())
                    ->href(Html::escapeURL(App::backend()->redir))
                    ->text($label)
                    ->class('back'),
            ])
        ->render();

        # --BEHAVIOR-- adminUsersActionsContent -- string, string, array<int, Hidden>
        App::behavior()->callBehavior('adminUsersActionsContentV2', App::backend()->action, $hiddens);

        if (!empty(App::backend()->users) && empty(App::backend()->blogs) && App::backend()->action === 'blogs') {
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
            foreach (App::backend()->users as $u) {
                $users[] = (new Link())
                    ->href(App::backend()->url()->get('admin.user', ['id' => $u]))
                    ->text($u);
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
                                            ->value($rs->blog_id)
                                            ->title(__('select') . ' ' . $rs->blog_id),
                                    ]),
                                (new Td())
                                    ->class('nowrap')
                                    ->text($rs->blog_id),
                                (new Td())
                                    ->class('maximal')
                                    ->text(Html::escapeHTML($rs->blog_name)),
                                (new Td())
                                    ->class('nowrap')
                                    ->items([
                                        (new Link())
                                            ->href(Html::escapeHTML($rs->blog_url))
                                            ->class('outgoing')
                                            ->separator(' ')
                                            ->items([
                                                (new Text(null, Html::escapeHTML($rs->blog_url))),
                                                (new Img('images/outgoing-link.svg'))
                                                    ->alt(''),
                                            ]),
                                    ]),
                                (new Td())
                                    ->class(['nowrap', 'count'])
                                    ->text((string) App::blogs()->countBlogPosts($rs->blog_id)),
                                (new Td())
                                    ->class('status')
                                    ->items([
                                        App::status()->blog()->image((int) $rs->blog_status),
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
        } elseif (!empty(App::backend()->blogs) && !empty(App::backend()->users) && App::backend()->action === 'perms') {
            // Permissions list for each selected blogs

            /*
             * @var        array<string, array{name: mixed, url: mixed, p: array<string, bool>}>
             */
            $user_perm = [];
            if (count(App::backend()->users) === 1) {
                // Display actual permissions if there is only one user concerned
                $user_perm = App::users()->getUserPermissions(App::backend()->users[0]);
            }

            $unknown_perms = [];

            $users = [];
            foreach (App::backend()->users as $u) {
                $users[] = (new Link())
                    ->href(App::backend()->url()->get('admin.user', ['id' => $u]))
                    ->text($u);
            }

            echo (new Note())
                ->text(sprintf(
                    __('You are about to change permissions on the following blogs for users %s.'),
                    implode(', ', array_map(fn (Link $user): string => $user->render(), $users))
                ))
            ->render();

            $blogs = [];
            foreach (App::backend()->blogs as $b) {
                $unknown_perms = $user_perm;
                $permissions   = [];
                $unknowns      = [];
                foreach (App::auth()->getPermissionsTypes() as $perm_id => $perm) {
                    $checked = false;
                    if (count(App::backend()->users) === 1) {
                        // Display actual permissions if there is only one user concerned
                        $checked = isset($user_perm[$b]['p'][$perm_id]) && $user_perm[$b]['p'][$perm_id];
                    }
                    if (isset($unknown_perms[$b]['p'][$perm_id])) {
                        unset($unknown_perms[$b]['p'][$perm_id]);
                    }

                    $permissions[] = (new Para())
                        ->items([
                            (new Checkbox(['perm[' . Html::escapeHTML($b) . '][' . Html::escapeHTML($perm_id) . ']', 'perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id)], $checked))
                                ->value(1)
                                ->label(new Label(__($perm), Label::IL_FT, 'perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id))),
                        ]);
                }

                if (isset($unknown_perms[$b])) {
                    foreach (array_keys($unknown_perms[$b]['p']) as $perm_id) {
                        $checked = false;
                        if (count(App::backend()->users) === 1) {
                            // Display actual permissions if there is only one user concerned
                            $checked = isset($user_perm[$b]['p'][$perm_id]) && $user_perm[$b]['p'][$perm_id];
                        }
                        $unknowns[] = (new Para())
                            ->items([
                                (new Checkbox(['perm[' . Html::escapeHTML($b) . '][' . Html::escapeHTML($perm_id) . ']', 'perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id)], $checked))
                                    ->value(1)
                                    ->label(new Label(sprintf(__('[%s] (unreferenced permission)'), $perm_id), Label::IL_FT, 'perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id))),
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
                                    ->href(App::backend()->url()->get('admin.blog', ['id' => Html::escapeHTML($b)]))
                                    ->text(Html::escapeHTML($b)),
                                (new Hidden(['blogs[]'], $b)),
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

        Page::helpBlock('core_users');
        Page::close();
    }
}
