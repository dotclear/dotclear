<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Dotclear\Core\Backend\Filter\FilterBlogs;
use Dotclear\Core\Backend\Filter\FilterComments;
use Dotclear\Core\Backend\Filter\FilterMedia;
use Dotclear\Core\Backend\Filter\FilterPosts;
use Dotclear\Core\Backend\Filter\FilterUsers;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;

/**
 * @brief   Admin list filter helpers library
 *
 * @since   2.36
 */
class Filter extends Container
{
    public const CONTAINER_ID = 'backendfilter';

    public function __construct()
    {
        // Create a non replaceable factory
        parent::__construct(new Factory(static::CONTAINER_ID, false));
    }

    public function getDefaultServices(): array
    {
        return [
            FilterBlogs::class    => FilterBlogs::class,
            FilterComments::class => FilterComments::class,
            FilterMedia::class    => FilterMedia::class,
            FilterPosts::class    => FilterPosts::class,
            FilterUsers::class    => FilterUsers::class,
        ];
    }

    /**
     * Get backend blogs list filters helper instance.
     */
    public function blogs(): FilterBlogs
    {
        // @phpstan-ignore return.type
        return $this->get(FilterBlogs::class);
    }

    /**
     * Get backend comments list filters helper instance.
     */
    public function comments(): FilterComments
    {
        // @phpstan-ignore return.type
        return $this->get(FilterComments::class);
    }

    /**
     * Get backend media list filters helper instance.
     */
    public function media(): FilterMedia
    {
        // @phpstan-ignore return.type
        return $this->get(FilterMedia::class);
    }

    /**
     * Get backend posts list filters helper instance.
     */
    public function posts(): FilterPosts
    {
        // @phpstan-ignore return.type
        return $this->get(FilterPosts::class);
    }

    /**
     * Get backend users list filters helper instance.
     */
    public function users(): FilterUsers
    {
        // @phpstan-ignore return.type
        return $this->get(FilterUsers::class);
    }
}
