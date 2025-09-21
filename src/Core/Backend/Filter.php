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

use Dotclear\App;
use Dotclear\Core\Backend\Filter\FilterBlogs;
use Dotclear\Core\Backend\Filter\FilterComments;
use Dotclear\Core\Backend\Filter\FilterMedia;
use Dotclear\Core\Backend\Filter\FilterPosts;
use Dotclear\Core\Backend\Filter\FilterUsers;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;

/**
 * @brief   Admin auth helpers library
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

    public function blogs(): FilterBlogs
    {
        return $this->get(FilterBlogs::class);
    }

    public function comments(): FilterComments
    {
        return $this->get(FilterComments::class);
    }

    public function media(): FilterMedia
    {
        return $this->get(FilterMedia::class);
    }

    public function posts(): FilterPosts
    {
        return $this->get(FilterPosts::class);
    }

    public function users(): FilterUsers
    {
        return $this->get(FilterUsers::class);
    }
}