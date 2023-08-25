<?php
/**
 * @brief dcProxyV2, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\Clearbricks;

class dcProxyV2
{
    public const SUFFIX = 'V2';

    public static function loadBehaviors(string $class, string $file)
    {
        Clearbricks::lib()->autoload([$class => $file]);

        $reflectionCore = new ReflectionClass($class);
        foreach ($reflectionCore->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            dcCore::app()->behavior->addBehavior($method->name . self::SUFFIX, [$method->class, $method->name]);
        }
    }

    public static function classAliases(array $aliases)
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
    'dcPagesActions' => Dotclear\Plugin\pages\BackendActions::class,
    'defaultWidgets' => Dotclear\Plugin\widgets\Widgets::class,
    'dcWidgets'      => Dotclear\Plugin\widgets\WidgetsStack::class,
    'dcWidget'       => Dotclear\Plugin\widgets\WidgetsElement::class,
]);

// Core and public behaviors
dcProxyV2::loadBehaviors('dcProxyV2CoreBehaviors', __DIR__ . '/inc/class.core.behaviors.php');  // Load core stuff
dcProxyV2::loadBehaviors('dcProxyV2PublicBehaviors', __DIR__ . '/inc/class.public.behaviors.php');  // Load public stuff

// Core proxy classes
Clearbricks::lib()->autoload([
    'dcAntispam'   => __DIR__ . '/inc/antispam.php',
    'dcSpamFilter' => __DIR__ . '/inc/antispam.php',
]);

if (!defined('DC_CONTEXT_ADMIN')) {
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
