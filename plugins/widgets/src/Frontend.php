<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Widgets::init();

        Core::frontend()->tpl->addValue('Widgets', FrontendTemplate::tplWidgets(...));
        Core::frontend()->tpl->addBlock('Widget', FrontendTemplate::tplWidget(...));
        Core::frontend()->tpl->addBlock('IfWidgets', FrontendTemplate::tplIfWidgets(...));

        return true;
    }
}
