<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module frontend process.
 * @ingroup widgets
 */
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

        App::frontend()->tpl->addValue('Widgets', FrontendTemplate::tplWidgets(...));
        App::frontend()->tpl->addBlock('Widget', FrontendTemplate::tplWidget(...));
        App::frontend()->tpl->addBlock('IfWidgets', FrontendTemplate::tplIfWidgets(...));

        return true;
    }
}
