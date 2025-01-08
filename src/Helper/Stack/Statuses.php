<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Stack;

use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;

/**
 * @brief       Statuses handler.
 *
 * @since       2.33
 */
class Statuses
{
    /**
     * @var    array<int, Status>  $statuses
     */
    protected array $statuses = [];

    /**
     * Create status instance.
     *
     * @param   array<int, Status>  $statuses    The status stack
     */
    public function __construct(
        protected string $column,
        array $statuses = [],
        protected int $threshold = 0
    ) {
        foreach ($statuses as $status) {
            if ($status instanceof Status) {    // @phpstan-ignore-line (false positive from PHPDoc for $statuses)
                $this->set($status);
            }
        }
    }

    /**
     * Sets a status.
     *
     * Returns false if status already exists.
     */
    public function set(Status $status): bool
    {
        if ($this->has($status->level()) || $this->has($status->id())) {
            return false;
        }

        $this->statuses[] = $status;

        return true;
    }

    /**
     * Checks if a status exists.
     *
     * Search by (string) id or (int) level.
     */
    public function has(int|string $needle): bool
    {
        foreach ($this->statuses as $status) {
            if (is_int($needle)       && $status->level() === $needle
                || is_string($needle) && $status->id()    === $needle
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a status level is restricted.
     *
     * Search by (string) id or (int) level.
     *
     * Levels are compared like that:
     * - needle <= defaul level = restricted
     * - needle > defaut level = not restricted
     */
    public function isRestricted(int|string $needle): bool
    {
        foreach ($this->statuses as $status) {
            if (is_int($needle)       && $status->level() === $needle
                || is_string($needle) && $status->id()    === $needle
            ) {
                return $status->level() <= $this->threshold;
            }
        }

        return true;
    }

    /**
     * Gets status threshold level.
     *
     * Default level is the last non OK level before OK levels.
     * Returns last status level if threshold level status does not exists.
     */
    public function threshold(): int
    {
        foreach ($this->statuses as $status) {
            if ($status->level() === $this->threshold) {
                return $this->threshold;
            }
        }

        // at least, returns last status
        $last = end($this->statuses);

        return $last ? $last->level() : 0;
    }

    /**
     * Gets a status level.
     *
     * Search by (string) id.
     * Returns threshold level if status does not exists.
     */
    public function level(string $needle): int
    {
        foreach ($this->statuses as $status) {
            if ($status->id() === $needle) {
                return $status->level();
            }
        }

        return $this->threshold();
    }

    /**
     * Gets a status id.
     *
     * Search by (int) level.
     * Returns threshold level id if status does not exists.
     */
    public function id(int $needle): string
    {
        foreach ($this->statuses as $status) {
            if ($status->level() === $needle) {
                return $status->id();
            }
        }

        return $this->id($this->threshold());
    }

    /**
     * Gets a status name (translated).
     *
     * Search by (string) id or (int) level.
     * Returns threshold level name if status does not exists.
     */
    public function name(int|string $needle): string
    {
        foreach ($this->statuses as $status) {
            if (is_int($needle)       && $status->level() === $needle
                || is_string($needle) && $status->id()    === $needle
            ) {
                return __($status->name());
            }
        }

        return $this->name($this->threshold());
    }

    /**
     * Gets a status icon URI.
     *
     * Search by (string) id or (int) level.
     * Returns threshold level icon if status does not exists.
     */
    public function icon(int|string $needle): string
    {
        foreach ($this->statuses as $status) {
            if (is_int($needle)       && $status->level() === $needle
                || is_string($needle) && $status->id()    === $needle
            ) {
                return $status->icon();
            }
        }

        return $this->icon($this->threshold());
    }

    /**
     * Get status admin image.
     *
     * Search by (string) id or (int) level.
     */
    public function image(int|string $needle, bool $with_text = false): Text|Img
    {
        foreach ($this->statuses as $status) {
            if (is_int($needle)       && $status->level() === $needle
                || is_string($needle) && $status->id()    === $needle
            ) {
                $img = (new Img($status->icon()))
                    ->alt(Html::escapeHTML(__($status->name())))
                    ->class(['mark', 'mark-' . $status->id()]);

                return $with_text ?
                    (new Text(null, $img->render() . Html::escapeHTML(__($status->name())))) :
                    $img;
            }
        }

        return $with_text ? (new Text(null, '')) : (new Img(''));
    }

    /**
     * Gets status table column for query.
     */
    public function column(): string
    {
        return $this->column;
    }

    /**
     * Gets statuses descriptors.
     *
     * @return  array<int, Status>  The descriptors.
     */
    public function dump(bool $with_hidden = true): array
    {
        if ($with_hidden) {
            return $this->statuses;
        }

        $statuses = [];
        foreach ($this->statuses as $status) {
            if (!$status->hidden()) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }

    /**
     * Gets statuses.
     *
     * @return  array<int, string>  The statuses by level/name.
     */
    public function statuses(): array
    {
        $combo = [];
        foreach ($this->statuses as $status) {
            $combo[$status->level()] = __($status->name());
        }

        return $combo;
    }

    /**
     * Gets statuses combo.
     *
     * Levels are return as string.
     *
     * @return  array<string, string>   The statuses by name/level .
     */
    public function combo(): array
    {
        $combo = [];
        foreach ($this->statuses as $status) {
            $combo[__($status->name())] = (string) $status->level();
        }

        return $combo;
    }

    /**
     * Gets status form filter.
     */
    public function filter(): Filter
    {
        return (new Filter('status'))
            ->param($this->column())
            ->title(__('Status:'))
            ->options(['-' => '', ...$this->combo()])
            ->prime(true);
    }
}
