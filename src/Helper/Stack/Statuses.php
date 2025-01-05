<?php

declare(strict_types=1);

namespace Dotclear\Helper\Stack;

use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;

/**
 * @brief       Statuses handler.
 *
 * @since       2.33
 * @package     Dotclear
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
class Statuses
{
	/**
	 * @var    array<int, Status>  $stack
	 */
	protected array $stack = [];

    /**
     * Create status instance.
     *
     * @param   array<int, Status>  $descriptors    The statuses descriptors stack
     */
    public function __construct(
        protected string $column,
        array $descriptors,
        protected int $limit = 0
    ) {
        foreach($descriptors as $descriptor) {
            if ($descriptor instanceof Status) {
                $this->set($descriptor);
            }
        }
    }

    /**
     * Sets a status.
     *
     * Returns false if status already exists.
     */
    public function set(Status $descriptor): bool
    {
        if ($this->has($descriptor->level()) || $this->has($descriptor->id())) {
            return false;
        }

        $this->stack[] = $descriptor;

        return true;
    }

    /**
     * Checks if a status exists.
     *
     * Search by (string) id or (int) level.
     */
    public function has(int|string $needle): bool
    {
        foreach($this->stack as $descriptor) {
            if (is_int($needle) && $descriptor->level() === $needle
                || is_string($needle) && $descriptor->id() === $needle
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
    public function isLimited(int|string $needle): bool
    {
        foreach($this->stack as $descriptor) {
            if (is_int($needle) && $descriptor->level() === $needle
                || is_string($needle) && $descriptor->id() === $needle
            ) {
                return $descriptor->level() <= $this->limit;
            }
        }

        return true;
    }

    /**
     * Gets status limit level.
     * 
     * Default level is the last non OK level before OK levels.
     * Returns last status level if limit level status does not exists.
     */
    public function limit(): int
    {
        foreach($this->stack as $descriptor) {
            if ($descriptor->level() === $this->limit) {
                return $this->limit;
            }
        }

        // at least, returns last status
        $last = end($this->stack);
        return $last ? $last->level() : 0;
    }

    /**
     * Gets a status level.
     *
     * Search by (string) id.
     * Returns limit level if status does not exists.
     */
    public function level(string $needle): int
    {
        foreach($this->stack as $descriptor) {
            if ($descriptor->id() === $needle) {
                return $descriptor->level();
            }
        }

        return $this->limit();
    }

    /**
     * Gets a status id.
     *
     * Search by (int) level.
     * Returns limit level id if status does not exists.
     */
    public function id(int $needle): string
    {
        foreach($this->stack as $descriptor) {
            if ($descriptor->level() === $needle) {
                return $descriptor->id();
            }
        }

        return $this->id($this->limit());
    }

    /**
     * Gets a status name.
     *
     * Search by (string) id or (int) level.
     * Returns limit level name if status does not exists.
     */
    public function name(int|string $needle): string
    {
        foreach($this->stack as $descriptor) {
            if (is_int($needle) && $descriptor->level() === $needle
                || is_string($needle) && $descriptor->id() === $needle
            ) {
                return $descriptor->name();
            }
        }

        return $this->name($this->limit());
    }

    /**
     * Gets a status icon URI.
     *
     * Search by (string) id or (int) level.
     * Returns limit level icon if status does not exists.
     */
    public function icon(int|string $needle): string
    {
        foreach($this->stack as $descriptor) {
            if (is_int($needle) && $descriptor->level() === $needle
                || is_string($needle) && $descriptor->id() === $needle
            ) {
                return $descriptor->icon();
            }
        }

        return $this->icon($this->limit());
    }

    /**
     * Get status admin image.
     *
     * Search by (string) id or (int) level.
     */
    public function image(int|string $needle, bool $with_text = false): Text|Img
    {
        foreach($this->stack as $descriptor) {
            if (is_int($needle) && $descriptor->level() === $needle
                || is_string($needle) && $descriptor->id() === $needle
            ) {
                $img = (new Img($descriptor->icon()))
                    ->alt(Html::escapeHTML($descriptor->name()))
                    ->class(['mark', 'mark-' . $descriptor->id()]);

                return $with_text ?
                    (new Text(null, $img->render() . Html::escapeHTML($descriptor->name()))) :
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
    public function dump(): array
    {
        return $this->stack;
    }

    /**
     * Gets statuses.
     *
     * @return  array<int, string>  The statuses by level/name.
     */
    public function statuses(): array
    {
        $combo = [];
        foreach ($this->stack as $descriptor) {
            $combo[$descriptor->level()] = $descriptor->name();
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
        foreach ($this->stack as $descriptor) {
            $combo[$descriptor->name()] = (string) $descriptor->level();
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