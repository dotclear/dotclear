<?php
/**
 * @brief Plugin aboutConfig My module class.
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.27
 */
declare(strict_types=1);

namespace Dotclear\Plugin\aboutConfig;

use Dotclear\Core\Core;
use Dotclear\Module\MyPlugin;

class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        return defined('DC_CONTEXT_ADMIN')
            && Core::auth()->isSuperAdmin();
    }
}
