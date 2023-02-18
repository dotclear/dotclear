<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use dcNsProcess;

class Init extends dcNsProcess
{
    // Constants

    /**
     * Pages permission
     *
     * @var        string
     */
    public const PERMISSION_PAGES = 'pages';

    public static function init(): bool
    {
        self::$init = true;

        // backward compatibility

        /*
         * @deprecated since 2.25
         */
        class_alias(__CLASS__, 'dcPages');

        /*
         * @deprecated since 2.26
         */
        class_alias(__CLASS__, 'initPages');

        return self::$init;
    }
}
