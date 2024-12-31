<?php

/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Extension;

use Dotclear\App;
use Dotclear\Core\Frontend\Ctx;
use Dotclear\Database\MetaRecord;

/**
 * @brief Dotclear comment record helpers
 *
 * This class adds new methods to database post results.
 * You can call them on every record comming from Blog::getComments and similar
 * methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class CommentPublic extends Comment
{
    /**
     * Gets the comment's content.
     *
     * Replace textual smilies by their image representation if requested
     *
     * @param      MetaRecord   $rs             Invisible parameter
     * @param      bool|int     $absolute_urls  Use absolute urls
     *
     * @return     string  The content.
     */
    public static function getContent(MetaRecord $rs, $absolute_urls = false): string
    {
        if (App::blog()->settings()->system->use_smilies) {
            $content = parent::getContent($rs, $absolute_urls);

            if (App::frontend()->smilies === null) {
                App::frontend()->smilies = Ctx::getSmilies(App::blog());
            }

            return Ctx::addSmilies($content);
        }

        return parent::getContent($rs, $absolute_urls);
    }
}
