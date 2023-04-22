<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Network\Http;
use Exception;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcCore::app()->admin->editor_is_admin = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_ADMIN,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id) || dcCore::app()->auth->isSuperAdmin();

            dcCore::app()->admin->editor_std_active = dcCore::app()->blog->settings->dclegacyeditor->active;

            static::$init = true;
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                dcCore::app()->admin->editor_std_active = (empty($_POST['dclegacyeditor_active'])) ? false : true;
                dcCore::app()->blog->settings->dclegacyeditor->put('active', dcCore::app()->admin->editor_std_active, 'boolean');

                dcPage::addSuccessNotice(__('The configuration has been updated.'));
                Http::redirect(dcCore::app()->admin->getPageURL());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        dcPage::openModule(__('dcLegacyEditor'));

        require __DIR__ . '/../tpl/index.php';

        dcPage::closeModule();
    }
}
