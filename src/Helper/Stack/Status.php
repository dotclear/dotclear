<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Stack;

/**
 * @brief       Status descriptor.
 *
 * @since       2.33
 */
class Status
{
    public function __construct(
        protected int $level,
        protected string $id,
        protected string $name,
        protected string $plural_name,
        protected string $icon,
        protected string $icon_dark = '',
        protected bool $hidden = false
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
     * Gets status name (English, not translated).
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets status plural name (English, not translated).
     */
    public function pluralName(): string
    {
        return $this->plural_name;
    }

    /**
     * Gets status icon URI.
     */
    public function icon(): string
    {
        return $this->icon;
    }

    /**
     * Gets status dark icon URI.
     */
    public function iconDark(): string
    {
        return $this->icon_dark;
    }

    /**
     * Check if status is hidden from some actions.
     */
    public function hidden(): bool
    {
        return $this->hidden;
    }
}
