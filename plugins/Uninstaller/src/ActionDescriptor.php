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
 * @brief   Cleaner action descriptor
 * @ingroup Uninstaller
 */
class ActionDescriptor
{
    /**
     * Contructor populate descriptor properties.
     *
     * @param   string  $id         The action ID
     * @param   string  $query      The query message
     * @param   string  $success    The succes message
     * @param   string  $error      The error message
     * @param   string  $ns         The namespace (for defined action)
     * @param   string  $select     The generic message (used for self::values() management)
     * @param   bool    $default    The default state of action form field (checked or not)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $query,
        public readonly string $success,
        public readonly string $error,
        public readonly string $ns = '',
        public readonly string $select = '',
        public readonly bool $default = true
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
