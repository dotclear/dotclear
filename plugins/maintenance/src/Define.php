<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use dcAuth;
use dcCore;
use dcModuleDefine;

class Define extends dcModuleDefine
{
    protected function init(): void
    {
        $this->name        = 'Maintenance';
        $this->desc        = 'Maintain your installation';
        $this->author      = 'Olivier Meunier & Association Dotclear';
        $this->version     = '2.0';
        $this->permissions = dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]);
        $this->type        = 'plugin';
        $this->settings    = [
            'self' => '#settings',
        ];
    }
}
