<?php
/**
 * @brief pings, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$_menu['Blog']->addItem(__('Pings'),
    $core->adminurl->get('admin.plugin.pings'),
    dcPage::getPF('pings/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.pings')) . '/', $_SERVER['REQUEST_URI']),
    $core->auth->isSuperAdmin());

$__autoload['pingsAdminBehaviors'] = dirname(__FILE__) . '/lib.pings.php';

$core->addBehavior('adminPostHeaders', array('pingsAdminBehaviors', 'pingJS'));
$core->addBehavior('adminPostFormItems', array('pingsAdminBehaviors', 'pingsFormItems'));
$core->addBehavior('adminAfterPostCreate', array('pingsAdminBehaviors', 'doPings'));
$core->addBehavior('adminAfterPostUpdate', array('pingsAdminBehaviors', 'doPings'));

$core->addBehavior('adminDashboardFavorites', 'pingDashboardFavorites');

function pingDashboardFavorites($core, $favs)
{
    $favs->register('pings', array(
        'title'      => __('Pings'),
        'url'        => $core->adminurl->get('admin.plugin.pings'),
        'small-icon' => dcPage::getPF('pings/icon.png'),
        'large-icon' => dcPage::getPF('pings/icon-big.png')
    ));
}

$core->addBehavior('adminPageHelpBlock', 'pingsPageHelpBlock');

function pingsPageHelpBlock($blocks)
{
    $found = false;
    foreach ($blocks as $block) {
        if ($block == 'core_post') {
            $found = true;
            break;
        }
    }
    if (!$found) {
        return;
    }
    $blocks[] = 'pings_post';
}
