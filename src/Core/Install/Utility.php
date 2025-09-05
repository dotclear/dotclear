<?php

/**
 * @package         Dotclear
 * @subpackage      Install
 *
 * @defsubpackage   Install        Application install services
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Install
 * @brief       Dotclear application install utilities.
 */

namespace Dotclear\Core\Install;

use Dotclear\App;
use Dotclear\Core\Utility as AbstractUtility;

/**
 * @brief   Utility class for install context.
 *
 * This utility calls itself Wizard or Intall process.
 */
class Utility extends AbstractUtility
{
    public const UTILITY_ID = 'Install';

    public static function process(): bool
    {
        // Call utility process from here
        App::task()->loadProcess(is_file(App::config()->configPath()) ? 'Install' : 'Wizard');

        return true;
    }
}
