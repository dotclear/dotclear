<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcXmlRpc extends xmlrpcIntrospectionServer
{
    private $blog_id;
    private $blog_loaded    = false;
    private $debug          = false;
    private $debug_file     = '/tmp/dotclear-xmlrpc.log';
    private $trace_args     = true;
    private $trace_response = true;

    public function __construct($blog_id)
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

    public function serve($data = false): void
    {
        parent::serve(false);
    }

    public function call($methodname, $args)
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

    private function debugTrace($methodname, $args, $rsp)
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

    /* Internal methods
    --------------------------------------------------- */
    private function setBlog()
    {
        if (!$this->blog_id) {
            throw new Exception('No blog ID given.');
        }

        if ($this->blog_loaded) {
            return true;
        }

        dcCore::app()->setBlog($this->blog_id);
        $this->blog_loaded = true;

        if (!dcCore::app()->blog->id) {
            dcCore::app()->blog = null;

            throw new Exception('Blog does not exist.');
        }

        foreach (array_keys(dcCore::app()->plugins->getModules()) as $id) {
            dcCore::app()->plugins->loadNsFile($id, 'xmlrpc');
        }

        return true;
    }

    /* Pingback support
    --------------------------------------------------- */

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

        # --BEHAVIOR-- publicBeforeReceiveTrackback
        dcCore::app()->callBehavior('publicBeforeReceiveTrackbackV2', $args);

        $tb = new dcTrackback(dcCore::app());

        return $tb->receivePingback($from_url, $to_url);
    }
}
