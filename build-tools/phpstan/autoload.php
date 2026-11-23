<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

// Autoload of the PHPStan extension, when it is not done by Composer:
// phpstan analyse --autoload-file <path to Dotclear>/build-tools/phpstan/autoload.php
require_once __DIR__ . '/RecordExtensionMethodReflection.php';
require_once __DIR__ . '/RecordExtensionMethodsExtension.php';
