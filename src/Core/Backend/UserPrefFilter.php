<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;

/**
 * Admin user preference filter
 */
class UserPrefFilter
{
    /**
     * @param string                                                $type       Filter type
     * @param ?string                                               $label      Filter label
     * @param null|array<string, string>|array<OptGroup|Option>     $options    List of options
     * @param ?string                                               $sortby     Default sort by field
     * @param ?string                                               $order      Sort order (desc|asc)
     * @param ?string                                               $nb_label   Label for nb of element per page
     * @param ?int                                                  $nb         Number of element per page
     */
    public function __construct(
        protected string $type,
        protected ?string $label,
        protected ?array $options,
        protected ?string $sortby,
        protected ?string $order,
        protected ?string $nb_label,
        protected ?int $nb,
    ) {
    }

    /**
     * Get user preferences filter type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get user preferences filter label
     */
    public function getLabel(): ?string
    {
        return $this->label ?? null;
    }

    /**
     * Get user preferences filter combo options
     *
     * @return null|array<string, string>|array<OptGroup|Option>
     */
    public function getOptions(): ?array
    {
        return $this->options ?? null;
    }

    protected function findValueInOptGroup(string $needle, Optgroup $optgroup): bool
    {
        $items = $optgroup->items;
        if ($items === null) {
            // No items in this OptGroup
            return false;
        }

        foreach ($items as $option) {
            if ($option instanceof Optgroup) {
                return $this->findValueInOptGroup($needle, $option);
            }

            if ($option instanceof Option && $option->value === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value is available in the options
     *
     * @param  string   $needle   Value to search for
     */
    public function findOption(string $needle): bool
    {
        if ($this->options !== null) {
            foreach ($this->options as $option) {
                if (is_string($option) && $option === $needle) {
                    return true;
                }

                if ($option instanceof Optgroup) {
                    return $this->findValueInOptGroup($needle, $option);
                }

                if ($option instanceof Option && $option->value === $needle) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get user preferences sort by
     */
    public function getSortBy(): ?string
    {
        return $this->sortby ?? null;
    }

    /**
     * Get user preferences sort order
     */
    public function getOrder(): ?string
    {
        return $this->order ?? null;
    }

    /**
     * Get user preferences label for number of element per page
     */
    public function getNbLabel(): ?string
    {
        return $this->nb_label ?? null;
    }

    /**
     * Get user preferences number of element per page
     */
    public function getNb(): ?int
    {
        return $this->nb ?? null;
    }
}
