<?php

/**
 * @package     Dotclear
 * @subpackage Core
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   Process class structure.
 *
 * @deprecated  since 2.36, use Dotclear\Helper\Process\traitProcess instead
 */
abstract class Process
{
    use TraitProcess;
}
