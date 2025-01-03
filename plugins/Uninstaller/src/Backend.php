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
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Notices;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief   The module backend process.
 * @ingroup Uninstaller
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            // add "unsinstall" button to modules list
            'adminModulesListGetActionsV2' => function (ModulesList $list, ModuleDefine $define): string {
                // do not unsintall current theme
                if ($define->get('type') == 'theme' && $define->getId() == App::blog()->settings()->get('system')->get('theme')) {
                    return '';
                }

                return count(Uninstaller::instance()->loadModules([$define])->getUserActions($define->getId())) === 0 ? '' :
                    sprintf(
                        ' <a href="%s" class="button delete uninstall_module_button">' . __('Uninstall') . '</a>',
                        My::manageUrl(['type' => $define->get('type'), 'id' => $define->getId()])
                    );
            },
            // perform direct action on theme deletion
            'themeBeforeDeleteV2' => function (ModuleDefine $define): void {
                self::moduleBeforeDelete($define);
            },
            // perform direct action on plugin deletion
            'pluginBeforeDeleteV2' => function (ModuleDefine $define): void {
                self::moduleBeforeDelete($define);
            },
            // add js to hide delete button when uninstaller exists
            'pluginsToolsHeadersV2' => fn (): string => self::modulesToolsHeader(),
            // add js to hide delete button when uninstaller exists
            'themesToolsHeadersV2' => fn (): string => self::modulesToolsHeader(),
        ]);

        return true;
    }

    /**
     * Perfom direct action on module deletion.
     *
     * This does not perform action on disabled module.
     *
     * @param   ModuleDefine    $define     The module
     */
    protected static function moduleBeforeDelete(ModuleDefine $define): void
    {
        if (My::settings()->get('no_direct_uninstall')) {
            return;
        }

        try {
            $uninstaller = Uninstaller::instance()->loadModules([$define]);

            // Do not perform direct actions on module if a duplicate exists.
            if (!in_array($define->get('type'), ['plugin', 'theme'])
                || $define->get('type') == 'plugin' && 1 < count(App::plugins()->getDefines(['id' => $define->getId()]))
                || $define->get('type') == 'theme'  && 1 < count(App::themes()->getDefines(['id' => $define->getId()]))
            ) {
                return;
            }

            $done = [];
            foreach ($uninstaller->getDirectActions($define->getId()) as $cleaner => $stack) {
                foreach ($stack as $action) {
                    if ($uninstaller->execute($cleaner, $action->id, $action->ns)) {
                        $done[] = $action->success;
                    } else {
                        App::error()->add($action->error);
                    }
                }
            }

            // If direct actions are made, do not execute dotclear delete action.
            if ($done !== []) {
                array_unshift($done, __('Plugin has been successfully uninstalled.'));
                Notices::addSuccessNotice(implode('<br>', $done));
                if ($define->get('type') == 'theme') {
                    App::backend()->url()->redirect(name: 'admin.blog.theme', suffix: '#themes');
                } else {
                    App::backend()->url()->redirect(name: 'admin.plugins', suffix: '#plugins');
                }
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }
    }

    /**
     * Get backend URL of uninstaller js.
     *
     * @return  string  The URL
     */
    protected static function modulesToolsHeader(): string
    {
        return My::jsLoad('backend');
    }
}
