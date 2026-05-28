<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use Dotclear\Module\MyPlugin;

/**
 * @brief   The module helper.
 * @ingroup simpleMenu
 *
 * @since   2.27
 */
class My extends MyPlugin
{
    /**
     * Name of blog settings workspace to store configuration
     */
    public const WORKSPACE = 'system';

    /**
     * Name of blog settings setting to store menu definition configuration
     */
    public const SETTING_MENU = 'simpleMenu';

    /**
     * Name of blog settings setting to store menu activation
     */
    public const SETTING_ACTIVE = 'simpleMenu_active';

    // Use default permissions
}
