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
use Dotclear\Process\Install\Cli;

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
        Cli::class,
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
        if (App::config()->cliMode()) {
            // In CLI mode process does the job
            App::task()->loadProcess((new \ReflectionClass(Cli::class))->getShortName());

            return true;
        }

        // Call utility process from here
        App::task()->loadProcess(
            App::config()->hasConfig() ?
            (new \ReflectionClass(Install::class))->getShortName() :
            (new \ReflectionClass(Wizard::class))->getShortName()
        );

        return true;
    }

    public static function init(): bool
    {
        // We need to pass CLI argument to App::task()->run()
        if (isset($_SERVER['argv'][1])) {
            $_SERVER['DC_RC_PATH'] = $_SERVER['argv'][1];
        }

        return true;
    }
}
