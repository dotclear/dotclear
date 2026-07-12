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
     */
    public string $messageType;

    /**
     * Fault code
     */
    public int $faultCode;

    /**
     * Fault string
     */
    public string $faultString;

    /**
     * Method name
     */
    public string $methodName;

    /**
     * Method parameters
     *
     * @var mixed[] $params
     */
    public array $params = [];

    // Currentstring variable stacks

    /**
     * The stack used to keep track of the current array/struct
     *
     * @var mixed[]     $_arraystructs
     */
    protected array $_arraystructs = [];

    /**
     * Stack keeping track of if things are structs or array
     *
     * @var mixed[]     $_arraystructstypes
     */
    protected array $_arraystructstypes = [];

    /**
     * A stack as well
     *
     * @var mixed[]     $_currentStructName
     */
    protected array $_currentStructName = [];

    /**
     * Current XML tag
     */
    protected string $_currentTag;

    /**
     * Current XML tag content
     */
    protected string $_currentTagContents = '';

    /**
     * The XML parser
     *
     * @var mixed   resource|XMLParser  $_parser
     */
    protected $_parser;

    /**
     * Constructor
     *
     * @param string    $message        XML Message
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
        if (PHP_VERSION_ID < 80400) {
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

        # Grab the error messages, if any
        if ($this->messageType === 'fault') {
            $code = is_array($this->params[0])
                && isset($this->params[0]['faultCode'])
                && is_numeric($code = $this->params[0]['faultCode']) ? (int) $code : 0;
            $string = is_array($this->params[0])
                && isset($this->params[0]['faultString'])
                && is_string($string = $this->params[0]['faultString']) ? $string : '';

            $this->faultCode   = $code;
            $this->faultString = $string;
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
                $value                     = (int) trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'double':
                $value                     = (float) trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'string':
                $value                     = trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'dateTime.iso8601':
                $value                     = new Date(trim($this->_currentTagContents));
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'value':
                # "If no type is indicated, the type is string."
                if (trim($this->_currentTagContents) !== '') {
                    $value                     = $this->_currentTagContents;
                    $this->_currentTagContents = '';
                    $valueFlag                 = true;
                }

                break;
            case 'boolean':
                $value                     = (bool) trim($this->_currentTagContents);
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
                $this->_currentStructName[] = trim($this->_currentTagContents);
                $this->_currentTagContents  = '';

                break;
            case 'methodName':
                $this->methodName          = trim($this->_currentTagContents);
                $this->_currentTagContents = '';

                break;
        }

        if ($valueFlag) {
            if (count($this->_arraystructs) > 0) {
                // Add value to struct or array
                $last_index_structs = count($this->_arraystructs) - 1;
                if ($this->_arraystructstypes[count($this->_arraystructstypes) - 1] == 'struct') {
                    // Add to struct
                    $last_index_structname = count($this->_currentStructName) - 1;
                    if (is_array($this->_arraystructs[$last_index_structs])) {
                        $offset = $this->_currentStructName[$last_index_structname];
                        if (is_string($offset) || is_int($offset)) {
                            $this->_arraystructs[$last_index_structs][$offset] = $value;
                        }
                    }
                } else {
                    // Add to array
                    if (!is_array($this->_arraystructs[$last_index_structs])) {
                        $this->_arraystructs[$last_index_structs] = [];
                    }
                    $this->_arraystructs[$last_index_structs][] = $value;
                }
            } else {
                # Just add as a paramater
                $this->params[] = $value;
            }
        }
    }
}
