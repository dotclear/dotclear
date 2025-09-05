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

use Dotclear\App;
use Dotclear\Exception\ProcessException;
use Dotclear\Helper\TraitDynamicProperties;

/**
 * @brief   Utility class structure.
 *
 * This class tags child class as Utility.
 * * An utility MUST extends Dotclear\Core\Utility class.
 * * A process MUST extends Dotclear\Core\Process class.
 *
 * @since   2.36
 */
abstract class Utility extends Process
{
    use TraitDynamicProperties;

    /**
     * The utility ID.
     *
     * This is also the task context.
     *
     * @var     string  UTILITY_ID
     */
    public const UTILITY_ID = '';

    /**
     * The utility process namespace schema.
     *
     * @var     string  PROCESS_NS
     */
    public const PROCESS_NS = 'Dotclear\\Process\\%s\\%s';

    /**
     * Initialize application utility.
     */
    public static function init(): bool
    {
        return !App::config()->cliMode();
    }
}
