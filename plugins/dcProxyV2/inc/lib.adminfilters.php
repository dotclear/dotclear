<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

/**
 * @brief Generic class for admin list filters form
 */
class adminGenericFilter extends adminGenericFilterV2
{
    public function __construct(dcCore $core, string $type)
    {
        parent::__construct($type);
    }
}
