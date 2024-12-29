<?php

/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use Dotclear\App;
use Dotclear\Helper\Network\XmlRpc\IntrospectionServer;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * XmlRpc handler.
 */
class XmlRpc extends IntrospectionServer
{
    /**
     * Set to true as soon as Blog is set (using Blog ID)
     *
     * @var     bool
     */
    private bool $blog_loaded = false;

    /**
     * Debug mode
     *
     * @var     bool
     */
    private bool $debug = false;

    /**
     * Debug file log
     *
     * @var     string
     */
    private readonly string $debug_file;

    /**
     * Trace arguments
     *
     * @var     bool
     */
    private bool $trace_args = true;

    /**
     * Trace response
     *
     * @var     bool
     */
    private bool $trace_response = true;

    /**
     * Constructs a new instance.
     *
     * @param   string  $blog_id  The blog ID
     */
    public function __construct(
        private readonly string $blog_id
    ) {
        $this->debug_file = App::config()->cacheRoot() . '/dotclear-xmlrpc.log';

        parent::__construct();

        # Pingback support
        $this->addCallback(
            'pingback.ping',
            $this->pingback_ping(...),
            ['string', 'string', 'string'],
            'Notify a link to a post.'
        );
    }

    /**
     * Start the XML-RPC server.
     *
     * @param   bool    $data   The data
     */
    public function serve($data = false): void
    {
        parent::serve(false);
    }

    /**
     * Call a XML-RPC method.
     *
     * @param   string  $methodname     The methodname
     * @param   mixed   $args           The arguments
     *
     * @return  mixed
     */
    public function call(string $methodname, $args): mixed
    {
        try {
            $rsp = @parent::call($methodname, $args);
            $this->debugTrace($methodname, $args, $rsp);

            return $rsp;
        } catch (Exception $e) {
            $this->debugTrace($methodname, $args, [$e->getMessage(), $e->getCode()]);

            throw $e;
        }
    }

    /**
     * Trace method response.
     *
     * @param   string  $methodname     The methodname
     * @param   mixed   $args           The arguments
     * @param   mixed   $rsp            The response
     */
    private function debugTrace(string $methodname, $args, $rsp): void
    {
        if (!$this->debug) {
            return;
        }

        if (($fp = @fopen($this->debug_file, 'a')) !== false) {
            fwrite($fp, '[' . date('r') . ']' . ' ' . $methodname);

            if ($this->trace_args) {
                fwrite($fp, "\n- args ---\n" . var_export($args, true));
            }

            if ($this->trace_response) {
                fwrite($fp, "\n- response ---\n" . var_export($rsp, true));
            }
            fwrite($fp, "\n");
            fclose($fp);
        }
    }

    // Internal methods
    // ----------------

    /**
     * Sets the blog.
     *
     * @throws  Exception
     *
     * @return  bool
     */
    private function setBlog()
    {
        if (!$this->blog_id) {
            throw new Exception('No blog ID given.');
        }

        if ($this->blog_loaded) {
            return true;
        }

        App::blog()->loadFromBlog($this->blog_id);
        $this->blog_loaded = true;

        if (!App::blog()->id()) {
            App::blog()->loadFromBlog('');

            throw new Exception('Blog does not exist.');
        }

        foreach (App::plugins()->getDefines(['state' => ModuleDefine::STATE_ENABLED]) as $define) {
            App::plugins()->loadNsFile($define->getId(), 'xmlrpc');
        }

        return true;
    }

    // XML-RPC methods

    /**
     * Receive a pingback.
     *
     * @param   string  $from_url   The from url
     * @param   string  $to_url     To url
     *
     * @return  string  Message sent back to the sender
     */
    public function pingback_ping(string $from_url, string $to_url): string
    {
        App::trackback()::checkURLs($from_url, $to_url);

        $args = [
            'type'     => 'pingback',
            'from_url' => $from_url,
            'to_url'   => $to_url,
        ];

        // Time to get things done...
        $this->setBlog();

        # --BEHAVIOR-- publicBeforeReceiveTrackback -- array<string,string>
        App::behavior()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

        return App::trackback()->receivePingback($from_url, $to_url);
    }
}
