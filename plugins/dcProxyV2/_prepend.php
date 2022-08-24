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

// Load core stuff

$__autoload['dcProxyV2CoreBehaviors'] = __DIR__ . '/inc/class.core.behaviors.php';

$reflectionCore = new ReflectionClass('dcProxyV2CoreBehaviors');
foreach ($reflectionCore->getMethods(ReflectionMethod::IS_STATIC) as $method) {
    dcCore::app()->addBehavior($method->name . 'V2', [$method->class, $method->name]);
}

// Load public stuff

$__autoload['dcProxyV2PublicBehaviors'] = __DIR__ . '/inc/class.public.behaviors.php';

$reflectionPublic = new ReflectionClass('dcProxyV2PublicBehaviors');
foreach ($reflectionPublic->getMethods(ReflectionMethod::IS_STATIC) as $method) {
    dcCore::app()->addBehavior($method->name . 'V2', [$method->class, $method->name]);
}

if (!defined('DC_CONTEXT_ADMIN')) {
    return false;
}

// Load admin stuff

$__autoload['dcProxyV2AdminBehaviors'] = __DIR__ . '/inc/class.admin.behaviors.php';

$reflectionAdmin = new ReflectionClass('dcProxyV2AdminBehaviors');
foreach ($reflectionAdmin->getMethods(ReflectionMethod::IS_STATIC) as $method) {
    dcCore::app()->addBehavior($method->name . 'V2', [$method->class, $method->name]);
}
