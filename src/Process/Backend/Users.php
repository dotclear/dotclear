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

namespace Dotclear\Process\Backend;

use ArrayObject;
use dcCore;
use Dotclear\Core\Backend\Filter\FilterUsers;
use Dotclear\Core\Backend\Listing\ListingUsers;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Exception;

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
        Core::behavior()->callBehavior('adminUsersActionsCombo', [& $combo_action]);

        Core::backend()->combo_action = $combo_action;

        // Filters
        Core::backend()->user_filter = new FilterUsers();

        // get list params
        $params = Core::backend()->user_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'user_id'          => 'U.user_id',
            'user_name'        => 'user_name',
            'user_firstname'   => 'user_firstname',
            'user_displayname' => 'user_displayname', ];

        # --BEHAVIOR-- adminUsersSortbyLexCombo -- array<int,array<string,string>>
        Core::behavior()->callBehavior('adminUsersSortbyLexCombo', [& $sortby_lex]);

        $params['order'] = (array_key_exists(Core::backend()->user_filter->sortby, $sortby_lex) ?
            Core::con()->lexFields($sortby_lex[Core::backend()->user_filter->sortby]) :
            Core::backend()->user_filter->sortby) . ' ' . Core::backend()->user_filter->order;

        // List
        Core::backend()->user_list = null;

        try {
            # --BEHAVIOR-- adminGetUsers
            $params = new ArrayObject($params);
            # --BEHAVIOR-- adminGetUsers -- ArrayObject
            Core::behavior()->callBehavior('adminGetUsers', $params);

            $rs       = Core::users()->getUsers($params);
            $counter  = Core::users()->getUsers($params, true);
            $rsStatic = $rs->toStatic();
            if (Core::backend()->user_filter->sortby != 'nb_post') {
                // Sort user list using lexical order if necessary
                $rsStatic->extend('rsExtUser');
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort(Core::backend()->user_filter->sortby, Core::backend()->user_filter->order);
            }
            Core::backend()->user_list = new ListingUsers($rsStatic, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        Page::open(
            __('Users'),
            Page::jsLoad('js/_users.js') . Core::backend()->user_filter->js(Core::backend()->url->get('admin.users')),
            Page::breadcrumb(
                [
                    __('System') => '',
                    __('Users')  => '',
                ]
            )
        );

        if (!dcCore::app()->error->flag()) {
            if (!empty($_GET['del'])) {
                Notices::message(__('User has been successfully removed.'));
            }
            if (!empty($_GET['upd'])) {
                Notices::message(__('The permissions have been successfully updated.'));
            }

            echo '<p class="top-add"><a class="button add" href="' . Core::backend()->url->get('admin.user') . '">' . __('New user') . '</a></p>';

            Core::backend()->user_filter->display('admin.users');

            // Show users
            Core::backend()->user_list->display(
                Core::backend()->user_filter->page,
                Core::backend()->user_filter->nb,
                (new Form('form-users'))
                    ->action(Core::backend()->url->get('admin.user.actions'))
                    ->method('post')
                    ->fields([
                        new Text('', '%s'),
                        (new Div())
                            ->class('two-cols')
                             ->items([
                                 (new Para())->class(['col checkboxes-helpers']),
                                 (new Para())->class(['col right'])->items([
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
                                         ->items(Core::backend()->combo_action),
                                     Core::nonce()->formNonce(),
                                     (new Submit('do-action'))
                                         ->value(__('ok')),
                                     ...Core::backend()->url->hiddenFormFields('admin.user.actions', Core::backend()->user_filter->values(true)),
                                 ]),
                             ]),
                    ])
                    ->render(),
                Core::backend()->user_filter->show()
            );
        }
        Page::helpBlock('core_users');
        Page::close();
    }
}
