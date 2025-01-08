<?php

/**
 * @file
 * @brief       The plugin dcProxyV2 methods aliases
 * @ingroup     dcProxyV2
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

use Dotclear\App;
use Dotclear\Helper\Clearbricks;

/**
 * @brief   The module method alias handler.
 * @ingroup dcProxyV2
 */
class dcProxyV2
{
    public const SUFFIX = 'V2';

    /**
     * Loads behaviors.
     *
     * @param      class-string  $class  The class
     * @param      string        $file   The file
     */
    public static function loadBehaviors(string $class, string $file): void
    {
        Clearbricks::lib()->autoload([$class => $file]);

        $reflectionCore = new ReflectionClass($class);
        foreach ($reflectionCore->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            App::behavior()->addBehavior($method->name . self::SUFFIX, [$method->class, $method->name]);    // @phpstan-ignore-line
        }
    }

    /**
     * Declare class aliases
     *
     * @param      array<string, string>  $aliases  The aliases
     */
    public static function classAliases(array $aliases): void
    {
        foreach ($aliases as $aliasName => $realName) {
            class_alias($realName, $aliasName);
        }
    }
}

// Classes aliases
dcProxyV2::classAliases([
    // alias → real name (including namespace if necessary, for both)

    // Deprecated since 2.25
    'dcPagesActions'                      => Dotclear\Plugin\pages\BackendActions::class,
    'defaultWidgets'                      => Dotclear\Plugin\widgets\Widgets::class,
    'dcWidgets'                           => Dotclear\Plugin\widgets\WidgetsStack::class,
    'dcWidget'                            => Dotclear\Plugin\widgets\WidgetsElement::class,
    'Dotclear\Core\Backend\Filter\Filter' => Dotclear\Helper\Stack\Filter::class, // 2.33
]);

// Core and public behaviors
dcProxyV2::loadBehaviors('dcProxyV2CoreBehaviors', __DIR__ . '/inc/class.core.behaviors.php');  // Load core stuff
dcProxyV2::loadBehaviors('dcProxyV2PublicBehaviors', __DIR__ . '/inc/class.public.behaviors.php');  // Load public stuff

// Core proxy classes
Clearbricks::lib()->autoload([
    'dcAntispam'   => __DIR__ . '/inc/antispam.php',
    'dcSpamFilter' => __DIR__ . '/inc/antispam.php',
]);

if (!App::task()->checkContext('BACKEND')) {
    return false;
}

// Admin behaviors
dcProxyV2::loadBehaviors('dcProxyV2AdminBehaviors', __DIR__ . '/inc/class.admin.behaviors.php');  // Load admin stuff

// Admin proxy classes
Clearbricks::lib()->autoload([
    'adminGenericFilter' => __DIR__ . '/inc/lib.adminfilters.php',
    'adminGenericList'   => __DIR__ . '/inc/lib.pager.php',
    'dcBlogroll'         => __DIR__ . '/inc/blogroll.php',
    'dcMaintenance'      => __DIR__ . '/inc/maintenance.php',
    'dcMaintenanceTask'  => __DIR__ . '/inc/maintenance.php',
    'flatImport'         => __DIR__ . '/inc/class.flat.import.php',
    'flatImportV2'       => __DIR__ . '/inc/class.flat.import.php',

    'dcActionsPage'         => __DIR__ . '/inc/class.dcaction.php',
    'dcPostsActionsPage'    => __DIR__ . '/inc/class.dcaction.php',
    'dcCommentsActionsPage' => __DIR__ . '/inc/class.dcaction.php',
    'dcBlogsActionsPage'    => __DIR__ . '/inc/class.dcaction.php',
    'dcPagesActions'        => __DIR__ . '/inc/class.dcaction.php',
    'dcPagesActionsPage'    => __DIR__ . '/inc/class.dcaction.php',
]);

// Deprecated functions (outside classes)
require_once __DIR__ . '/inc/lib.helper.php';
