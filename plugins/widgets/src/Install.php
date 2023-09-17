<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use Dotclear\Core\Process;
use Exception;

/**
 * @brief   The module install process.
 * @ingroup widgets
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (class_exists(__NAMESPACE__ . '\Widgets')) {
            Widgets::init();
        } else {
            throw new Exception(__('Unable to initialize default widgets.'));
        }

        $s = My::settings();
        if ($s->widgets_nav != null) {
            $s->put('widgets_nav', WidgetsStack::load($s->widgets_nav)->store());
        } else {
            $s->put('widgets_nav', '', 'string', 'Navigation widgets', false);
        }
        if ($s->widgets_extra != null) {
            $s->put('widgets_extra', WidgetsStack::load($s->widgets_extra)->store());
        } else {
            $s->put('widgets_extra', '', 'string', 'Extra widgets', false);
        }
        if ($s->widgets_custom != null) {
            $s->put('widgets_custom', WidgetsStack::load($s->widgets_custom)->store());
        } else {
            $s->put('widgets_custom', '', 'string', 'Custom widgets', false);
        }

        return true;
    }
}
