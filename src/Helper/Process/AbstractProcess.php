<?php

/**
 * @package     Dotclear
 * @subpackage Core
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Process;

/**
 * @brief   Process class structure.
 *
 * You should use trait TraitProcess intead of class AbstractProcess.
 */
abstract class AbstractProcess
{
    use TraitProcess;
}
