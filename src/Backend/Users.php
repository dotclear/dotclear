<?php
/**
 * @since 2.27 Before as admin/users.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Backend;

use ArrayObject;
use adminUserFilter;
use adminUserList;
use dcCore;
use dcNsProcess;
use dcPage;
use Exception;
use form;

class Users extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        dcPage::checkSuper();

        // Actions
        $combo_action = [
            __('Set permissions') => 'blogs',
            __('Delete')          => 'deleteuser',
        ];

        # --BEHAVIOR-- adminUsersActionsCombo -- array<int,array<string,string>>
        dcCore::app()->callBehavior('adminUsersActionsCombo', [& $combo_action]);

        dcCore::app()->admin->combo_action = $combo_action;

        // Filters
        dcCore::app()->admin->user_filter = new adminUserFilter();
        dcCore::app()->admin->user_filter->add('process', 'UsersActions');

        // get list params
        $params = dcCore::app()->admin->user_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'user_id'          => 'U.user_id',
            'user_name'        => 'user_name',
            'user_firstname'   => 'user_firstname',
            'user_displayname' => 'user_displayname', ];

        # --BEHAVIOR-- adminUsersSortbyLexCombo -- array<int,array<string,string>>
        dcCore::app()->callBehavior('adminUsersSortbyLexCombo', [& $sortby_lex]);

        $params['order'] = (array_key_exists(dcCore::app()->admin->user_filter->sortby, $sortby_lex) ?
            dcCore::app()->con->lexFields($sortby_lex[dcCore::app()->admin->user_filter->sortby]) :
            dcCore::app()->admin->user_filter->sortby) . ' ' . dcCore::app()->admin->user_filter->order;

        // List
        dcCore::app()->admin->user_list = null;

        try {
            # --BEHAVIOR-- adminGetUsers
            $params = new ArrayObject($params);
            # --BEHAVIOR-- adminGetUsers -- ArrayObject
            dcCore::app()->callBehavior('adminGetUsers', $params);

            $rs       = dcCore::app()->getUsers($params);
            $counter  = dcCore::app()->getUsers($params, true);
            $rsStatic = $rs->toStatic();
            if (dcCore::app()->admin->user_filter->sortby != 'nb_post') {
                // Sort user list using lexical order if necessary
                $rsStatic->extend('rsExtUser');
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort(dcCore::app()->admin->user_filter->sortby, dcCore::app()->admin->user_filter->order);
            }
            dcCore::app()->admin->user_list = new adminUserList($rsStatic, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        dcPage::open(
            __('Users'),
            dcPage::jsLoad('js/_users.js') . dcCore::app()->admin->user_filter->js(),
            dcPage::breadcrumb(
                [
                    __('System') => '',
                    __('Users')  => '',
                ]
            )
        );

        if (!dcCore::app()->error->flag()) {
            if (!empty($_GET['del'])) {
                dcPage::message(__('User has been successfully removed.'));
            }
            if (!empty($_GET['upd'])) {
                dcPage::message(__('The permissions have been successfully updated.'));
            }

            echo '<p class="top-add"><strong><a class="button add" href="' . dcCore::app()->adminurl->get('admin.user') . '">' . __('New user') . '</a></strong></p>';

            dcCore::app()->admin->user_filter->display('admin.user.actions');

            // Show users
            dcCore::app()->admin->user_list->display(
                dcCore::app()->admin->user_filter->page,
                dcCore::app()->admin->user_filter->nb,
                '<form action="' . dcCore::app()->adminurl->get('admin.user.actions') . '" method="post" id="form-users">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right"><label for="action" class="classic">' .
                __('Selected users action:') . ' ' .
                form::combo('action', dcCore::app()->admin->combo_action) .
                '</label> ' .
                '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
                dcCore::app()->adminurl->getHiddenFormFields('admin.user.actions', dcCore::app()->admin->user_filter->values(true)) .
                dcCore::app()->formNonce() .
                '</p>' .
                '</div>' .
                '</form>',
                dcCore::app()->admin->user_filter->show()
            );
        }
        dcPage::helpBlock('core_users');
        dcPage::close();
    }
}
