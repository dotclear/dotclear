<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('initWidgets', array('simpleMenuWidgets', 'initWidgets'));

class simpleMenuWidgets
{
    public static function initWidgets($w)
    {
        $w->create('simplemenu', __('Simple menu'), array('tplSimpleMenu', 'simpleMenuWidget'), null, 'List of simple menu items');
        $w->simplemenu->setting('title', __('Title (optional)') . ' :', __('Menu'));
        $w->simplemenu->setting('description', __('Item description'), 0, 'combo',
            array(
                __('Displayed in link')                   => 0, // span
                __('Used as link title')                  => 1, // title
                __('Displayed in link and used as title') => 2, // both
                __('Not displayed nor used')              => 3 // none
            )
        );
        $w->simplemenu->setting('homeonly', __('Display on:'), 0, 'combo',
            array(
                __('All pages')           => 0,
                __('Home page only')      => 1,
                __('Except on home page') => 2
            )
        );
        $w->simplemenu->setting('content_only', __('Content only'), 0, 'check');
        $w->simplemenu->setting('class', __('CSS class:'), '');
        $w->simplemenu->setting('offline', __('Offline'), 0, 'check');
    }
}
