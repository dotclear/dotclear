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

abstract class dcActionsPage extends dcActionsPageV2
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPostsActionsPage extends dcPostsActionsPageV2
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcCommentsActionsPage extends dcCommentsActionsPageV2
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcBlogsActionsPage extends dcBlogsActionsPageV2
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}

class dcPagesActionsPage extends dcPagesActionsPageV2
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
    }
}
