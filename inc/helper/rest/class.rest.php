<?php
/**
 * @class restServer
 * @brief REST Server
 *
 * A very simple REST server implementation
 *
 * @package Clearbricks
 * @subpackage Rest
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class restServer
{
    /**
     * Server response (XML)
     *
     * @var xmlTag
     */
    public $rsp;

    /**
     * Server's functions
     *
     * @var        array of array [callback, xml?]
     */
    public $functions = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rsp = new xmlTag('rsp');
    }

    /**
     * Add Function
     *
     * This adds a new function to the server. <var>$callback</var> should be
     * a valid PHP callback. Callback function takes two arguments: GET and
     * POST values.
     *
     * @param string            $name        Function name
     * @param callable|array    $callback    Callback function
     */
    public function addFunction(string $name, $callback): void
    {
        if (is_callable($callback)) {
            $this->functions[$name] = $callback;
        }
    }

    /**
     * Call Function
     *
     * This method calls callback named <var>$name</var>.
     *
     * @param string    $name        Function name
     * @param array     $get         GET values
     * @param array     $post        POST values
     *
     * @return mixed
     */
    protected function callFunction(string $name, array $get, array $post)
    {
        if (isset($this->functions[$name])) {
            return call_user_func($this->functions[$name], $get, $post);
        }
    }

    /**
     * Main server
     *
     * This method creates the main server.
     *
     * @param string    $encoding        Server charset
     *
     * @return bool
     */
    public function serve(string $encoding = 'UTF-8'): bool
    {
        $get  = $_GET ?: [];
        $post = $_POST ?: [];

        if (!isset($_REQUEST['f'])) {
            $this->rsp->status = 'failed';
            $this->rsp->message('No function given');
            $this->getXML($encoding);

            return false;
        }

        if (!isset($this->functions[$_REQUEST['f']])) {
            $this->rsp->status = 'failed';
            $this->rsp->message('Function does not exist');
            $this->getXML($encoding);

            return false;
        }

        try {
            $res = $this->callFunction($_REQUEST['f'], $get, $post);
        } catch (Exception $e) {
            $this->rsp->status = 'failed';
            $this->rsp->message($e->getMessage());
            $this->getXML($encoding);

            return false;
        }

        $this->rsp->status = 'ok';
        $this->rsp->insertNode($res);
        $this->getXML($encoding);

        return true;
    }

    /**
     * Stream the XML data (header and body)
     *
     * @param      string  $encoding  The encoding
     */
    private function getXML($encoding = 'UTF-8')
    {
        header('Content-Type: text/xml; charset=' . $encoding);
        echo $this->rsp->toXML(true, $encoding);
    }
}

/**
 * @class xmlTag
 *
 * XML Tree
 *
 * @package Clearbricks
 * @subpackage XML
 */
class xmlTag
{
    /**
     * XML tag name
     *
     * @var mixed
     */
    private $_name;

    /**
     * XML tag attributes
     *
     * @var        array
     */
    private $_attr = [];

    /**
     * XML tag nodes (childs)
     *
     * @var        array
     */
    private $_nodes = [];

    /**
     * Constructor
     *
     * Creates the root XML tag named <var>$name</var>. If content is given,
     * it will be appended to root tag with {@link insertNode()}
     *
     * @param string        $name           Tag name
     * @param mixed         $content        Tag content
     */
    public function __construct(?string $name = null, $content = null)
    {
        $this->_name = $name;

        if ($content !== null) {
            $this->insertNode($content);
        }
    }

    /**
     * Add Attribute
     *
     * Magic __set method to add an attribute.
     *
     * @param string    $name        Attribute name
     * @param mixed     $value       Attribute value
     *
     * @see insertAttr()
     */
    public function __set(string $name, $value): void
    {
        $this->insertAttr($name, $value);
    }

    /**
     * Add a tag
     *
     * This magic __call method appends a tag to XML tree.
     *
     * @param string    $name        Tag name
     * @param array     $args        Function arguments, the first one would be tag content
     */
    public function __call(string $name, array $args)
    {
        if (!preg_match('#^[a-z_]#', $name)) {
            return false;
        }

        if (!isset($args[0])) {
            $args[0] = null;
        }

        $this->insertNode(new self($name, $args[0]));
    }

    /**
     * Add CDATA
     *
     * Appends CDATA to current tag.
     *
     * @param string    $value        Tag CDATA content
     */
    public function CDATA(string $value): void
    {
        $this->insertNode($value);
    }

    /**
     * Add Attribute
     *
     * This method adds an attribute to current tag.
     *
     * @param string    $name         Attribute name
     * @param mixed     $value        Attribute value
     *
     * @see insertAttr()
     */
    public function insertAttr(string $name, $value): void
    {
        $this->_attr[$name] = $value;
    }

    /**
     * Insert Node
     *
     * This method adds a new XML node. Node could be a instance of xmlTag, an
     * array of valid values, a boolean or a string.
     *
     * @param xmlTag|array|bool|string    $node    Node value
     */
    public function insertNode($node = null): void
    {
        if ($node instanceof self) {
            $this->_nodes[] = $node;
        } elseif (is_array($node)) {
            $child = new self(null);
            foreach ($node as $tag => $n) {
                $child->insertNode(new self($tag, $n));
            }
            $this->_nodes[] = $child;
        } elseif (is_bool($node)) {
            $this->_nodes[] = $node ? '1' : '0';
        } else {
            $this->_nodes[] = (string) $node;
        }
    }

    /**
     * XML Result
     *
     * Returns a string with XML content.
     *
     * @param bool      $prolog          Append prolog to result
     * @param string    $encoding        Result charset
     *
     * @return string
     */
    public function toXML(bool $prolog = false, string $encoding = 'UTF-8'): string
    {
        if ($this->_name && count($this->_nodes) > 0) {
            $format = '<%1$s%2$s>%3$s</%1$s>';
        } elseif ($this->_name && count($this->_nodes) === 0) {
            $format = '<%1$s%2$s/>';
        } else {
            $format = '%3$s';
        }

        $res = $attr = $content = '';

        foreach ($this->_attr as $k => $v) {
            $attr .= ' ' . $k . '="' . htmlspecialchars((string) $v, ENT_QUOTES, $encoding) . '"';
        }

        foreach ($this->_nodes as $node) {
            if ($node instanceof self) {
                $content .= $node->toXML();
            } else {
                $content .= htmlspecialchars((string) $node, ENT_QUOTES, $encoding);
            }
        }

        $res = sprintf($format, $this->_name, $attr, $content);

        if ($prolog && $this->_name) {
            $res = '<?xml version="1.0" encoding="' . $encoding . '" ?>' . "\n" . $res;
        }

        return $res;
    }
}
