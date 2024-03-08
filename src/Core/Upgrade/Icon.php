<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

/**
 * @brief   Upgrade process menus and icons helper.
 *
 * @since   2.29
 */
class Icon
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $url,
        public readonly string $icon,
        public readonly string $dark,
        public readonly bool $perm,
        public readonly string $descr
    ) {
    }
}
