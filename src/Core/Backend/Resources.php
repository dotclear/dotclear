<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Backend help resources.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcCore;

class Resources
{
    /** @var    bool    Non global help context */
    private $context = false;

    /** @var    array<string,array<string,string>>   The help stack */
    private $stack = [];

    /**
     * Get / switch help context.
     *
     * If true, help sidebar is not loaded.
     *
     * @param   null|bool   $status     The context status
     *
     * @return  bool    The context status
     */
    public function context(?bool $status = null): bool
    {
        if (is_bool($status)) {
            $this->context = $status;
        }

        return $this->context;
    }

    /**
     * Get resources group of entries.
     *
     * @param   string  $group  The group
     *
     * @return  array<string,string>
     */
    public function __get(string $group): array
    {
        return $this->entries($group);
    }

    /**
     * Get resources group of entries.
     *
     * @param   string  $group  The group
     *
     * @return  array<string,string>
     */
    public function entries(string $group): array
    {
        $this->getDeprecated($group);

        return $this->stack[$group] ?? [];
    }

    /**
     * Get a reources entry.
     *
     * @param   string  $group  The group
     * @param   string  $entry  The entry
     *
     * @return  string  The entry value or empty string if not exists
     */
    public function entry(string $group, string $entry): string
    {
        return $this->stack[$group][$entry] ?? '';
    }

    /**
     * Set a resource entry value.
     *
     * We can not orverwrite existing entry
     *
     * @param   string  $group  The group
     * @param   string  $entry  The entry
     * @param   string  $value  The value
     *
     * @return  Resources   Self instance
     */
    public function set(string $group, string $entry, string $value): Resources
    {
        if (!isset($this->stack[$group][$entry])) {
            $this->stack[$group][$entry] = $value;
        }

        return $this;
    }

    /**
     * Reset a group of resources.
     *
     * @param   string  $group  The group
     *
     * @return  Resources   Self instance
     */
    public function reset(string $group): Resources
    {
        $this->stack[$group] = [];

        return $this;
    }

    /**
     * Populate stack with deprecated dcCore resources.
     *
     * dcCore::app()->resources array is deprecated since 2.28
     */
    private function getDeprecated(string $group): void
    {
        if (isset(dcCore::app()->resources[$group])) {
            if (!isset($this->stack[$group])) {
                $this->stack[$group] = [];
            }
            // cope with non array rss_news
            if (!is_array(dcCore::app()->resources[$group])) {
                dcCore::app()->resources[$group] = ['undefined' => dcCore::app()->resources[$group]];
            }
            $this->stack[$group] = array_merge($this->stack[$group], dcCore::app()->resources[$group]);
        }
    }
}
