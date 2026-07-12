<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper;

use Dotclear\Helper\Html\XmlTag;
use Dotclear\Interface\Core\RestInterface;
use Exception;

/**
 * @class RestServer
 *
 * A very simple REST server implementation
 */
class RestServer implements RestInterface
{
    /**
     * Response: XML.
     */
    public XmlTag $rsp;

    /**
     * Response: JSON.
     *
     * @var     array<string, mixed>  $json
     */
    public ?array $json = null;

    /**
     * Server's functions.
     *
     * @var     array<string, callable>   $functions
     */
    public array $functions = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rsp = new XmlTag('rsp');
    }

    /**
     * Add Function.
     *
     * This adds a new function to the server. <var>$callback</var> should be a valid PHP callback.
     *
     * Callback function takes two or three arguments:
     * * supplemental parameter (if not null)
     * * GET values
     * * POST values
     *
     * @param   string      $name     Function name
     * @param   callable    $callback   Callback function
     */
    public function addFunction(string $name, callable $callback): void
    {
        $this->functions[$name] = $callback;
    }

    /**
     * Call Function.
     *
     * This method calls callback named <var>$name</var>.
     *
     * @param   string         $name   Function name
     * @param   array<mixed>   $get    GET values
     * @param   array<mixed>   $post   POST values
     * @param   mixed          $param  Supplemental parameter
     *
     * @return  mixed
     */
    protected function callFunction(string $name, array $get, array $post, $param = null)
    {
        if (isset($this->functions[$name])) {
            if ($param !== null) {
                return call_user_func($this->functions[$name], $param, $get, $post);
            }

            return call_user_func($this->functions[$name], $get, $post);
        }

        return null;
    }

    /**
     * Main server
     *
     * This method creates the main server.
     *
     * @param   string  $encoding   Server charset
     * @param   int     $format     Response format
     * @param   mixed   $param  Supplemental parameter
     */
    public function serve(string $encoding = 'UTF-8', int $format = self::DEFAULT_RESPONSE, $param = null): bool
    {
        if (!in_array($format, [self::XML_RESPONSE, self::JSON_RESPONSE], true)) {
            $format = self::DEFAULT_RESPONSE;
        }

        $get  = $_GET;
        $post = $_POST;

        if ($format === self::XML_RESPONSE) {
            if (!isset($_REQUEST['f'])) {
                $this->rsp->status = 'failed';
                $this->rsp->message('No function given');
                $this->getXML($encoding);

                return false;
            }

            $function = is_string($function = $_REQUEST['f']) ? $function : '';
            if ($function === '') {
                $this->rsp->status = 'failed';
                $this->rsp->message('No function given');
                $this->getXML($encoding);

                return false;
            }

            if (!isset($this->functions[$function])) {
                $this->rsp->status = 'failed';
                $this->rsp->message('Function does not exist');
                $this->getXML($encoding);

                return false;
            }

            try {
                $res = $this->callFunction($function, $get, $post, $param);
            } catch (Exception $e) {
                $this->rsp->status = 'failed';
                $this->rsp->message($e->getMessage());
                $this->getXML($encoding);

                return false;
            }

            // Check new node value
            $node = $res instanceof XmlTag || is_array($res) || is_null($res) || is_scalar($res) ? $res : null;
            if (is_array($node)) {
                // Ensure array keys are strings
                $list = [];
                foreach ($node as $key => $value) {
                    $list[(string) $key] = $value;
                }
                $node = $list;
            }

            $this->rsp->status = 'ok';
            $this->rsp->insertNode($node);
            $this->getXML($encoding);

            return true;
        }

        // JSON_RESPONSE :

        if (!isset($_REQUEST['f'])) {
            $this->json = [
                'success' => false,
                'message' => 'No function given',
            ];
            $this->getJSON($encoding);

            return false;
        }

        $function = is_string($function = $_REQUEST['f']) ? $function : '';
        if ($function === '') {
            $this->json = [
                'success' => false,
                'message' => 'No function given',
            ];
            $this->getJSON($encoding);

            return false;
        }

        if (!isset($this->functions[$function])) {
            $this->json = [
                'success' => false,
                'message' => 'Function does not exist',
            ];
            $this->getJSON($encoding);

            return false;
        }

        try {
            $res = $this->callFunction($function, $get, $post, $param);
        } catch (Exception $exception) {
            $this->json = [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
            $this->getJSON($encoding);

            return false;
        }

        $this->json = [
            'success' => true,
            'payload' => $res,
        ];
        $this->getJSON($encoding);

        return true;
    }

    /**
     * Stream the XML data (header and body).
     *
     * @param   string   $encoding  The encoding
     */
    private function getXML(string $encoding = 'UTF-8'): void
    {
        header('Content-Type: text/xml; charset=' . $encoding);
        echo $this->rsp->toXML(true, $encoding);
    }

    /**
     * Stream the JSON data (header and body).
     *
     * @param   string  $encoding   The encoding
     */
    private function getJSON(string $encoding = 'UTF-8'): void
    {
        header('Content-Type: application/json; charset=' . $encoding);
        echo json_encode($this->json, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Serve or not the REST requests.
     *
     * @param   bool    $serve  The flag
     */
    public function enableRestServer(bool $serve = true): void
    {
    }

    /**
     * Check if we need to serve REST requests.
     */
    public function serveRestRequests(): bool
    {
        return true;
    }
}
