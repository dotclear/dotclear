<?php
/**
 * Psalm mock PHP 8 attributes
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

#[Attribute]
final class AllowDynamicProperties
{
    public function __construct()
    {
    }
}
