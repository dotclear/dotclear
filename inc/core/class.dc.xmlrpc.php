<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Core\Core;
use Dotclear\Helper\Network\XmlRpc\IntrospectionServer;

class dcXmlRpc extends IntrospectionServer
{
    /**
     * Blog ID
     *
     * @var string
     */
    private string $blog_id;

    /**
     * Set to true as soon as Blog is set (using Blog ID)
     *
     * @var        bool
     */
    private bool $blog_loaded = false;

    /**
     * Debug mode
     *
     * @var        bool
     */
    private bool $debug = false;

    /**
     * Debug file log
     *
     * @var        string
     */
    private string $debug_file = DC_TPL_CACHE . '/dotclear-xmlrpc.log';

    /**
     * Trace arguments
     *
     * @var        bool
     */
    private bool $trace_args = true;

    /**
     * Trace response
     *
     * @var        bool
     */
    private bool $trace_response = true;

    /**
     * Constructs a new instance.
     *
     * @param      string  $blog_id  The blog ID
     */
    public function __construct(string $blog_id)
    {
        parent::__construct();

        $this->blog_id = $blog_id;

        # Pingback support
        $this->addCallback(
            'pingback.ping',
            [$this, 'pingback_ping'],
            ['string', 'string', 'string'],
            'Notify a link to a post.'
        );
    }

    /**
     * Start the XML-RPC server
     *
     * @param      bool  $data   The data
     */
    public function serve($data = false): void
    {
        parent::serve(false);
    }

    /**
     * Call a XML-RPC method
     *
     * @param      string  $methodname  The methodname
     * @param      mixed   $args        The arguments
     *
     * @return     mixed
     */
    public function call(string $methodname, $args)
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
     * Trace method response
     *
     * @param      string  $methodname  The methodname
     * @param      mixed   $args        The arguments
     * @param      mixed   $rsp         The response
     */
    private function debugTrace(string $methodname, $args, $rsp)
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
     * @throws     Exception
     *
     * @return     bool
     */
    private function setBlog()
    {
        if (!$this->blog_id) {
            throw new Exception('No blog ID given.');
        }

        if ($this->blog_loaded) {
            return true;
        }

        Core::setBlog($this->blog_id);
        $this->blog_loaded = true;

        if (!Core::blog()->id) {
            Core::blog() = null;

            throw new Exception('Blog does not exist.');
        }

        foreach (Core::plugins()->getDefines(['state' => dcModuleDefine::STATE_ENABLED]) as $define) {
            Core::plugins()->loadNsFile($define->getId(), 'xmlrpc');
        }

        return true;
    }

    // XML-RPC methods

    /**
     * Receive a pingback
     *
     * @param      string  $from_url  The from url
     * @param      string  $to_url    To url
     *
     * @return     string  Message sent back to the sender
     */
    public function pingback_ping(string $from_url, string $to_url): string
    {
        dcTrackback::checkURLs($from_url, $to_url);

        $args = [
            'type'     => 'pingback',
            'from_url' => $from_url,
            'to_url'   => $to_url,
        ];

        // Time to get things done...
        $this->setBlog();

        # --BEHAVIOR-- publicBeforeReceiveTrackback -- array<string,string>
        Core::behavior()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

        return (new dcTrackback())->receivePingback($from_url, $to_url);
    }
}
