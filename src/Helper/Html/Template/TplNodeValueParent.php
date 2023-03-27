<?php
/**
 * @class TplNodeValueParent
 *
 * Value node, for all {{tpl:Tag}}
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

class TplNodeValueParent extends TplNodeValue
{
    /**
     * Compile node value parent
     *
     * @param  Template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(Template $tpl): string
    {
        // simply ask currently being displayed to display itself!
        return TplNodeBlockDefinition::renderParent($tpl);
    }
}
