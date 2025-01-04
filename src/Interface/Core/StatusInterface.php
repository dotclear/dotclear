<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Helper\Stack\Statuses;

/**
 * @brief   Statuses handler interface.
 *
 * @since   2.33
 */
interface StatusInterface
{
	/**
	 * Blog statuses handler.
	 */
	public function blog(): Statuses;

	/**
	 * Comment statuses handler.
	 */
	public function comment(): Statuses;

	/**
	 * Post statuses handler.
	 */
	public function post(): Statuses;

	/**
	 * User statuses handler.
	 */
	public function user(): Statuses;
}