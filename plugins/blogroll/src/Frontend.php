<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

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

        Core::frontend()->tpl->addValue('Blogroll', FrontendTemplate::blogroll(...));
        Core::frontend()->tpl->addValue('BlogrollXbelLink', FrontendTemplate::blogrollXbelLink(...));

        Core::behavior()->addBehaviors([
            'initWidgets'        => Widgets::initWidgets(...),
            'initDefaultWidgets' => Widgets::initDefaultWidgets(...),
        ]);

        return true;
    }
}
