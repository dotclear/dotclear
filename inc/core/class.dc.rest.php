<?php
/**
 * @brief Dotclear REST server extension
 *
 * This class extends restServer to handle dcCore instance in each rest method call.
 * Instance of this class is provided by dcCore $rest.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcRestServer extends restServer
{
    /**
     * @deprecated since 2.23
     */
    public $core; ///< dcCore instance
    /**
     * Payload (JSON)
     */
    public $json;

    /**
     * Constructs a new instance.
     *
     * @param      dcCore  $core   The core
     */
    public function __construct(dcCore $core = null)
    {
        parent::__construct();

        $this->json = null;

        $this->core = dcCore::app();
    }

    /**
     * Rest method call.
     *
     * @param      string  $name   The method name
     * @param      array   $get    The GET parameters copy
     * @param      array   $post   The POST parameters copy
     *
     * @return     mixed    Rest method result
     */
    protected function callFunction($name, $get, $post)
    {
        if (isset($this->functions[$name])) {
            return call_user_func($this->functions[$name], dcCore::app(), $get, $post);
        }
    }

    /**
     * Main server
     *
     * This method creates the main server.
     *
     * @param string    $encoding        Server charset
     */
    public function serve($encoding = 'UTF-8')
    {
        if (isset($_REQUEST['json'])) {
            if (!isset($_REQUEST['f'])) {
                $this->json = [
                    'success' => false,
                    'message' => 'No function given',
                ];
                $this->getJSON($encoding);

                return false;
            }

            if (!isset($this->functions[$_REQUEST['f']])) {
                $this->json = [
                    'success' => false,
                    'message' => 'Function does not exist',
                ];
                $this->getJSON($encoding);

                return false;
            }

            try {
                $get  = $_GET ?: [];
                $post = $_POST ?: [];

                $res = $this->callFunction($_REQUEST['f'], $get, $post);
            } catch (Exception $e) {
                $this->json = [
                    'success' => false,
                    'message' => $e->getMessage(),
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

        return parent::serve($encoding);
    }

    private function getJSON($encoding = 'UTF-8')
    {
        header('Content-Type: application/json; charset=' . $encoding);
        echo json_encode($this->json, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
    }
}
