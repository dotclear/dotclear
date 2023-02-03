<?php
/**
 * @brief Berlin, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Theme\berlin;

use dcAuth;
use dcCore;
use dcModuleDefine;

class Define extends dcModuleDefine
{
    protected function init(): void
    {
        $this->name        = 'Berlin';
        $this->desc        = 'Dotclear 2.7+ default theme';
        $this->author      = 'Dotclear Team';
        $this->version     = '2.0';
        $this->type        = 'theme';
        $this->tplset      = 'dotty';
    }
}