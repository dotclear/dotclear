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
        global $__autoload;

        Clearbricks::lib()->autoload([$class => $file]);

        $reflectionCore = new ReflectionClass($class);
        foreach ($reflectionCore->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            dcCore::app()->addBehavior($method->name . self::SUFFIX, [$method->class, $method->name]);
        }
    }
}

dcProxyV2::loadBehaviors('dcProxyV2CoreBehaviors', __DIR__ . '/inc/class.core.behaviors.php');  // Load core stuff
dcProxyV2::loadBehaviors('dcProxyV2PublicBehaviors', __DIR__ . '/inc/class.public.behaviors.php');  // Load public stuff

if (!defined('DC_CONTEXT_ADMIN')) {
    return false;
}

dcProxyV2::loadBehaviors('dcProxyV2AdminBehaviors', __DIR__ . '/inc/class.admin.behaviors.php');  // Load admin stuff
