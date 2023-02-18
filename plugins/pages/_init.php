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
class initPages
{
    // Constants

    /**
     * Pages permission
     *
     * @var        string
     */
    public const PERMISSION_PAGES = 'pages';
}

// backward compatibility

/**
 * This class is an alias of initPages.
 *
 * @deprecated since 2.25
 */
class dcPages extends initPages
{
}
