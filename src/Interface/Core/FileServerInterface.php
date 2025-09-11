<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Interface for the helper to serve file.
 *
 * This class checks request URI to find pf, tf and vf queries and serve related file.
 * It is limited as it is loaded before any utility to speed up requests.
 * Warning: Frontend URLs are not defined nor the context (Backend, Frontend, …).
 *
 * @since   2.27
 */
interface FileServerInterface
{
    /**
     * Supported types of resource.
     *
     * @var     string[]  DEFAULT_TYPES
     */
    public const DEFAULT_TYPES = [
        'plugin',
        'theme',
        'core',
        'var',
    ];

    /**
     * Supported file extension.
     *
     * @var     string[]  DEFAULT_EXTENSIONS
     */
    public const DEFAULT_EXTENSIONS = [
        'css',
        'eot',
        'gif',
        'html',
        'jpeg',
        'jpg',
        'js',
        'mjs',
        'json',
        'otf',
        'png',
        'svg',
        'swf',
        'ttf',
        'txt',
        'webp',
        'avif',
        'woff',
        'woff2',
        'xml',
    ];

    /**
     * Supported core base folder.
     *
     * @var     string[]  DEFAULT_CORE_LIMITS
     */
    public const DEFAULT_CORE_LIMITS = [
        'js',
        'css',
        'img',
        'smilies',
    ];

    /**
     * Supported minifield file extension.
     *
     * @var     string[]  DEFAULT_MINIFIED
     */
    public const DEFAULT_MINIFIED = [
        'css',
        'js',
        'mjs',
    ];

    /**
     * File extension that does not need cache in dev mode.
     *
     * @var     string[]  DEFAULT_NOCACHE
     */
    public const DEFAULT_NOCACHE = [
        'css',
        'js',
        'mjs',
        'html',
    ];
}
