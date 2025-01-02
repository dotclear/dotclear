<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

/**
 * @class TplNodeValueParent
 *
 * Value node, for all {{tpl:Tag}}
 */
class TplNodeValueParent extends TplNodeValue
{
    /**
     * Compile node value parent
     *
     * @param  Template     $tpl    The current template engine instance
     */
    public function compile(Template $tpl): string
    {
        // simply ask currently being displayed to display itself!
        return TplNodeBlockDefinition::renderParent($tpl);
    }
}
