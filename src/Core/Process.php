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

/**
 * @brief   Process class structure.
 *
 * Provides an object to handle process in three steps:
 * init ? => process ? => render
 * (Before as modules file in dcModules::loadNsFile)
 *
 * @since   2.36    A class can extends Process or use TraitProcess
 */
abstract class Process
{
    use TraitProcess;
}
