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

use Dotclear\Core\Backend\Action\ActionsBlogs;
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;

/**
 * @brief   Admin list action helpers library
 *
 * @since   2.36
 */
class Action extends Container
{
    public const CONTAINER_ID = 'backendaction';

    public function __construct()
    {
        // Create a non replaceable factory
        parent::__construct(new Factory(static::CONTAINER_ID, false));
    }

    public function getDefaultServices(): array
    {
        return [
            ActionsBlogs::class    => ActionsBlogs::class,
            ActionsComments::class => ActionsComments::class,
            ActionsPosts::class    => ActionsPosts::class,
        ];
    }

    /**
     * Get blogs list action instance.
     *
     * New instance is returned on each call.
     *
     * @param   null|string             $uri            The form uri
     * @param   array<string, mixed>    $redir_args     The redirection $_GET arguments,
     */
    public function blogs(?string $uri, array $redir_args = []): ActionsBlogs
    {
        return $this->get(ActionsBlogs::class, true, uri: $uri, redir_args: $redir_args);
    }

    /**
     * Get comments list action instance.
     *
     * New instance is returned on each call.
     *
     * @param   null|string             $uri            The form uri
     * @param   array<string, mixed>    $redir_args     The redirection $_GET arguments,
     */
    public function comments(?string $uri, array $redir_args = []): ActionsComments
    {
        return $this->get(ActionsComments::class, true, uri: $uri, redir_args: $redir_args);
    }

    /**
     * Get posts list action instance.
     *
     * New instance is returned on each call.
     *
     * @param   null|string             $uri            The form uri
     * @param   array<string, mixed>    $redir_args     The redirection $_GET arguments,
     */
    public function posts(?string $uri, array $redir_args = []): ActionsPosts
    {
        return $this->get(ActionsPosts::class, true, uri: $uri, redir_args: $redir_args);
    }
}
