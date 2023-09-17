<?php
/**
 * @file
 * @brief       The plugin dcProxyV2 plugin importExport aliases
 * @ingroup     dcProxyV2
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

/**
 * @deprecated since 2.25
 */
class flatImport extends flatImportV2
{
    /**
     * Constructs a new instance.
     */
    public function __construct(dcCore $core, $file)    // @phpstan-ignore-line
    {
        parent::__construct($file);
    }
}

/**
 * @deprecated since 2.25
 */
class flatImportV2 extends Dotclear\Plugin\importExport\FlatImportV2
{
}
