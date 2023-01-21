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
 * @version 1.4
 */
define('CLEARBRICKS_VERSION', '1.4');

// Autoload for clearbricks
class Autoload
{
    public $stack = [];

    public function __construct()
    {
        spl_autoload_register([$this, 'loadClass']);

        /*
         * @deprecated since 1.3, use Clearbricks::lib()->autoload() instead
         */
        $GLOBALS['__autoload'] = &$this->stack;
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
     * Get the source file of a registered class
     *
     * @param      string  $class  The class
     *
     * @return     mixed   source file of class, false is not set
     */
    public function source(string $class)
    {
        if (isset($this->stack[$class])) {
            return $this->stack[$class];
        }

        return false;
    }
}

class Clearbricks
{
    private static ?self $instance = null;

    /**
     * @var Autoload instance
     */
    private \Autoload $autoloader;

    public function __construct()
    {
        // Singleton mode
        if (self::$instance) {
            throw new Exception('Library can not be loaded twice.', 500);
        }
        self::$instance = $this;

        $this->autoloader = new Autoload();

        $this->autoloader->add([
            // Common helpers
            'Autoloader'       => __DIR__ . '/common/Autoloader.php',
            'crypt'            => __DIR__ . '/common/lib.crypt.php',
            'dt'               => __DIR__ . '/common/lib.date.php',
            'files'            => __DIR__ . '/common/lib.files.php',
            'path'             => __DIR__ . '/common/lib.files.php',
            'form'             => __DIR__ . '/common/lib.form.php',
            'formSelectOption' => __DIR__ . '/common/lib.form.php',
            'html'             => __DIR__ . '/common/lib.html.php',
            'http'             => __DIR__ . '/common/lib.http.php',
            'l10n'             => __DIR__ . '/common/lib.l10n.php',
            'text'             => __DIR__ . '/common/lib.text.php',

            // Database Abstraction Layer
            'dbLayer'  => __DIR__ . '/dblayer/dblayer.php',
            'dbStruct' => __DIR__ . '/dbschema/class.dbstruct.php',
            'dbSchema' => __DIR__ . '/dbschema/class.dbschema.php',

            // Files Manager
            'filemanager' => __DIR__ . '/filemanager/class.filemanager.php',
            'fileItem'    => __DIR__ . '/filemanager/class.filemanager.php',

            // Feed Reader
            'feedParser' => __DIR__ . '/net.http.feed/class.feed.parser.php',
            'feedReader' => __DIR__ . '/net.http.feed/class.feed.reader.php',

            // HTML Filter
            'htmlFilter' => __DIR__ . '/html.filter/class.html.filter.php',

            // HTML Validator
            'htmlValidator' => __DIR__ . '/html.validator/class.html.validator.php',

            // Image Manipulation Tools
            'imageMeta'  => __DIR__ . '/image/class.image.meta.php',
            'imageTools' => __DIR__ . '/image/class.image.tools.php',

            // Send Mail Utilities
            'mail' => __DIR__ . '/mail/class.mail.php',

            // Send Mail Through Sockets
            'socketMail' => __DIR__ . '/mail/class.socket.mail.php',

            // HTML Pager
            'pager' => __DIR__ . '/pager/class.pager.php',

            // REST Server
            'restServer' => __DIR__ . '/rest/class.rest.php',
            'xmlTag'     => __DIR__ . '/rest/class.rest.php',

            // Database PHP Session
            'sessionDB' => __DIR__ . '/session.db/class.session.db.php',

            // Simple Template Systeme
            'template'               => __DIR__ . '/template/class.template.php',
            'tplNode'                => __DIR__ . '/template/class.tplnode.php',
            'tplNodeBlock'           => __DIR__ . '/template/class.tplnodeblock.php',
            'tplNodeText'            => __DIR__ . '/template/class.tplnodetext.php',
            'tplNodeValue'           => __DIR__ . '/template/class.tplnodevalue.php',
            'tplNodeBlockDefinition' => __DIR__ . '/template/class.tplnodeblockdef.php',
            'tplNodeValueParent'     => __DIR__ . '/template/class.tplnodevalueparent.php',

            // URL Handler
            'urlHandler' => __DIR__ . '/url.handler/class.url.handler.php',

            // Wiki to XHTML Converter
            'wiki2xhtml' => __DIR__ . '/text.wiki2xhtml/class.wiki2xhtml.php',

            // Common Socket Class
            'netSocket' => __DIR__ . '/net/class.net.socket.php',

            // HTTP Client
            'netHttp'    => __DIR__ . '/net.http/class.net.http.php',
            'HttpClient' => __DIR__ . '/net.http/class.net.http.php',

            // XML-RPC Client and Server
            'xmlrpcValue'               => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcMessage'             => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcRequest'             => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcDate'                => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcBase64'              => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcClient'              => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcClientMulticall'     => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcBasicServer'         => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',
            'xmlrpcIntrospectionServer' => __DIR__ . '/net.xmlrpc/class.net.xmlrpc.php',

            // Zip tools
            'fileUnzip' => __DIR__ . '/zip/class.unzip.php',
            'fileZip'   => __DIR__ . '/zip/class.zip.php',

            // Diff tools
            'diff'     => __DIR__ . '/diff/lib.diff.php',
            'tidyDiff' => __DIR__ . '/diff/lib.tidy.diff.php',

            // HTML Form helpers
            'formComponent' => __DIR__ . '/html.form/class.form.component.php',
            'formForm'      => __DIR__ . '/html.form/class.form.form.php',
            'formTextarea'  => __DIR__ . '/html.form/class.form.textarea.php',
            'formInput'     => __DIR__ . '/html.form/class.form.input.php',
            'formButton'    => __DIR__ . '/html.form/class.form.button.php',
            'formCheckbox'  => __DIR__ . '/html.form/class.form.checkbox.php',
            'formColor'     => __DIR__ . '/html.form/class.form.color.php',
            'formDate'      => __DIR__ . '/html.form/class.form.date.php',
            'formDatetime'  => __DIR__ . '/html.form/class.form.datetime.php',
            'formEmail'     => __DIR__ . '/html.form/class.form.email.php',
            'formFile'      => __DIR__ . '/html.form/class.form.file.php',
            'formHidden'    => __DIR__ . '/html.form/class.form.hidden.php',
            'formNumber'    => __DIR__ . '/html.form/class.form.number.php',
            'formPassword'  => __DIR__ . '/html.form/class.form.password.php',
            'formRadio'     => __DIR__ . '/html.form/class.form.radio.php',
            'formSubmit'    => __DIR__ . '/html.form/class.form.submit.php',
            'formTime'      => __DIR__ . '/html.form/class.form.time.php',
            'formUrl'       => __DIR__ . '/html.form/class.form.url.php',
            'formLabel'     => __DIR__ . '/html.form/class.form.label.php',
            'formFieldset'  => __DIR__ . '/html.form/class.form.fieldset.php',
            'formLegend'    => __DIR__ . '/html.form/class.form.legend.php',
            'formSelect'    => __DIR__ . '/html.form/class.form.select.php',
            'formOptgroup'  => __DIR__ . '/html.form/class.form.optgroup.php',
            'formOption'    => __DIR__ . '/html.form/class.form.option.php',
        ]);

        // We may need l10n __() function
        l10n::bootstrap();

        // We set default timezone to avoid warning
        dt::setTZ('UTC');
    }

    /**
     * Get Clearbricks singleton instance
     *
     * @return     Clearbricks
     */
    public static function lib(): Clearbricks
    {
        return self::$instance;
    }

    /**
     * Autoload: register class(es)
     *
     * @param      array  $stack  Array of class => file (strings)
     */
    public function autoload(array $stack)
    {
        $this->autoloader->add($stack);
    }

    /**
     * Return source file associated with a registered class
     *
     * @param      string  $class  The class
     *
     * @return     mixed   Source file or false
     */
    public function autoloadSource(string $class)
    {
        return $this->autoloader->source($class);
    }
}

// Create singleton
new Clearbricks();
