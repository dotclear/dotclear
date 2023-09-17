<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

/**
 * @ingroup blogroll
 */
class initBlogroll
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
 * This class is an alias of initBlogroll.
 *
 * @deprecated since 2.25
 */
class dcLinks extends initBlogroll
{
}
