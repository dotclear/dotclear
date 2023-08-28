<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use ArrayObject;
use dcCore;
use Dotclear\Core\Core;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;

class FrontendBehaviors
{
    /**
     * Public init
     */
    public static function publicPrepend(): void
    {
        // Localized string we find in template
        __("This tag's comments Atom feed");
        __("This tag's entries Atom feed");
    }

    /**
     * Prepare tags metadata if necessary (Entries or Comments blocks)
     *
     * @param      string       $block  The block
     * @param      ArrayObject  $attr   The attribute
     *
     * @return     string
     */
    public static function templateBeforeBlock(string $block, ArrayObject $attr): string
    {
        if (($block == 'Entries' || $block == 'Comments') && isset($attr['tag'])) {
            return
            "<?php\n" .
            "if (!isset(\$params)) { \$params = []; }\n" .
            "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
            "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
            "\$params['from'] .= ', '.Core::con()->prefix().'meta META ';\n" .
            "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
            "\$params['sql'] .= \"AND META.meta_type = 'tag' \";\n" .
            "\$params['sql'] .= \"AND META.meta_id = '" . Core::con()->escape($attr['tag']) . "' \";\n" .
                "?>\n";
        } elseif (empty($attr['no_context']) && ($block == 'Entries' || $block == 'Comments')) {
            return
                '<?php if (Core::frontend()->ctx->exists("meta") && Core::frontend()->ctx->meta->rows() && (Core::frontend()->ctx->meta->meta_type == "tag")) { ' .
                "if (!isset(\$params)) { \$params = []; }\n" .
                "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
                "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
                "\$params['from'] .= ', '.Core::con()->prefix().'meta META ';\n" .
                "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
                "\$params['sql'] .= \"AND META.meta_type = 'tag' \";\n" .
                "\$params['sql'] .= \"AND META.meta_id = '\".Core::con()->escape(Core::frontend()->ctx->meta->meta_id).\"' \";\n" .
                "} ?>\n";
        }

        return '';
    }

    /**
     * Adds tags tpl path.
     */
    public static function addTplPath(): void
    {
        $tplset           = dcCore::app()->themes->moduleInfo(Core::blog()->settings->system->theme, 'tplset');
        $default_template = Path::real(My::path()) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;

        if (!empty($tplset) && is_dir($default_template . $tplset)) {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), $default_template . $tplset);
        } else {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), $default_template . DC_DEFAULT_TPLSET);
        }
    }
}
