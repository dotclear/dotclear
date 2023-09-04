<?php
/**
 * Psalm bootstrap
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

// Composer Autoloader

require_once __DIR__ . '/vendor/autoload.php';

// Dotclear Autoloader

require_once __DIR__ . '/src/Autoloader.php';

$autoloader = new Autoloader('', '', true);
$autoloader->addNamespace('Dotclear', implode(DIRECTORY_SEPARATOR, [__DIR__, 'src']));

// Clearbricks Autoloader (deprecated)

$__autoload = [
    // Traits
    'dcTraitDynamicProperties' => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'trait.dc.dynprop.php']),

    // Core
    'dcCore' => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.core.php']),

    'dcCategories'   => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.categories.php']),
    'dcNamespace'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.namespace.php']),
    'dcSettings'     => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.settings.php']),
    'dcTrackback'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.trackback.php']),
    'dcXmlRpc'       => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.xmlrpc.php']),
    'rsExtLog'       => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'lib.rs.ext.log.php']),
    'dcWorkspace'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.workspace.php']),
    'dcPrefs'        => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.prefs.php']),
    'rsExtPost'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtComment'   => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtDates'     => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtUser'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtBlog'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),

    // Public
    'rsExtendPublic'     => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'public', 'rs.extension.php']),
    'rsExtPostPublic'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'public', 'rs.extension.php']),
    'rsExtCommentPublic' => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'public', 'rs.extension.php']),

    // Moved to src
    'form'             => implode(DIRECTORY_SEPARATOR, [__DIR__, 'src', 'Helper', 'Html', 'Form', 'Legacy.php']),
    'formSelectOption' => implode(DIRECTORY_SEPARATOR, [__DIR__, 'src', 'Helper', 'Html', 'Form', 'Legacy.php']),
];
spl_autoload_register(function ($name) use ($__autoload) {if (isset($__autoload[$name])) { require_once $__autoload[$name]; }});

// Ensure L10n functions exist
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'src', 'Helper','L10n.php']);

/**
 * Local error handler
 *
 * @param      string  $summary  The summary
 * @param      string  $message  The message
 * @param      int     $code     The code
 */
function __error(string $summary, string $message, int $code = 0)
{
    # Error codes
    # 10 : no config file
    # 20 : database issue
    # 30 : blog is not defined
    # 40 : template files creation
    # 50 : no default theme
    # 60 : template processing error
    # 70 : blog is offline

    trigger_error($summary, E_USER_ERROR);
    exit;
}
