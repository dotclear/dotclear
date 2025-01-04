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
use Dotclear\Core\Backend\Filter\FilterUsers;
use Dotclear\Core\Backend\Listing\ListingUsers;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Schema\Extension\User;
use Exception;

/**
 * @since 2.27 Before as admin/users.php
 */
class Users extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        // Actions
        $combo_action = [
            __('Set permissions') => 'blogs',
            __('Delete')          => 'deleteuser',
        ];

        # --BEHAVIOR-- adminUsersActionsCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminUsersActionsCombo', [& $combo_action]);

        App::backend()->combo_action = $combo_action;

        // Filters
        App::backend()->user_filter = new FilterUsers();

        // get list params
        $params = App::backend()->user_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'user_id'          => 'U.user_id',
            'user_name'        => 'user_name',
            'user_firstname'   => 'user_firstname',
            'user_displayname' => 'user_displayname', ];

        # --BEHAVIOR-- adminUsersSortbyLexCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminUsersSortbyLexCombo', [& $sortby_lex]);

        $params['order'] = (array_key_exists(App::backend()->user_filter->sortby, $sortby_lex) ?
            App::con()->lexFields($sortby_lex[App::backend()->user_filter->sortby]) :
            App::backend()->user_filter->sortby) . ' ' . App::backend()->user_filter->order;

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
            if (App::backend()->user_filter->sortby != 'nb_post') {
                // Sort user list using lexical order if necessary
                $rsStatic->extend(User::class);
                $rsStatic = $rsStatic->toStatic();
                $rsStatic->lexicalSort(App::backend()->user_filter->sortby, App::backend()->user_filter->order);
            }
            App::backend()->user_list = new ListingUsers($rsStatic, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        Page::open(
            __('Users'),
            Page::jsLoad('js/_users.js') . App::backend()->user_filter->js(App::backend()->url()->get('admin.users')),
            Page::breadcrumb(
                [
                    __('System') => '',
                    __('Users')  => '',
                ]
            )
        );

        if (!App::error()->flag()) {
            if (!empty($_GET['del'])) {
                Notices::message(__('User has been successfully removed.'));
            }
            if (!empty($_GET['upd'])) {
                Notices::message(__('The permissions have been successfully updated.'));
            }

            echo (new Para())
                ->class('top-add')
                ->items([
                    (new Link())
                        ->class(['button', 'add'])
                        ->href(App::backend()->url()->get('admin.user'))
                        ->text(__('New user')),
                ])
            ->render();

            App::backend()->user_filter->display('admin.users');

            // Show users
            App::backend()->user_list->display(
                App::backend()->user_filter->page,
                App::backend()->user_filter->nb,
                (new Form('form-users'))
                    ->action(App::backend()->url()->get('admin.user.actions'))
                    ->method('post')
                    ->fields([
                        new Text('', '%s'),
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
                                     ...App::backend()->url()->hiddenFormFields('admin.user.actions', App::backend()->user_filter->values(true)),
                                 ]),
                             ]),
                    ])
                    ->render(),
                App::backend()->user_filter->show()
            );
        }
        Page::helpBlock('core_users');
        Page::close();
    }
}
