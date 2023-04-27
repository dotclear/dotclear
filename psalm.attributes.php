<?php
/**
 * Psalm mock PHP 8 attributes
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

#[Attribute]
final class AllowDynamicProperties
{
    public function __construct()
    {
    }
}
