<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\aboutConfig;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   The module helper.
 * @ingroup aboutConfig
 *
 * @since   2.27
 */
class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        // Limit to backend and super admin
        return  $context === self::INSTALL ? null :
            App::task()->checkContext('BACKEND')
            && App::auth()->isSuperAdmin();
    }
}
