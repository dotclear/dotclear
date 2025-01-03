<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\Core\Backend\Action\ActionsPosts;

/**
 * @brief   The module backend tagss posts actions.
 * @ingroup tags
 */
class BackendActions extends ActionsPosts
{
    /**
     * Use render method.
     */
    protected bool $use_render = true;
}
