<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use dcNsProcess;

class Init extends dcNsProcess
{
    /**
     * Links table name
     *
     * @var        string
     */
    public const SPAMRULE_TABLE_NAME = 'spamrule';

    public static function init(): bool
    {
        self::$init = true;

        // backward compatibility

        /*
         * @deprecated since 2.26
         */
        class_alias(__CLASS__, 'initAntispam');

        return self::$init;
    }
}
