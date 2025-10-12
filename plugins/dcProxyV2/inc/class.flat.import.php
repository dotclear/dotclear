<?php

/**
 * @file
 * @brief       The plugin dcProxyV2 plugin importExport aliases
 * @ingroup     dcProxyV2
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

/**
 * @deprecated since 2.25
 */
class flatImport extends flatImportV2
{
    /**
     * Constructs a new instance.
     */
    // @phpstan-ignore constructor.unusedParameter
    public function __construct(dcCore $core, string $file)
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
