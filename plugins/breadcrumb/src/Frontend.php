<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\breadcrumb;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module frontend process.
 * @ingroup breadcrumb
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

        App::frontend()->tpl->addValue('Breadcrumb', FrontendTemplate::breadcrumb(...));

        return true;
    }
}
