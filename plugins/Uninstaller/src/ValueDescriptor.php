<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

/**
 * @brief   Cleaner value descriptor.
 * @ingroup Uninstaller
 *
 * Description of a value from CleanerParent::value()
 * and CleanerParent::related()
 */
class ValueDescriptor
{
    /**
     * Contructor populate descriptor properties.
     *
     * @param   string  $ns     The namespace
     * @param   string  $id     The ID on the namespace
     * @param   int     $count  The count of ID on the namespace
     */
    public function __construct(
        public readonly string $ns = '',
        public readonly string $id = '',
        public readonly int $count = 0,
    ) {
    }

    /**
     * Get descriptor properties.
     *
     * @return  array<string,mixed>     The properties
     */
    public function dump(): array
    {
        return get_object_vars($this);
    }
}
