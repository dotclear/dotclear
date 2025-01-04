<?php

/**
 * @file
 * @brief       The plugin dcProxyV1 class aliases
 * @ingroup     dcProxyV1
 *
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

/**
 * @brief   The module class alias handler.
 * @ingroup dcProxyV1
 */
class dcProxyV1
{
    /**
     * Declare class alisases
     *
     * @param      array<string, string>  $aliases  The aliases
     */
    public static function classAliases(array $aliases): void
    {
        foreach ($aliases as $aliasName => $realName) {
            if (!class_exists($aliasName)) {
                class_alias($realName, $aliasName);
            }
        }
    }
}

// Classes aliases
dcProxyV1::classAliases([
    // alias → real name (including namespace if necessary, for both)

    // Deprecated since 2.26
    'Clearbricks' => \Dotclear\Helper\Clearbricks::class,

    // Form helpers
    'formButton'    => \Dotclear\Helper\Html\Form\Button::class,
    'formCheckbox'  => \Dotclear\Helper\Html\Form\Checkbox::class,
    'formColor'     => \Dotclear\Helper\Html\Form\Color::class,
    'formComponent' => \Dotclear\Helper\Html\Form\Component::class,
    'formDate'      => \Dotclear\Helper\Html\Form\Date::class,
    'formDatetime'  => \Dotclear\Helper\Html\Form\Datetime::class,
    'formDiv'       => \Dotclear\Helper\Html\Form\Div::class,
    'formEmail'     => \Dotclear\Helper\Html\Form\Email::class,
    'formFieldset'  => \Dotclear\Helper\Html\Form\Fieldset::class,
    'formFile'      => \Dotclear\Helper\Html\Form\File::class,
    'formForm'      => \Dotclear\Helper\Html\Form\Form::class,
    'formHidden'    => \Dotclear\Helper\Html\Form\Hidden::class,
    'formInput'     => \Dotclear\Helper\Html\Form\Input::class,
    'formLabel'     => \Dotclear\Helper\Html\Form\Label::class,
    'formLegend'    => \Dotclear\Helper\Html\Form\Legend::class,
    'formLink'      => \Dotclear\Helper\Html\Form\Link::class,
    'formNote'      => \Dotclear\Helper\Html\Form\Note::class,
    'formNumber'    => \Dotclear\Helper\Html\Form\Number::class,
    'formOptgroup'  => \Dotclear\Helper\Html\Form\Optgroup::class,
    'formOption'    => \Dotclear\Helper\Html\Form\Option::class,
    'formPara'      => \Dotclear\Helper\Html\Form\Para::class,
    'formPassword'  => \Dotclear\Helper\Html\Form\Password::class,
    'formRadio'     => \Dotclear\Helper\Html\Form\Radio::class,
    'formSelect'    => \Dotclear\Helper\Html\Form\Select::class,
    'formSubmit'    => \Dotclear\Helper\Html\Form\Submit::class,
    'formText'      => \Dotclear\Helper\Html\Form\Text::class,
    'formTextarea'  => \Dotclear\Helper\Html\Form\Textarea::class,
    'formTime'      => \Dotclear\Helper\Html\Form\Time::class,
    'formUrl'       => \Dotclear\Helper\Html\Form\Url::class,

    // Diff helpers
    'diff'          => \Dotclear\Helper\Diff\Diff::class,
    'tidyDiff'      => \Dotclear\Helper\Diff\TidyDiff::class,
    'tidyDiffChunk' => \Dotclear\Helper\Diff\TidyDiffChunk::class,
    'tidyDiffLine'  => \Dotclear\Helper\Diff\TidyDiffLine::class,

    // Crypt helpers
    'crypt' => \Dotclear\Helper\Crypt::class,

    // Mail helpers
    'mail'       => \Dotclear\Helper\Network\Mail\Mail::class,
    'socketMail' => \Dotclear\Helper\Network\Mail\MailSocket::class,

    // Pager helpers
    'pager' => \Dotclear\Helper\Html\Pager::class,

    // XmlTag helpers
    'xmlTag' => \Dotclear\Helper\Html\XmlTag::class,

    // Rest helpers
    'restServer' => \Dotclear\Helper\RestServer::class,

    // Text helpers
    'text' => \Dotclear\Helper\Text::class,

    // Files and Path, … helpers
    'files'       => \Dotclear\Helper\File\Files::class,
    'path'        => \Dotclear\Helper\File\Path::class,
    'filemanager' => \Dotclear\Helper\File\Manager::class,
    'fileItem'    => \Dotclear\Helper\File\File::class,

    // Html helpers
    'html'       => \Dotclear\Helper\Html\Html::class,
    'htmlFilter' => \Dotclear\Helper\Html\HtmlFilter::class,

    // Mail helpers
    'http' => \Dotclear\Helper\Network\Http::class,

    // Wiki helpers
    'wiki2xhtml' => \Dotclear\Helper\Html\WikiToHtml::class,

    // Simple Template Systeme
    'template'               => \Dotclear\Helper\Html\Template\Template::class,
    'tplNode'                => \Dotclear\Helper\Html\Template\TplNode::class,
    'tplNodeBlock'           => \Dotclear\Helper\Html\Template\TplNodeBlock::class,
    'tplNodeText'            => \Dotclear\Helper\Html\Template\TplNodeText::class,
    'tplNodeValue'           => \Dotclear\Helper\Html\Template\TplNodeValue::class,
    'tplNodeBlockDefinition' => \Dotclear\Helper\Html\Template\TplNodeBlockDefinition::class,
    'tplNodeValueParent'     => \Dotclear\Helper\Html\Template\TplNodeValueParent::class,

    // HTML Validation
    'htmlValidator' => \Dotclear\Helper\Html\HtmlValidator::class,

    // Socket
    'netSocket' => \Dotclear\Helper\Network\Socket\Socket::class,

    // L10n
    'l10n' => \Dotclear\Helper\L10n::class,

    // Image helpers
    'imageMeta'  => \Dotclear\Helper\File\Image\ImageMeta::class,
    'imageTools' => \Dotclear\Helper\File\Image\ImageTools::class,

    // URL Handler
    'urlHandler' => \Dotclear\Helper\Network\UrlHandler::class,

    // net HTTP Client
    'netHttp' => \Dotclear\Helper\Network\HttpClient::class,

    // XML-RPC helper
    'xmlrpcException'           => \Dotclear\Helper\Network\XmlRpc\XmlRpcException::class,
    'xmlrpcValue'               => \Dotclear\Helper\Network\XmlRpc\Value::class,
    'xmlrpcMessage'             => \Dotclear\Helper\Network\XmlRpc\Message::class,
    'xmlrpcRequest'             => \Dotclear\Helper\Network\XmlRpc\Request::class,
    'xmlrpcDate'                => \Dotclear\Helper\Network\XmlRpc\Date::class,
    'xmlrpcBase64'              => \Dotclear\Helper\Network\XmlRpc\Base64::class,
    'xmlrpcClient'              => \Dotclear\Helper\Network\XmlRpc\Client::class,
    'xmlrpcClientMulticall'     => \Dotclear\Helper\Network\XmlRpc\ClientMulticall::class,
    'xmlrpcBasicServer'         => \Dotclear\Helper\Network\XmlRpc\BasicServer::class,
    'xmlrpcIntrospectionServer' => \Dotclear\Helper\Network\XmlRpc\IntrospectionServer::class,

    // Feed Helpers
    'feedParser' => \Dotclear\Helper\Network\Feed\Parser::class,
    'feedReader' => \Dotclear\Helper\Network\Feed\Reader::class,

    // Date helpers
    'dt' => \Dotclear\Helper\Date::class,

    // Zip helpers
    'fileZip'   => \Dotclear\Helper\File\Zip\Zip::class,
    'fileUnzip' => \Dotclear\Helper\File\Zip\Unzip::class,

    // Database -------------------

    'dcSqlStatement'      => \Dotclear\Database\Statement\SqlStatement::class,
    'dcSelectStatement'   => \Dotclear\Database\Statement\SelectStatement::class,
    'dcJoinStatement'     => \Dotclear\Database\Statement\JoinStatement::class,
    'dcUpdateStatement'   => \Dotclear\Database\Statement\UpdateStatement::class,
    'dcInsertStatement'   => \Dotclear\Database\Statement\InsertStatement::class,
    'dcDeleteStatement'   => \Dotclear\Database\Statement\DeleteStatement::class,
    'dcTruncateStatement' => \Dotclear\Database\Statement\TruncateStatement::class,

    'sessionDB' => \Dotclear\Database\Session::class,

    'cursor'          => \Dotclear\Database\Cursor::class,
    'record'          => \Dotclear\Database\Record::class,
    'staticRecord'    => \Dotclear\Database\StaticRecord::class,
    'extStaticRecord' => \Dotclear\Database\StaticRecord::class,

    'i_dbLayer' => \Dotclear\Database\InterfaceHandler::class,
    'dbLayer'   => \Dotclear\Database\AbstractHandler::class,

    'i_dbSchema' => \Dotclear\Database\InterfaceSchema::class,
    'dbSchema'   => \Dotclear\Database\AbstractSchema::class,

    'dbStruct'      => \Dotclear\Database\Structure::class,
    'dbStructTable' => \Dotclear\Database\Table::class,

    // Core -----------------------

    'dcAuth'         => \Dotclear\Core\Auth::class,
    'dcBlog'         => \Dotclear\Core\Blog::class,
    'dcCategories'   => \Dotclear\Core\Categories::class,
    'dcDeprecated'   => \Dotclear\Core\Deprecated::class,
    'dcError'        => \Dotclear\Core\Error::class,
    'dcLog'          => \Dotclear\Core\Log::class,
    'dcMedia'        => \Dotclear\Core\Media::class,
    'dcMeta'         => \Dotclear\Core\Meta::class,
    'dcModuleDefine' => \Dotclear\Module\ModuleDefine::class,
    'dcModules'      => \Dotclear\Module\Modules::class,
    'dcNamespace'    => \Dotclear\Core\BlogWorkspace::class,
    'dcNotices'      => \Dotclear\Core\Notice::class,
    'dcNsProcess'    => \Dotclear\Core\Process::class,
    'dcPlugins'      => \Dotclear\Module\Plugins::class,
    'dcPostMedia'    => \Dotclear\Core\PostMedia::class,
    'dcPrefs'        => \Dotclear\Core\UserPreferences::class,
    'dcRecord'       => \Dotclear\Database\MetaRecord::class,
    'dcRestServer'   => \Dotclear\Core\Rest::class,
    'dcSettings'     => \Dotclear\Core\BlogSettings::class,
    'dcStore'        => \Dotclear\Module\Store::class,
    'dcStoreParser'  => \Dotclear\Module\StoreParser::class,
    'dcStoreReader'  => \Dotclear\Module\StoreReader::class,
    'dcThemes'       => \Dotclear\Module\Themes::class,
    'dcTrackback'    => \Dotclear\Core\Trackback::class,
    'dcUpdate'       => \Dotclear\Core\Upgrade\Update::class,
    'dcWorkspace'    => \Dotclear\Core\UserWorkspace::class,
    'dcXmlRpc'       => \Dotclear\Core\Frontend\XmlRpc::class,

    'rsExtPost'    => \Dotclear\Schema\Extension\Post::class,
    'rsExtComment' => \Dotclear\Schema\Extension\Comment::class,
    'rsExtDates'   => \Dotclear\Schema\Extension\Dates::class,
    'rsExtUser'    => \Dotclear\Schema\Extension\User::class,
    'rsExtBlog'    => \Dotclear\Schema\Extension\Blog::class,

    'dcTraitDynamicProperties' => \Dotclear\Helper\TraitDynamicProperties::class,

    // Core admin -----------------

    'adminBlogFilter'      => \Dotclear\Core\Backend\Filter\FilterBlogs::class,
    'adminBlogList'        => \Dotclear\Core\Backend\Listing\ListingBlogs::class,
    'adminCommentFilter'   => \Dotclear\Core\Backend\Filter\FilterComments::class,
    'adminCommentList'     => \Dotclear\Core\Backend\Listing\ListingComments::class,
    'adminGenericFilterV2' => \Dotclear\Core\Backend\Filter\Filters::class,
    'adminGenericListV2'   => \Dotclear\Core\Backend\Listing\Listing::class,
    'adminMediaFilter'     => \Dotclear\Core\Backend\Filter\FilterMedia::class,
    'adminMediaList'       => \Dotclear\Core\Backend\Listing\ListingMedia::class,
    'adminMediaPage'       => \Dotclear\Core\Backend\MediaPage::class,
    'adminModulesList'     => \Dotclear\Core\Backend\ModulesList::class,
    'adminPostFilter'      => \Dotclear\Core\Backend\Filter\FilterPosts::class,
    'adminPostList'        => \Dotclear\Core\Backend\Listing\ListingPosts::class,
    'adminPostMiniList'    => \Dotclear\Core\Backend\Listing\ListingPostsMini::class,
    'adminUserFilter'      => \Dotclear\Core\Backend\Filter\FilterUsers::class,
    'adminUserList'        => \Dotclear\Core\Backend\Listing\ListingUsers::class,
    'adminThemesList'      => \Dotclear\Core\Backend\ThemesList::class,
    'adminUserPref'        => \Dotclear\Core\Backend\UserPref::class,
    'dcActions'            => \Dotclear\Core\Backend\Action\Actions::class,
    'dcAdmin'              => \Dotclear\Core\Backend\Utility::class,
    'dcAdminBlogPref'      => \Dotclear\Core\Backend\BlogPref::class,
    'dcAdminCombos'        => \Dotclear\Core\Backend\Combos::class,
    'dcAdminFilter'        => \Dotclear\Helper\Stack\Filter::class, // 2.33
    'dcAdminFilters'       => \Dotclear\Core\Backend\Filter\FiltersLibrary::class,
    'dcAdminHelper'        => \Dotclear\Core\Backend\Helper::class,
    'dcAdminNotices'       => \Dotclear\Core\Backend\Notices::class,
    'dcAdminURL'           => \Dotclear\Core\Backend\Url::class,
    'dcBlogsActions'       => \Dotclear\Core\Backend\Action\ActionsBlogs::class,
    'dcCommentsActions'    => \Dotclear\Core\Backend\Action\ActionsComments::class,
    'dcPostsActions'       => \Dotclear\Core\Backend\Action\ActionsPosts::class,
    'dcFavorites'          => \Dotclear\Core\Backend\Favorites::class,
    'dcMenu'               => \Dotclear\Core\Backend\Menu::class,
    'dcPage'               => \Dotclear\Core\Backend\Page::class,
    'dcPager'              => \Dotclear\Core\Backend\Listing\Pager::class,
    'dcThemeConfig'        => \Dotclear\Core\Backend\ThemeConfig::class,

    // Core public ----------------

    'dcPublic'      => \Dotclear\Core\Frontend\Utility::class,
    'dcTemplate'    => \Dotclear\Core\Frontend\Tpl::class,
    'dcUrlHandlers' => \Dotclear\Core\Frontend\Url::class,
    'context'       => \Dotclear\Core\Frontend\Ctx::class,

    'rsExtPostPublic'    => \Dotclear\Schema\Extension\PostPublic::class,
    'rsExtCommentPublic' => \Dotclear\Schema\Extension\CommentPublic::class,

    // Upgrade --------------------

    'dcUpgrade' => \Dotclear\Core\Upgrade\Upgrade::class,
]);
