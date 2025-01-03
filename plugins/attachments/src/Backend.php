<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup attachments
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('attachments');
        __('Manage post attachments');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminPostFormItems' => BackendBehaviors::adminPostFormItems(...),
            'adminPostAfterForm' => BackendBehaviors::adminPostAfterForm(...),
            'adminPostHeaders'   => fn (): string => My::jsLoad('post'),
            'adminPageFormItems' => BackendBehaviors::adminPostFormItems(...),
            'adminPageAfterForm' => BackendBehaviors::adminPostAfterForm(...),
            'adminPageHeaders'   => fn (): string => My::jsLoad('post'),
            'adminPageHelpBlock' => BackendBehaviors::adminPageHelpBlock(...),
        ]);

        return true;
    }
}
