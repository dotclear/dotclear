<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

use DOMDocument;
use Exception;

/**
 * @class Message
 *
 * XLM-RPC helpers
 */
class Message
{
    /**
     * Brut XML message
     */
    protected string $brutxml;

    /**
     * Type of message - methodCall / methodResponse / fault
     *
     * @var string
     */
    public $messageType;

    /**
     * Fault code
     *
     * @var int
     */
    public $faultCode;

    /**
     * Fault string
     *
     * @var string
     */
    public $faultString;

    /**
     * Method name
     *
     * @var string
     */
    public $methodName;

    /**
     * Method parameters
     *
     * @var array<int, mixed>
     */
    public $params = [];

    // Currentstring variable stacks

    /**
     * The stack used to keep track of the current array/struct
     *
     * @var array<int, mixed>
     */
    protected $_arraystructs = [];

    /**
     * Stack keeping track of if things are structs or array
     *
     * @var array<int, mixed>
     */
    protected $_arraystructstypes = [];

    /**
     * A stack as well
     *
     * @var array<int, mixed>
     */
    protected $_currentStructName = [];

    /**
     * Current XML tag
     *
     * @var string
     */
    protected $_currentTag;

    /**
     * Current XML tag content
     *
     * @var string
     */
    protected $_currentTagContents;

    /**
     * The XML parser
     *
     * @var mixed   resource|XMLParser
     */
    protected $_parser;

    /**
     * Constructor
     *
     * @param string        $message        XML Message
     */
    public function __construct(
        protected string $message
    ) {
        $this->brutxml = $this->message;
    }

    /**
     * Message parser
     */
    public function parse(): bool
    {
        // first remove the XML declaration
        $this->message = (string) preg_replace('/<\?xml(.*)?\?>/', '', $this->message);
        if (trim($this->message) === '') {
            throw new Exception('XML Parser Error. Empty message');
        }

        // Strip DTD.
        $header = (string) preg_replace('/^<!DOCTYPE[^>]*+>/im', '', substr($this->message, 0, 200), 1);

        $xml = trim(substr_replace($this->message, $header, 0, 200));
        if ($xml === '') {
            throw new Exception('XML Parser Error.');
        }

        // Confirm the XML now starts with a valid root tag. A root tag can end in [> \t\r\n]
        $root_tag = substr($xml, 0, strcspn(substr($xml, 0, 20), "> \t\r\n"));

        // Reject a second DTD.
        if (strtoupper($root_tag) === '<!DOCTYPE') {
            throw new Exception('XML Parser Error.');
        }

        // Check root tag
        if (!in_array($root_tag, ['<methodCall', '<methodResponse', '<fault'])) {
            throw new Exception('XML Parser Error.');
        }

        try {
            $dom = new DOMDocument();
            if ($dom->loadXML($xml)) {
                if ($dom->getElementsByTagName('*')->length > 30000) {
                    throw new Exception('XML Parser Error.');
                }
            } else {
                throw new Exception('XML Parser Error.');
            }
        } catch (Exception) {
            throw new Exception('XML Parser Error.');
        }
        $this->_parser = xml_parser_create();
        # Set XML parser to take the case of tags in to account
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);

        # Set XML parser callback functions
        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            xml_set_object($this->_parser, $this); // No more needed with PHP 8.4
        }
        xml_set_element_handler(
            $this->_parser,
            $this->tag_open(...),
            $this->tag_close(...)
        );
        xml_set_character_data_handler($this->_parser, $this->cdata(...));

        if (xml_parse($this->_parser, $this->message) === 0) {
            $c = xml_get_error_code($this->_parser);
            $e = xml_error_string($c);
            $e .= ' on line ' . xml_get_current_line_number($this->_parser);

            throw new Exception('XML Parser Error. ' . $e, $c);
        }

        xml_parser_free($this->_parser);

        # Grab the error messages, if any
        if ($this->messageType == 'fault') {
            $this->faultCode   = (int) $this->params[0]['faultCode'];
            $this->faultString = $this->params[0]['faultString'];
        }

        return true;
    }

    /**
     * xml_set_element_handler() start handler
     *
     * @param      mixed                    $parser  The parser (resource|XMLParser)
     * @param      string                   $tag     The tag
     * @param      array<string, mixed>     $attr    The attribute
     */
    protected function tag_open($parser, string $tag, array $attr): void
    {
        $this->_currentTag = $tag;

        switch ($tag) {
            case 'methodCall':
            case 'methodResponse':
            case 'fault':
                $this->messageType = $tag;

                break;
                # Deal with stacks of arrays and structs
            case 'data': # data is to all intents and puposes more interesting than array
                $this->_arraystructstypes[] = 'array';
                $this->_arraystructs[]      = [];

                break;
            case 'struct':
                $this->_arraystructstypes[] = 'struct';
                $this->_arraystructs[]      = [];

                break;
        }
    }

    /**
     * xml_set_character_data_handler() data handler
     *
     * @param      mixed                $parser  The parser (resource|XMLParser)
     * @param      string               $cdata   The cdata
     */
    protected function cdata($parser, string $cdata): void
    {
        $this->_currentTagContents .= $cdata;
    }

    /**
     * xml_set_element_handler() start handler
     *
     * @param      mixed                $parser  The parser (resource|XMLParser)
     * @param      string               $tag     The tag
     */
    protected function tag_close($parser, string $tag): void
    {
        $valueFlag = false;
        $value     = null;

        switch ($tag) {
            case 'int':
            case 'i4':
                $value                     = (int) trim((string) $this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'double':
                $value                     = (float) trim((string) $this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'string':
                $value                     = trim((string) $this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'dateTime.iso8601':
                $value                     = new Date(trim((string) $this->_currentTagContents));
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'value':
                # "If no type is indicated, the type is string."
                if (trim($this->_currentTagContents) !== '') {
                    $value                     = (string) $this->_currentTagContents;
                    $this->_currentTagContents = '';
                    $valueFlag                 = true;
                }

                break;
            case 'boolean':
                $value                     = (bool) trim((string) $this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'base64':
                $value                     = base64_decode($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
                # Deal with stacks of arrays and structs
            case 'data':
            case 'struct':
                $value = array_pop($this->_arraystructs);
                array_pop($this->_arraystructstypes);
                $valueFlag = true;

                break;
            case 'member':
                array_pop($this->_currentStructName);

                break;
            case 'name':
                $this->_currentStructName[] = trim((string) $this->_currentTagContents);
                $this->_currentTagContents  = '';

                break;
            case 'methodName':
                $this->methodName          = trim((string) $this->_currentTagContents);
                $this->_currentTagContents = '';

                break;
        }

        if ($valueFlag) {
            if (count($this->_arraystructs) > 0) {
                # Add value to struct or array
                if ($this->_arraystructstypes[count($this->_arraystructstypes) - 1] == 'struct') {
                    # Add to struct
                    $this->_arraystructs[count($this->_arraystructs) - 1][$this->_currentStructName[count($this->_currentStructName) - 1]] = $value;
                } else {
                    # Add to array
                    $this->_arraystructs[count($this->_arraystructs) - 1][] = $value;
                }
            } else {
                # Just add as a paramater
                $this->params[] = $value;
            }
        }
    }
}
