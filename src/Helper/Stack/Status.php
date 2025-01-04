<?php

declare(strict_types=1);

namespace Dotclear\Helper\Stack;

/**
 * @brief   	Status descriptor.
 *
 * @todo 		Add image and class properties
 *
 * @since       2.33
 * @package     Dotclear
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
class Status
{
	public function __construct(
		protected int $level,
		protected string $id,
		protected string $name
	) {
	}

	/**
	 * Gets status level.
	 */
	public function level(): int
	{
		return $this->level;
	}

	/**
	 * Gets status id.
	 */
	public function id(): string
	{
		return $this->id;
	}

	/**
	 * Gets status name.
	 */
	public function name(): string
	{
		return $this->name;
	}
}