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
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Schema\Extension\User;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/users.php
 */
class Users
{
    use TraitProcess;

    public static function init(): bool
    {
        App::backend()->page()->checkSuper();

        // Actions
        $combo_action = [
            __('Permissions') => [__('Set permissions') => 'blogs'],
            __('Status')      => App::status()->user()->action(),
            __('Delete')      => [__('Delete') => 'deleteuser'],
        ];

        # --BEHAVIOR-- adminUsersActionsCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminUsersActionsCombo', [& $combo_action]);

        App::backend()->combo_action = $combo_action;

        // Filters
        App::backend()->user_filter = App::backend()->filter()->users(); // Backward compatibility

        // get list params
        $params = App::backend()->filter()->users()->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'user_id'          => 'U.user_id',
            'user_name'        => 'user_name',
            'user_firstname'   => 'user_firstname',
            'user_displayname' => 'user_displayname',
        ];

        # --BEHAVIOR-- adminUsersSortbyLexCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminUsersSortbyLexCombo', [& $sortby_lex]);

        $params['order'] = (array_key_exists(App::backend()->filter()->users()->sortby, $sortby_lex) ?
            App::db()->con()->lexFields($sortby_lex[App::backend()->filter()->users()->sortby]) :
            App::backend()->filter()->users()->sortby) . ' ' . App::backend()->filter()->users()->order;

        // List
        App::backend()->user_list = null;

        try {
            # --BEHAVIOR-- adminGetUsers
            $params = new ArrayObject($params);
            # --BEHAVIOR-- adminGetUsers -- ArrayObject
            App::behavior()->callBehavior('adminGetUsers', $params);

            $rs       = App::users()->getUsers($params);
            $counter  = App::users()->getUsers($params, true);
            $rsStatic = $rs->toStatic();
            if (App::backend()->filter()->users()->sortby != 'nb_post') {
                // Sort user list using lexical order if necessary
                $rsStatic->extend(User::class);
                $rsStatic = $rsStatic->toStatic();
                $rsStatic->lexicalSort(App::backend()->filter()->users()->sortby, App::backend()->filter()->users()->order);
            }
            App::backend()->user_list = App::backend()->listing()->users($rsStatic, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        App::backend()->page()->open(
            __('Users'),
            App::backend()->page()->jsLoad('js/_users.js') . App::backend()->filter()->users()->js(App::backend()->url()->get('admin.users')),
            App::backend()->page()->breadcrumb(
                [
                    __('System') => '',
                    __('Users')  => '',
                ]
            )
        );

        if (!App::error()->flag()) {
            if (!empty($_GET['del'])) {
                App::backend()->notices()->message(__('User has been successfully removed.'));
            }
            if (!empty($_GET['upd'])) {
                App::backend()->notices()->message(__('The permissions have been successfully updated.'));
            }

            echo (new Para())
                ->class('new-stuff')
                ->items([
                    (new Link())
                        ->class(['button', 'add'])
                        ->href(App::backend()->url()->get('admin.user'))
                        ->text(__('New user')),
                ])
            ->render();

            App::backend()->filter()->users()->display('admin.users');

            // Show users
            App::backend()->user_list->display(
                App::backend()->filter()->users()->page,
                App::backend()->filter()->users()->nb,
                (new Form('form-users'))
                    ->action(App::backend()->url()->get('admin.user.actions'))
                    ->method('post')
                    ->fields([
                        (new Text(null, '%s')),
                        (new Div())
                            ->class('two-cols')
                             ->items([
                                 (new Para())->class(['col', 'checkboxes-helpers']),
                                 (new Para())->class(['col', 'right', 'form-buttons'])->items([
                                     (new Select('action'))
                                         ->class('online')
                                         ->title(__('Actions'))
                                         ->label(
                                             (new Label(
                                                 __('Selected users action:'),
                                                 Label::OUTSIDE_LABEL_BEFORE
                                             ))
                                             ->class('classic')
                                         )
                                         ->items(App::backend()->combo_action),
                                     App::nonce()->formNonce(),
                                     (new Submit('do-action'))
                                         ->value(__('ok')),
                                     ...App::backend()->url()->hiddenFormFields('admin.user.actions', App::backend()->filter()->users()->values(true)),
                                     (new Hidden(['redir_label'], __('Back to users list'))),

                                 ]),
                             ]),
                    ])
                    ->render(),
                App::backend()->filter()->users()->show()
            );
        }
        App::backend()->page()->helpBlock('core_users');
        App::backend()->page()->close();
    }
}
