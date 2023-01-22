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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcBlogroll
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
}

// backward compatibility

/**
 * This class is an alias of dcBlogroll.
 *
 * @deprecated since 2.25
 */
class dcLinks extends dcBlogroll
{
}
