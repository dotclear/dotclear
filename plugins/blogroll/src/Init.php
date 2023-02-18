<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use dcNsProcess;

class Init extends dcNsProcess
{
    // Constants

    /**
     * Blogroll permission
     *
     * @var        string
     */
    public const PERMISSION_BLOGROLL = 'blogroll';

    /**
     * Links table name
     *
     * @var        string
     */
    public const LINK_TABLE_NAME = 'link';

    public static function init(): bool
    {
        self::$init = true;

        // backward compatibility

        /*
         * @deprecated since 2.25
         */
        class_alias(__CLASS__, 'dcLinks');

        /*
         * @deprecated since 2.26
         */
        class_alias(__CLASS__, 'initBlogroll');

        return self::$init;
    }
}
