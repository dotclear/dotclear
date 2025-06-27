<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Interface\Module\ModulesInterface;

/**
 * @brief   Theems Handler interface.
 *
 * @since   2.28
 */
interface ThemesInterface extends ModulesInterface
{
    /**
     * Determines whether the specified theme is overloadable.
     *
     * @param      string  $id     The theme identifier
     */
    public function isOverloadable(string $id): bool;
}
