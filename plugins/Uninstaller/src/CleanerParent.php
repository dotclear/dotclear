<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

/**
 * @brief   Cleaner abstract class.
 * @ingroup Uninstaller
 *
 * Cleaner manages only one part of uninstall process.
 * For exemple Settings, Caches, db, etc...
 */
abstract class CleanerParent
{
    /**
     * The cleaner Id.
     */
    public readonly string $id;

    /**
     * The cleaner name.
     */
    public readonly string $name;

    /**
     * The cleaner description.
     */
    public readonly string $desc;

    /**
     * The cleaner actions decriptions.
     *
     * @var     array<string,ActionDescriptor>  $actions
     */
    public readonly array $actions;

    /**
     * Constructor set up a Cleaner.
     */
    public function __construct(CleanerDescriptor $descriptor)
    {
        $this->id      = $descriptor->id;
        $this->name    = $descriptor->name;
        $this->desc    = $descriptor->desc;
        $this->actions = $descriptor->actions;
    }

    /**
     * Get an action description.
     *
     * @return  null|ActionDescriptor   The action descriptor
     */
    final public function get(string $id): ?ActionDescriptor
    {
        return $this->actions[$id] ?? null;
    }

    /**
     * Get list of distirbuted values for the cleaner.
     *
     * @return  array<int,string>   The values [value,]
     */
    abstract public function distributed(): array;

    /**
     * Get all possible ns values from the cleaner.
     *
     * @return  array<int,ValueDescriptor>  The values.
     */
    abstract public function values(): array;

    /**
     * Get all related values for a namespace from the cleaner.
     *
     * @param   string  $ns     The namespace
     *
     * @return  array<int,ValueDescriptor>  The values.
     */
    public function related(string $ns): array
    {
        return [];
    }

    /**
     * Execute action on an value.
     *
     * @param   string  $action     The action id
     * @param   string  $ns         The value.
     *
     * @return  bool    The success
     */
    abstract public function execute(string $action, string $ns): bool;
}
