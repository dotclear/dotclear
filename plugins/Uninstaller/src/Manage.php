<?php
/**
 * @brief Uninstaller, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

use dcCore;
use dcModuleDefine;
use dcThemes;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Label,
    Link,
    Para,
    Submit,
    Text
};
use Exception;

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

        // load dcThemes if required
        if (self::getType() == 'theme' && !is_a(dcCore::app()->themes, 'dcThemes')) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules((string) Core::blog()?->themes_path);
        }

        // get selected module
        $define = dcCore::app()->{self::getType() . 's'}->getDefine($_REQUEST['id'], ['state' => dcModuleDefine::STATE_ENABLED]);
        if (!$define->isDefined()) {
            Core::error()->add(__('Unknown module id to uninstall'));
            self::doRedirect();
        }

        // load uninstaller for selected module and check if it has action
        $uninstaller = Uninstaller::instance()->loadModules([$define]);
        $actions     = $uninstaller->getUserActions($define->getId());
        if (!count($actions)) {
            Core::error()->add(__('There are no uninstall actions for this module'));
            self::doRedirect();
        }

        // nothing to do
        if (empty($_POST)) {
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
                            Core::error()->add($action->error);
                        }
                    }
                }
            }
            // list success actions
            if (!empty($done)) {
                array_unshift($done, __('Uninstall action successfuly excecuted'));
                Notices::addSuccessNotice(implode('<br />', $done));
            } else {
                Notices::addWarningNotice(__('No uninstall action done'));
            }
            self::doRedirect();
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // load module uninstaller
        $define      = dcCore::app()->{self::getType() . 's'}->getDefine($_REQUEST['id'], ['state' => dcModuleDefine::STATE_ENABLED]);
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
            ->separator(' ')
            ->items([
                (new Submit(['do']))
                    ->value(__('Perform selected actions'))
                    ->class('delete'),
                (new Link())
                    ->class('button')
                    ->text(__('Cancel'))
                    ->href(self::getRedirect()),
                ... My::hiddenFields([
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
            Core::behavior()->callBehavior('UninstallerHeader')
        );

        echo
        Page::breadcrumb([
            __('System') => '',
            My::name()   => '',
        ]) .
        Notices::getNotices() .

        (new Div())
            ->items([
                (new Text('h3', sprintf((self::getType() == 'theme' ? __('Uninstall theme "%s"') : __('Uninstall plugin "%s"')), __($define->get('name'))))),
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
        return self::getType() == 'theme' ? 'admin.blog.theme' : 'admin.plugins';
    }

    private static function getRedirect(): string
    {
        return (string) Core::backend()->url->get(self::getRedir()) . '#' . self::getType() . 's';
    }

    private static function doRedirect(): void
    {
        Core::backend()->url->redirect(name: self::getRedir(), suffix: '#' . self::getType() . 's');
    }
}
