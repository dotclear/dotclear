<?php
/**
 * Version handler.
 *
 * Handle id,version pairs through database.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

interface CoreFactoryInterface
{
	public function con(): \Dotclear\Database\AbstractHandler;
	public function version(): \Dotclear\Core\Version;
}