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
use Dotclear\Core\Backend\Listing\ListingBlogs;
use Dotclear\Core\Backend\Listing\ListingComments;
use Dotclear\Core\Backend\Listing\ListingMedia;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Listing\ListingPostsMini;
use Dotclear\Core\Backend\Listing\ListingUsers;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;

/**
 * @brief   Admin list helpers library
 *
 * @since   2.36
 */
class Listing extends Container
{
    public const CONTAINER_ID = 'backendlisting';

    public function __construct()
    {
        // Create a non replaceable factory
        parent::__construct(new Factory(static::CONTAINER_ID, false));
    }

    public function getDefaultServices(): array
    {
        return [
            ListingBlogs::class     => ListingBlogs::class,
            ListingComments::class  => ListingComments::class,
            ListingMedia::class     => ListingMedia::class,
            ListingPosts::class     => ListingPosts::class,
            ListingPostsMini::class => ListingPostsMini::class,
            ListingUsers::class     => ListingUsers::class,
            Pager::class            => Pager::class,
        ];
    }

    /**
     * Get backend blogs listing helper instance.
     *
     * New instance is returned on each call.
     */
    public function blogs(MetaRecord $rs, mixed $rs_count): ListingBlogs
    {
        return $this->get(ListingBlogs::class, true, rs: $rs, rs_count: $rs_count);
    }

    /**
     * Get backend comments listing helper instance.
     *
     * New instance is returned on each call.
     */
    public function comments(MetaRecord $rs, mixed $rs_count): ListingComments
    {
        return $this->get(ListingComments::class, true, rs: $rs, rs_count: $rs_count);
    }

    /**
     * Get backend media listing helper instance.
     *
     * New instance is returned on each call.
     */
    public function media(MetaRecord $rs, mixed $rs_count): ListingMedia
    {
        return $this->get(ListingMedia::class, true, rs: $rs, rs_count: $rs_count);
    }

    /**
     * Get backend posts listing helper instance.
     *
     * New instance is returned on each call.
     */
    public function posts(MetaRecord $rs, mixed $rs_count): ListingPosts
    {
        return $this->get(ListingPosts::class, true, rs: $rs, rs_count: $rs_count);
    }

    /**
     * Get backend posts mini listing helper instance.
     *
     * New instance is returned on each call.
     */
    public function postsMini(MetaRecord $rs, mixed $rs_count): ListingPostsMini
    {
        return $this->get(ListingPostsMini::class, true, rs: $rs, rs_count: $rs_count);
    }

    /**
     * Get backend users listing helper instance.
     *
     * New instance is returned on each call.
     */
    public function users(MetaRecord $rs, mixed $rs_count): ListingUsers
    {
        return $this->get(ListingUsers::class, true, rs: $rs, rs_count: $rs_count);
    }

    /**
     * Ge backend pager instance.
     *
     * New instance is returned on each call.
     */
    public function pager(int $current_page, int $nb_elements, int $nb_per_page = 10, int $nb_pages_per_group = 10): Pager
    {
        return $this->get(
            Pager::class, // service
            true,         // reload
            current_page: $current_page,
            nb_elements: $nb_elements,
            nb_per_page: $nb_per_page,
            nb_pages_per_group: $nb_pages_per_group
        );
    }
}