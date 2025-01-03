<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\Core\Backend\Action\ActionsComments;

/**
 * @brief   The module backend comments actions.
 * @ingroup pages
 */
class BackendActionsComments extends ActionsComments
{
    /**
     * Use render method.
     */
    protected bool $use_render = true;
}
