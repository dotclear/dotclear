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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcProxyV2
{
    public const SUFFIX = 'V2';

    public static function loadBehaviors(string $class, string $file)
    {
        Clearbricks::lib()->autoload([$class => $file]);

        $reflectionCore = new ReflectionClass($class);
        foreach ($reflectionCore->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            dcCore::app()->addBehavior($method->name . self::SUFFIX, [$method->class, $method->name]);
        }
    }
}

// Core and public behaviors
dcProxyV2::loadBehaviors('dcProxyV2CoreBehaviors', __DIR__ . '/inc/class.core.behaviors.php');  // Load core stuff
dcProxyV2::loadBehaviors('dcProxyV2PublicBehaviors', __DIR__ . '/inc/class.public.behaviors.php');  // Load public stuff

if (!defined('DC_CONTEXT_ADMIN')) {
    return false;
}

// Admin behaviors
dcProxyV2::loadBehaviors('dcProxyV2AdminBehaviors', __DIR__ . '/inc/class.admin.behaviors.php');  // Load admin stuff

// Admin proxy classes
Clearbricks::lib()->autoload([
    'adminGenericFilter' => __DIR__ . '/inc/lib.adminfilters.php',
    'adminGenericList'   => __DIR__ . '/inc/lib.pager.php',
    'flatImport'         => __DIR__ . '/inc/class.flat.import.php',
    'flatImportV2'       => __DIR__ . '/inc/class.flat.import.php',

    'dcActionsPage'         => __DIR__ . '/inc/class.dcaction.php',
    'dcPostsActionsPage'    => __DIR__ . '/inc/class.dcaction.php',
    'dcCommentsActionsPage' => __DIR__ . '/inc/class.dcaction.php',
    'dcBlogsActionsPage'    => __DIR__ . '/inc/class.dcaction.php',
    'dcPagesActionsPage'    => __DIR__ . '/inc/class.dcaction.php',
]);

// Deprecated functions (outside classes)
require_once __DIR__ . '/inc/lib.helper.php';
