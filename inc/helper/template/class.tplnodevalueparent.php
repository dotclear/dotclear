<?php
/**
 * @class tplNodeValueParent
 * @brief Value node, for all {{tpl:Tag}}
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class tplNodeValueParent extends tplNodeValue
{
    /**
     * Compile node value parent
     *
     * @param  template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(template $tpl): string
    {
        // simply ask currently being displayed to display itself!
        return tplNodeBlockDefinition::renderParent($tpl);
    }
}
