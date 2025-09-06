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
use Dotclear\Process\Install\Install;
use Dotclear\Process\Install\Wizard;

/**
 * @brief   Utility class for install context.
 *
 * This utility calls itself Wizard or Intall process.
 */
class Utility extends AbstractUtility
{
    public const CONTAINER_ID = 'Install';

    public const UTILITY_PROCESS = [
        'Install' => Install::class,
        'Wizard'  => Wizard::class,
    ];

    public static function process(): bool
    {
        // Call utility process from here
        App::task()->loadProcess(is_file(App::config()->configPath()) ? 
            (new \ReflectionClass(Install::class))->getShortName() :
            (new \ReflectionClass(Wizard::class))->getShortName()
        );

        return true;
    }
}
