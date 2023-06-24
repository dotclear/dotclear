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
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Exception;

class Manage extends Process
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE);

        dcCore::app()->admin->editor_std_active = static::$init && dcCore::app()->blog->settings->dclegacyeditor->active;

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

                Page::addSuccessNotice(__('The configuration has been updated.'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
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
        Page::openModule(My::name());

        require My::path() . '/tpl/index.php';

        Page::closeModule();
    }
}
