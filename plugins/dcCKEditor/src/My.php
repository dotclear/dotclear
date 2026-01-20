<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcCKEditor;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief   The module backend process.
 * @ingroup dcCKEditor
 *
 * @since   2.27
 */
class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        // Check specific post config
        if (!empty($_GET['config'])) {
            return App::task()->checkContext('BACKEND') && App::blog()->isDefined();
        }

        return null;
    }
}
