<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

/**
 * @class Value
 *
 * XLM-RPC helpers
 */
class Value
{
    /**
     * Data value
     *
     * @var mixed
     */
    protected $data;

    /**
     * Data type
     *
     * @var string
     */
    protected $type;

    /**
     * Constructor
     *
     * @param mixed    $data        Data value
     * @param mixed    $type        Data type
     */
    public function __construct($data, $type = false)
    {
        $this->data = $data;
        if (!$type) {
            $type = $this->calculateType();
        }
        $this->type = $type;
        if ($type === 'struct' || $type === 'array') {
            if (is_array($this->data)) {
                // Turn all the values in the array in to new Value objects
                foreach ($this->data as $key => $value) {
                    $this->data[$key] = new Value($value);
                }
            }
        }
    }

    /**
     * XML Data
     *
     * Returns an XML subset of the Value.
     *
     * @return string
     */
    public function getXml(): string
    {
        # Return XML for this value
        switch ($this->type) {
            case 'boolean':
            case 'bool':
                return '<boolean>' . (!is_null($this->data) ? ($this->data ? '1' : '0') : '') . '</boolean>';
            case 'integer':
            case 'int':
                return '<int>' . (!is_null($this->data) && !is_object($this->data) ? (int) $this->data : '') . '</int>';
            case 'double':
            case 'float':
                return '<double>' . (!is_null($this->data) && !is_object($this->data) ? (float) $this->data : '') . '</double>';
            case 'string':
                return '<string>' . (!is_null($this->data) && !is_array($this->data) && !is_object($this->data) ? htmlspecialchars((string) $this->data) : '') . '</string>';
            case 'array':
                $return = '<array><data>' . "\n";
                foreach ((array) $this->data as $item) {
                    $return .= '  <value>' . (is_object($item) ? $item->getXml() : (new self($item))->getXml()) . "</value>\n";
                }
                $return .= '</data></array>';

                return $return;
            case 'struct':
                $return = '<struct>' . "\n";
                foreach ((array) $this->data as $name => $value) {
                    $return .= "  <member><name>$name</name><value>";
                    $return .= (is_object($value) ? $value->getXml() : (new self($value))->getXml()) . "</value></member>\n";
                }
                $return .= '</struct>';

                return $return;
            case 'date':
                return (is_object($this->data) && $this->data instanceof Date ?
                    $this->data->getXml() :
                    (new Date(!is_array($this->data) && !is_object($this->data) ? (int) $this->data : 0))->getXml());
            case 'base64':
                return (is_object($this->data) && $this->data instanceof Base64 ?
                    $this->data->getXml() :
                    (new Base64(!is_array($this->data) && !is_object($this->data) ? (string) $this->data : ''))->getXml()
                );
        }

        return '';
    }

    /**
     * Calculate Type
     *
     * Returns the type of the value if it was not given in constructor.
     *
     * @return string
     */
    protected function calculateType(): string
    {
        if ($this->data === true || $this->data === false) {
            return 'boolean';
        }
        if (is_integer($this->data)) {
            return 'int';
        }
        if (is_double($this->data)) {
            return 'double';
        }
        # Deal with xmlrpc object types base64 and date
        if (is_object($this->data) && $this->data instanceof Date) {
            return 'date';
        }
        if (is_object($this->data) && $this->data instanceof Base64) {
            return 'base64';
        }
        # If it is a normal PHP object convert it in to a struct
        if (is_object($this->data)) {
            $this->data = get_object_vars($this->data);

            return 'struct';
        }
        if (!is_array($this->data)) {
            return 'string';
        }
        # We have an array - is it an array or a struct ?
        if ($this->isStruct($this->data)) {
            return 'struct';
        }

        return 'array';
    }

    /**
     * Data is struct (associative array)
     *
     * Returns true if <var>$array</var> is a Struct and not only an Array.
     *
     * @param array<mixed>|array<string, mixed>        $array        Array
     *
     * @return bool
     */
    protected function isStruct(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }
}
