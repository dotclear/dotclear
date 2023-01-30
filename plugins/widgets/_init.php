<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
dcCore::app()->autoload->addNamespace('Dotclear\Plugin\widgets', __DIR__ . DIRECTORY_SEPARATOR . 'src');

/*
 * @deprecated since 2.25
 *
 * use Dotclear\Plugin\widgets\Widgets [as defaultWidgets]
 * use Dotclear\Plugin\widgets\WidgetsStack [as dcWidgets]
 * use Dotclear\Plugin\widgets\WidgetsElement [as dcWidget]
 */
class_alias(Dotclear\Plugin\widgets\Widgets::class, 'defaultWidgets');
class_alias(Dotclear\Plugin\widgets\WidgetsStack::class, 'dcWidgets');
class_alias(Dotclear\Plugin\widgets\WidgetsElement::class, 'dcWidget');
