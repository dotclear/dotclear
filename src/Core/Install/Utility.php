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
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Helper\Process\AbstractUtility;
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
        Install::class,
        Wizard::class,
    ];

    protected function getDefaultServices(): array
    {
        return [
            Favorites::class => Favorites::class,
            Page::class      => Page::class,
            Utils::class     => Utils::class,
        ];
    }

    public function favorites(): Favorites
    {
        return $this->get(Favorites::class);
    }

    public function page(): Page
    {
        return $this->get(Page::class);
    }

    public function utils(): Utils
    {
        return $this->get(Utils::class);
    }

    public static function process(): bool
    {
        // Call utility process from here
        App::task()->loadProcess(
            is_file(App::config()->configPath()) ?
            (new \ReflectionClass(Install::class))->getShortName() :
            (new \ReflectionClass(Wizard::class))->getShortName()
        );

        return true;
    }

    public static function init(): bool
    {
        return !App::config()->cliMode();
    }
}
