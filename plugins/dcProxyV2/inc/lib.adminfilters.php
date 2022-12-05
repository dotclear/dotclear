<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @brief Generic class for admin list filters form
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class adminGenericFilter extends adminGenericFilterV2
{
    public function __construct(dcCore $core, string $type) // @phpstan-ignore-line
    {
        parent::__construct($type);
    }
}
