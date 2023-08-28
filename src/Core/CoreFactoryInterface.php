<?php
/**
 * Core factory interface.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Session;
use Dotclear\Helper\Behavior;

interface CoreFactoryInterface
{
	public function behavior(): Behavior;
	public function blogs(): Blogs;
	public function con(): AbstractHandler;
	public function filter(): Filter;
	public function formater(): Formater;
	public function nonce(): Nonce;
	public function postTypes(): PostTypes;
	public function session(): Session;
	public function version(): Version;
}