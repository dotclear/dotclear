<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

abstract class dcActionsPage extends dcActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPostsActionsPage extends dcPostsActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcCommentsActionsPage extends dcCommentsActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcBlogsActionsPage extends dcBlogsActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPagesActionsPage extends dcPagesActions
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}
