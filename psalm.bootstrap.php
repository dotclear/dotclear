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

    'dcAuth'         => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.auth.php']),
    'dcBlog'         => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.blog.php']),
    'dcCategories'   => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.categories.php']),
    'dcError'        => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.error.php']),
    'dcMeta'         => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.meta.php']),
    'dcMedia'        => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.media.php']),
    'dcPostMedia'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.postmedia.php']),
    'dcModuleDefine' => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.module.define.php']),
    'dcModules'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.modules.php']),
    'dcPlugins'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.plugins.php']),
    'dcThemes'       => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.themes.php']),
    'dcRestServer'   => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rest.php']),
    'dcNamespace'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.namespace.php']),
    'dcNotices'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.notices.php']),
    'dcSettings'     => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.settings.php']),
    'dcTrackback'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.trackback.php']),
    'dcUpdate'       => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.update.php']),
    'dcUtils'        => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.utils.php']),
    'dcXmlRpc'       => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.xmlrpc.php']),
    'dcDeprecated'   => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.deprecated.php']),
    'dcLog'          => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.log.php']),
    'rsExtLog'       => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.log.php']),
    'dcWorkspace'    => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.workspace.php']),
    'dcPrefs'        => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.prefs.php']),
    'dcStore'        => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.store.php']),
    'dcStoreReader'  => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.store.reader.php']),
    'dcStoreParser'  => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.store.parser.php']),
    'rsExtPost'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtComment'   => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtDates'     => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtUser'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),
    'rsExtBlog'      => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'core', 'class.dc.rs.extensions.php']),

    // Public
    'dcTemplate'         => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'public', 'class.dc.template.php']),
    'context'            => implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'public', 'lib.tpl.context.php']),
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
