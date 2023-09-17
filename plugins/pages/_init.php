<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

/**
 * @ingroup pages
 */
class initPages
{
    /**
     * Pages permission.
     *
     * @var     string  PERMISSION_PAGES
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
