<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   The module helper.
 * @ingroup Uninstaller
 */
class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        // check super admin except install that follows MyPlugin check
        return $context === My::INSTALL ? null :
            App::task()->checkContext('BACKEND')
            && App::auth()->isSuperAdmin();
    }
}
