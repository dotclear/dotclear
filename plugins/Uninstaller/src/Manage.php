<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Label,
    Link,
    Para,
    Submit,
    Text
};
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief   The module backend manage process.
 * @ingroup Uninstaller
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // no module selected
        if (empty($_REQUEST['id'])) {
            self::doRedirect();
        }

        // load Themes if required
        if (self::getType() === 'theme' && App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath());
        }

        // get selected module
        $define = App::{self::getType() . 's'}()->getDefine($_REQUEST['id'], ['state' => ModuleDefine::STATE_ENABLED]);
        if (!$define->isDefined()) {
            App::error()->add(__('Unknown module id to uninstall'));
            self::doRedirect();
        }

        // load uninstaller for selected module and check if it has action
        $uninstaller = Uninstaller::instance()->loadModules([$define]);
        $actions     = $uninstaller->getUserActions($define->getId());
        if (count($actions) === 0) {
            App::error()->add(__('There are no uninstall actions for this module'));
            self::doRedirect();
        }

        // nothing to do
        if ($_POST === []) {
            return true;
        }

        try {
            $done = [];
            // loop through module uninstall actions and execute them
            foreach ($actions as $cleaner => $stack) {
                foreach ($stack as $action) {
                    if (isset($_POST['action'][$cleaner]) && isset($_POST['action'][$cleaner][$action->id])) {
                        if ($uninstaller->execute($cleaner, $action->id, $_POST['action'][$cleaner][$action->id])) {
                            $done[] = $action->success;
                        } else {
                            App::error()->add($action->error);
                        }
                    }
                }
            }
            // list success actions
            if ($done !== []) {
                array_unshift($done, __('Uninstall action successfuly excecuted'));
                Notices::addSuccessNotice(implode('<br>', $done));
            } else {
                Notices::addWarningNotice(__('No uninstall action done'));
            }
            self::doRedirect();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // load module uninstaller
        $define      = App::{self::getType() . 's'}()->getDefine($_REQUEST['id'], ['state' => ModuleDefine::STATE_ENABLED]);
        $uninstaller = Uninstaller::instance()->loadModules([$define]);
        $fields      = [];

        // custom actions form fields
        if ($uninstaller->hasRender($define->getId())) {
            $fields[] = (new Text('', $uninstaller->render($define->getId())));
        }

        // user actions form fields
        foreach ($uninstaller->getUserActions($define->getId()) as $cleaner => $stack) {
            foreach ($stack as $action) {
                $fields[] = (new Para())
                    ->items([
                        (new Checkbox(['action[' . $cleaner . '][' . $action->id . ']', 'action_' . $cleaner . '_' . $action->id], $action->default))
                            ->value($action->ns),
                        (new Label($action->query, Label::OUTSIDE_LABEL_AFTER))
                            ->for('action_' . $cleaner . '_' . $action->id)
                            ->class('classic'),
                    ]);
            }
        }

        // submit
        $fields[] = (new Para())
            ->class('form-buttons')
            ->separator(' ')
            ->items([
                (new Submit(['do']))
                    ->value(__('Perform selected actions'))
                    ->class('delete'),
                (new Link())
                    ->class('button')
                    ->text(__('Cancel'))
                    ->href(self::getRedirect()),
                ...My::hiddenFields([
                    'type' => self::getType(),
                    'id'   => $define->getId(),
                ]),
            ]);

        // display form
        Page::openModule(
            My::name(),
            Page::jsJson('uninstaller', ['confirm_uninstall' => __('Are you sure you perform these ations?')]) .
            My::jsLoad('manage') .

            # --BEHAVIOR-- UninstallerHeader
            App::behavior()->callBehavior('UninstallerHeader')
        );

        echo
        Page::breadcrumb([
            __('System') => '',
            My::name()   => '',
        ]) .
        Notices::getNotices() .

        (new Div())
            ->items([
                (new Text('h3', sprintf((self::getType() === 'theme' ? __('Uninstall theme "%s"') : __('Uninstall plugin "%s"')), __($define->get('name'))))),
                (new Text('p', sprintf(__('The module "%s %s" offers advanced unsintall process:'), $define->getId(), $define->get('version')))),
                (new Form('uninstall-form'))
                    ->method('post')
                    ->action(My::manageUrl())
                    ->fields($fields),
            ])
            ->render();

        Page::closeModule();
    }

    private static function getType(): string
    {
        return ($_REQUEST['type'] ?? 'theme') == 'theme' ? 'theme' : 'plugin';
    }

    private static function getRedir(): string
    {
        return self::getType() === 'theme' ? 'admin.blog.theme' : 'admin.plugins';
    }

    private static function getRedirect(): string
    {
        return App::backend()->url()->get(self::getRedir()) . '#' . self::getType() . 's';
    }

    private static function doRedirect(): void
    {
        App::backend()->url()->redirect(name: self::getRedir(), suffix: '#' . self::getType() . 's');
    }
}
