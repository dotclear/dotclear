<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
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
     *
     * @var     bool    $use_render
     */
    protected $use_render = true;
}
