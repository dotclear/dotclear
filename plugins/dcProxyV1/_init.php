<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

/**
 * @deprecated  since 2.28, use \Dotclear\Plugin\blogroll\Blogroll instead
 */
class initBlogroll
{
    /**
     * Blogroll permission.
     *
     * @deprecated  since 2.28, use \Dotclear\Plugin\blogroll\Blogroll instead
     *
     * @var        string
     */
    public const PERMISSION_BLOGROLL = 'blogroll';

    /**
     * Links (blogroll) table name.
     *
     * @deprecated  since 2.28, use \Dotclear\Plugin\blogroll\Blogroll instead
     *
     * @var        string
     */
    public const LINK_TABLE_NAME = 'link';
}

/**
 * @deprecated since 2.25, use \Dotclear\Plugin\blogroll\Blogroll instead
 */
class dcLinks extends initBlogroll
{
}

/**
 * @deprecated  since 2.28, use \Dotclear\Plugin\antispam\Antispam instead
 */
class initAntispam
{
    /**
     * Spam rules table name.
     *
     * @deprecated  since 2.28, use \Dotclear\Plugin\antispam\Antispam instead
     *
     * @var     string  SPAMRULE_TABLE_NAME
     */
    public const SPAMRULE_TABLE_NAME = 'spamrule';
}

/**
 * @deprecated  since 2.28, use \Dotclear\Plugin\pages\Pages instead
 */
class initPages
{
    /**
     * Pages permission.
     *
     * @deprecated  since 2.28, use \Dotclear\Plugin\pages\Pages instead
     *
     * @var     string  PERMISSION_PAGES
     */
    public const PERMISSION_PAGES = 'pages';
}

/**
 * @deprecated since 2.25, use \Dotclear\Plugin\pages\Pages instead
 */
class dcPages extends initPages
{
}
