<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup dcLegacyEditor
 */
class Manage extends Process
{
    public static function init(): bool
    {
        App::backend()->editor_is_admin    = self::status(My::checkContext(My::MANAGE));
        App::backend()->editor_std_active  = self::status(My::checkContext(My::MANAGE)) && My::settings()->active;
        App::backend()->editor_std_dynamic = self::status(My::checkContext(My::MANAGE)) && My::settings()->dynamic;

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                App::backend()->editor_std_active = (empty($_POST['dclegacyeditor_active'])) ? false : true;
                My::settings()->put('active', App::backend()->editor_std_active, 'boolean');

                App::backend()->editor_std_dynamic = (empty($_POST['dclegacyeditor_dynamic'])) ? false : true;
                My::settings()->put('dynamic', App::backend()->editor_std_dynamic, 'boolean');

                Notices::addSuccessNotice(__('The configuration has been updated.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::openModule(My::name());

        require My::path() . '/tpl/index.php';

        Page::closeModule();
    }
}
