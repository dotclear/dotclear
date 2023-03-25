<?php
/**
 * @package Clearbricks
 *
 * Tiny library including:
 * - Database abstraction layer (MySQL/MariadDB, postgreSQL and SQLite)
 * - File manager
 * - Feed reader
 * - HTML filter/validator
 * - Images manipulation tools
 * - Mail utilities
 * - HTML pager
 * - REST Server
 * - Database driven session handler
 * - Simple Template Systeme
 * - URL Handler
 * - Wiki to XHTML Converter
 * - HTTP/NNTP clients
 * - XML-RPC Client and Server
 * - Zip tools
 * - Diff tools
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 * @version 2.0
 */

namespace Dotclear\Helper;

use Exception;

class Clearbricks
{
    /**
     * Old way autoload classes stack
     *
     * @var        array
     */
    public $stack = [];

    /**
     * Instance singleton
     */
    private static ?self $instance = null;

    public function __construct()
    {
        // Singleton mode
        if (self::$instance) {
            throw new Exception('Library can not be loaded twice.', 500);
        }

        define('CLEARBRICKS_VERSION', '2.0');

        self::$instance = $this;

        spl_autoload_register([$this, 'loadClass']);

        // Load old CB classes
        $old_helper_root = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'inc', 'helper']);
        $this->add([
            // Common helpers
            'dt'               => $old_helper_root . '/common/lib.date.php',
            'form'             => $old_helper_root . '/common/lib.form.php',
            'formSelectOption' => $old_helper_root . '/common/lib.form.php',
            'http'             => $old_helper_root . '/common/lib.http.php',
            'l10n'             => $old_helper_root . '/common/lib.l10n.php',

            // Database Abstraction Layer
            'dbLayer'  => $old_helper_root . '/dblayer/dblayer.php',
            'dbStruct' => $old_helper_root . '/dbschema/class.dbstruct.php',
            'dbSchema' => $old_helper_root . '/dbschema/class.dbschema.php',

            // Files Manager
            'filemanager' => $old_helper_root . '/filemanager/class.filemanager.php',
            'fileItem'    => $old_helper_root . '/filemanager/class.filemanager.php',

            // Feed Reader
            'feedParser' => $old_helper_root . '/net.http.feed/class.feed.parser.php',
            'feedReader' => $old_helper_root . '/net.http.feed/class.feed.reader.php',

            // HTML Filter
            'htmlFilter' => $old_helper_root . '/html.filter/class.html.filter.php',

            // HTML Validator
            'htmlValidator' => $old_helper_root . '/html.validator/class.html.validator.php',

            // Image Manipulation Tools
            'imageMeta'  => $old_helper_root . '/image/class.image.meta.php',
            'imageTools' => $old_helper_root . '/image/class.image.tools.php',

            // Database PHP Session
            'sessionDB' => $old_helper_root . '/session.db/class.session.db.php',

            // Simple Template Systeme
            'template'               => $old_helper_root . '/template/class.template.php',
            'tplNode'                => $old_helper_root . '/template/class.tplnode.php',
            'tplNodeBlock'           => $old_helper_root . '/template/class.tplnodeblock.php',
            'tplNodeText'            => $old_helper_root . '/template/class.tplnodetext.php',
            'tplNodeValue'           => $old_helper_root . '/template/class.tplnodevalue.php',
            'tplNodeBlockDefinition' => $old_helper_root . '/template/class.tplnodeblockdef.php',
            'tplNodeValueParent'     => $old_helper_root . '/template/class.tplnodevalueparent.php',

            // URL Handler
            'urlHandler' => $old_helper_root . '/url.handler/class.url.handler.php',

            // Wiki to XHTML Converter
            'wiki2xhtml' => $old_helper_root . '/text.wiki2xhtml/class.wiki2xhtml.php',

            // Common Socket Class
            'netSocket' => $old_helper_root . '/net/class.net.socket.php',

            // HTTP Client
            'netHttp'    => $old_helper_root . '/net.http/class.net.http.php',
            'HttpClient' => $old_helper_root . '/net.http/class.net.http.php',

            // XML-RPC Client and Server
            'xmlrpcValue'               => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcMessage'             => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcRequest'             => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcDate'                => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcBase64'              => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcClient'              => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcClientMulticall'     => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcBasicServer'         => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcIntrospectionServer' => $old_helper_root . '/net.xmlrpc/class.net.xmlrpc.php',

            // Zip tools
            'fileUnzip' => $old_helper_root . '/zip/class.unzip.php',
            'fileZip'   => $old_helper_root . '/zip/class.zip.php',
        ]);

        // Helpers bootsrap
        self::init();
    }

    /**
     * Initializes the object.
     */
    public static function init(): void
    {
        // We may need l10n __() function
        \l10n::bootstrap();

        // We set default timezone to avoid warning
        \dt::setTZ('UTC');
    }

    /**
     * Get Clearbricks singleton instance
     *
     * @return     self
     *
     * @deprecated Since 2.26
     */
    public static function lib(): self
    {
        if (!self::$instance) {
            // Init singleton
            new self();
        }

        return self::$instance;
    }

    public function loadClass(string $name)
    {
        if (isset($this->stack[$name]) && is_file($this->stack[$name])) {
            require_once $this->stack[$name];
        }
    }

    /**
     * Add class(es) to autoloader stack
     *
     * @param      array  $stack  Array of class => file (strings)
     */
    public function add(array $stack)
    {
        if (is_array($stack)) {
            $this->stack = array_merge($this->stack, $stack);
        }
    }

    /**
     * Autoload: register class(es)
     * Exemaple: Clearbricks::lib()->autoload(['class' => 'classfullpath'])
     *
     * @param      array  $stack  Array of class => file (strings)
     *
     * @deprecated Since 2.26, use namespaces instead
     */
    public function autoload(array $stack)
    {
        $this->add($stack);
    }
}
