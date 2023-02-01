<?php

/**
 * Actions
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
abstract class dcActionsPage extends dcActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPostsActionsPage extends dcPostsActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcCommentsActionsPage extends dcCommentsActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcBlogsActionsPage extends dcBlogsActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPagesActionsPage extends Dotclear\Plugin\pages\BackendActions // dcPagesActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])    // @phpstan-ignore-line
    {
        parent::__construct($uri, $redirect_args);
    }
}
