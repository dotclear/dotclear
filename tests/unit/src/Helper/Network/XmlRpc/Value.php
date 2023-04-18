<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\Network\XmlRpc;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

/*
 * @tags XmlRpc, XmlRpcValue
 */
class Value extends atoum
{
    public function test($value, $type, $xml)
    {
        $elt = new \Dotclear\Helper\Network\XmlRpc\Value($value, $type);
        if ($value instanceof \Dotclear\Helper\Network\XmlRpc\Date) {
            //                $this->dump(json_encode($elt->getXml()));
        }
        $this
            ->string($elt->getXml())
            ->isEqualTo($xml)
        ;
    }

    protected function testDataProvider()
    {
        $date         = new \Dotclear\Helper\Network\XmlRpc\Date('+2003-08-13T00:01:42+00:00');
        $base64       = new \Dotclear\Helper\Network\XmlRpc\Base64('data');
        $object       = new \stdClass();
        $object->data = 42;

        return [
            // Value, type (or false), XML representation

            // Null value (0-11)
            [null, false, '<string></string>'],
            [null, 'bool', '<boolean></boolean>'],
            [null, 'boolean', '<boolean></boolean>'],
            [null, 'int', '<int></int>'],
            [null, 'integer', '<int></int>'],
            [null, 'double', '<double></double>'],
            [null, 'float', '<double></double>'],
            [null, 'string', '<string></string>'],
            [null, 'array', '<array><data>' . "\n" . '</data></array>'],
            [null, 'struct', '<struct>' . "\n" . '</struct>'],
            [null, 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [null, 'base64', '<base64></base64>'],

            // Boolean true (12-20)
            [true, false, '<boolean>1</boolean>'],
            [true, 'boolean', '<boolean>1</boolean>'],
            [true, 'int', '<int>1</int>'],
            [true, 'double', '<double>1</double>'],
            [true, 'string', '<string>1</string>'],
            [true, 'array', '<array><data>' . "\n" . '  <value><boolean>1</boolean></value>' . "\n" . '</data></array>'],
            [true, 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><boolean>1</boolean></value></member>' . "\n" . '</struct>'],
            [true, 'date', '<dateTime.iso8601>19700101T00:00:01</dateTime.iso8601>'],
            [true, 'base64', '<base64>MQ==</base64>'],

            // Boolean false (21-29)
            [false, false, '<boolean>0</boolean>'],
            [false, 'boolean', '<boolean>0</boolean>'],
            [false, 'int', '<int>0</int>'],
            [false, 'double', '<double>0</double>'],
            [false, 'string', '<string></string>'],
            [false, 'array', '<array><data>' . "\n" . '  <value><boolean>0</boolean></value>' . "\n" . '</data></array>'],
            [false, 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><boolean>0</boolean></value></member>' . "\n" . '</struct>'],
            [false, 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [false, 'base64', '<base64></base64>'],

            // Integer 0 (30-38)
            [0, false, '<int>0</int>'],
            [0, 'boolean', '<boolean>0</boolean>'],
            [0, 'int', '<int>0</int>'],
            [0, 'double', '<double>0</double>'],
            [0, 'string', '<string>0</string>'],
            [0, 'array', '<array><data>' . "\n" . '  <value><int>0</int></value>' . "\n" . '</data></array>'],
            [0, 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><int>0</int></value></member>' . "\n" . '</struct>'],
            [0, 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [0, 'base64', '<base64>MA==</base64>'],

            // Integer != 0 (39-47)
            [42, false, '<int>42</int>'],
            [42, 'boolean', '<boolean>1</boolean>'],
            [42, 'int', '<int>42</int>'],
            [42, 'double', '<double>42</double>'],
            [42, 'string', '<string>42</string>'],
            [42, 'array', '<array><data>' . "\n" . '  <value><int>42</int></value>' . "\n" . '</data></array>'],
            [42, 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><int>42</int></value></member>' . "\n" . '</struct>'],
            [42, 'date', '<dateTime.iso8601>19700101T00:00:42</dateTime.iso8601>'],
            [42, 'base64', '<base64>NDI=</base64>'],

            // Float/Double 0 (48-56)
            [0.0, false, '<double>0</double>'],
            [0.0, 'boolean', '<boolean>0</boolean>'],
            [0.0, 'int', '<int>0</int>'],
            [0.0, 'double', '<double>0</double>'],
            [0.0, 'string', '<string>0</string>'],
            [0.0, 'array', '<array><data>' . "\n" . '  <value><double>0</double></value>' . "\n" . '</data></array>'],
            [0.0, 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><double>0</double></value></member>' . "\n" . '</struct>'],
            [0.0, 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [0.0, 'base64', '<base64>MA==</base64>'],

            // Float/Double != 0 (57-65)
            [3.14, false, '<double>3.14</double>'],
            [3.14, 'boolean', '<boolean>1</boolean>'],
            [3.14, 'int', '<int>3</int>'],
            [3.14, 'double', '<double>3.14</double>'],
            [3.14, 'string', '<string>3.14</string>'],
            [3.14, 'array', '<array><data>' . "\n" . '  <value><double>3.14</double></value>' . "\n" . '</data></array>'],
            [3.14, 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><double>3.14</double></value></member>' . "\n" . '</struct>'],
            [3.14, 'date', '<dateTime.iso8601>19700101T00:00:03</dateTime.iso8601>'],
            [3.14, 'base64', '<base64>My4xNA==</base64>'],

            // String empty (66-74)
            ['', false, '<string></string>'],
            ['', 'boolean', '<boolean>0</boolean>'],
            ['', 'int', '<int>0</int>'],
            ['', 'double', '<double>0</double>'],
            ['', 'string', '<string></string>'],
            ['', 'array', '<array><data>' . "\n" . '  <value><string></string></value>' . "\n" . '</data></array>'],
            ['', 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><string></string></value></member>' . "\n" . '</struct>'],
            ['', 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            ['', 'base64', '<base64></base64>'],

            // String not empty (75-83)
            ['dc', false, '<string>dc</string>'],
            ['dc', 'boolean', '<boolean>1</boolean>'],
            ['dc', 'int', '<int>0</int>'],
            ['dc', 'double', '<double>0</double>'],
            ['dc', 'string', '<string>dc</string>'],
            ['dc', 'array', '<array><data>' . "\n" . '  <value><string>dc</string></value>' . "\n" . '</data></array>'],
            ['dc', 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><string>dc</string></value></member>' . "\n" . '</struct>'],
            ['dc', 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            ['dc', 'base64', '<base64>ZGM=</base64>'],

            // Array empty (84-92)
            [[], false, '<array><data>' . "\n" . '</data></array>'],
            [[], 'boolean', '<boolean>0</boolean>'],
            [[], 'int', '<int>0</int>'],
            [[], 'double', '<double>0</double>'],
            [[], 'string', '<string></string>'],
            [[], 'array', '<array><data>' . "\n" . '</data></array>'],
            [[], 'struct', '<struct>' . "\n" . '</struct>'],
            [[], 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [[], 'base64', '<base64></base64>'],

            // Array not empty (93-101)
            [[42, 17, false], false, '<array><data>' . "\n" . '  <value><int>42</int></value>' . "\n" . '  <value><int>17</int></value>' . "\n" . '  <value><boolean>0</boolean></value>' . "\n" . '</data></array>'],
            [[42, 17, false], 'boolean', '<boolean>1</boolean>'],
            [[42, 17, false], 'int', '<int>1</int>'],
            [[42, 17, false], 'double', '<double>1</double>'],
            [[42, 17, false], 'string', '<string></string>'],
            [[42, 17, false], 'array', '<array><data>' . "\n" . '  <value><int>42</int></value>' . "\n" . '  <value><int>17</int></value>' . "\n" . '  <value><boolean>0</boolean></value>' . "\n" . '</data></array>'],
            [[42, 17, false], 'struct', '<struct>' . "\n" . '  <member><name>0</name><value><int>42</int></value></member>' . "\n" . '  <member><name>1</name><value><int>17</int></value></member>' . "\n" . '  <member><name>2</name><value><boolean>0</boolean></value></member>' . "\n" . '</struct>'],
            [[42, 17, false], 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [[42, 17, false], 'base64', '<base64></base64>'],

            // Struct not empty (102-110)
            [['id' => 42, 'status' => false], false, '<struct>' . "\n" . '  <member><name>id</name><value><int>42</int></value></member>' . "\n" . '  <member><name>status</name><value><boolean>0</boolean></value></member>' . "\n" . '</struct>'],
            [['id' => 42, 'status' => false], 'boolean', '<boolean>1</boolean>'],
            [['id' => 42, 'status' => false], 'int', '<int>1</int>'],
            [['id' => 42, 'status' => false], 'double', '<double>1</double>'],
            [['id' => 42, 'status' => false], 'string', '<string></string>'],
            [['id' => 42, 'status' => false], 'array', '<array><data>' . "\n" . '  <value><int>42</int></value>' . "\n" . '  <value><boolean>0</boolean></value>' . "\n" . '</data></array>'],
            [['id' => 42, 'status' => false], 'struct', '<struct>' . "\n" . '  <member><name>id</name><value><int>42</int></value></member>' . "\n" . '  <member><name>status</name><value><boolean>0</boolean></value></member>' . "\n" . '</struct>'],
            [['id' => 42, 'status' => false], 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [['id' => 42, 'status' => false], 'base64', '<base64></base64>'],

            // Date (111-119)
            [$date, false, '<dateTime.iso8601>20030813T00:01:42</dateTime.iso8601>'],
            [$date, 'boolean', '<boolean>1</boolean>'],
            [$date, 'int', '<int></int>'],
            [$date, 'double', '<double></double>'],
            [$date, 'string', '<string></string>'],
            [$date, 'array', '<array><data>' . "\n" . '  <value><string>2003</string></value>' . "\n" . '  <value><string>08</string></value>' . "\n" . '  <value><string>13</string></value>' . "\n" . '  <value><string>00</string></value>' . "\n" . '  <value><string>01</string></value>' . "\n" . '  <value><string>42</string></value>' . "\n" . '  <value><int>1060732902</int></value>' . "\n" . '</data></array>'],
            [$date, 'struct', '<struct>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'year</name><value><string>2003</string></value></member>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'month</name><value><string>08</string></value></member>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'day</name><value><string>13</string></value></member>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'hour</name><value><string>00</string></value></member>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'minute</name><value><string>01</string></value></member>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'second</name><value><string>42</string></value></member>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'ts</name><value><int>1060732902</int></value></member>' . "\n" . '</struct>'],
            [$date, 'date', '<dateTime.iso8601>20030813T00:01:42</dateTime.iso8601>'],
            [$date, 'base64', '<base64></base64>'],

            // Base64 (120-128)
            [$base64, false, '<base64>ZGF0YQ==</base64>'],
            [$base64, 'boolean', '<boolean>1</boolean>'],
            [$base64, 'int', '<int></int>'],
            [$base64, 'double', '<double></double>'],
            [$base64, 'string', '<string></string>'],
            [$base64, 'array', '<array><data>' . "\n" . '  <value><string>data</string></value>' . "\n" . '</data></array>'],
            [$base64, 'struct', '<struct>' . "\n" . '  <member><name>' . "\u{0000}" . '*' . "\u{0000}" . 'data</name><value><string>data</string></value></member>' . "\n" . '</struct>'],
            [$base64, 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [$base64, 'base64', '<base64>ZGF0YQ==</base64>'],

            // Base64 (129-137)
            [$object, false, '<struct>' . "\n" . '  <member><name>data</name><value><int>42</int></value></member>' . "\n" . '</struct>'],
            [$object, 'boolean', '<boolean>1</boolean>'],
            [$object, 'int', '<int></int>'],
            [$object, 'double', '<double></double>'],
            [$object, 'string', '<string></string>'],
            [$object, 'array', '<array><data>' . "\n" . '  <value><int>42</int></value>' . "\n" . '</data></array>'],
            [$object, 'struct', '<struct>' . "\n" . '  <member><name>data</name><value><int>42</int></value></member>' . "\n" . '</struct>'],
            [$object, 'date', '<dateTime.iso8601>19700101T00:00:00</dateTime.iso8601>'],
            [$object, 'base64', '<base64></base64>'],

            // Unknown type (138)
            [42, 'unknown', ''],

        ];
    }
}
