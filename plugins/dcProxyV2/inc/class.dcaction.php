<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

use Dotclear\Core\Backend\Action\Actions;
use Dotclear\Core\Backend\Action\ActionsBlogs;
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Action\ActionsPosts;

/**
 * @brief   The module backend actions aliases handler.
 * @ingroup dcProxyV2
 */
abstract class dcActionsPage extends Actions
{
    public function __construct(dcCore $core, ?string $uri, array $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPostsActionsPage extends ActionsPosts
{
    public function __construct(dcCore $core, ?string $uri, array $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcCommentsActionsPage extends ActionsComments
{
    public function __construct(dcCore $core, ?string $uri, array $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcBlogsActionsPage extends ActionsBlogs
{
    public function __construct(dcCore $core, ?string $uri, array $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPagesActionsPage extends Dotclear\Plugin\pages\BackendActions // dcPagesActions
{
    public function __construct(dcCore $core, ?string $uri, array $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}
