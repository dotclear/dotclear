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

namespace tests\unit\Dotclear\Helper\Html\Form;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;
use Dotclear\Helper\Html\Form\Caption;

class Table extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my');

        $this
            ->string($component->render())
            ->match('/<table.*?>(?:.*?\n*)?<\/table>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testWithElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my', 'div');

        $this
            ->string($component->render())
            ->match('/<div.*?>(?:.*?\n*)?<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my', 'slot');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('table')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Table')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my');

        $this
            ->string($component->getElement())
            ->isEqualTo('table')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }

    public function testAttachCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);

        $this
            ->string($component->render())
            ->contains($caption->render())
        ;
    }

    public function testAttachNullCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);
        $component->attachCaption(null);

        $this
            ->string($component->render())
            ->notContains($caption->render())
        ;
    }

    public function testDetachCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);
        $component->detachCaption();

        $this
            ->string($component->render())
            ->notContains($caption->render())
        ;
    }

    public function testItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $item = new \Dotclear\Helper\Html\Form\Tbody(['myinput']);
        $component->items([
            $item,
        ]);

        $this
            ->string($component->render())
            ->contains($item->render())
        ;
    }

    public function testParts()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $thead   = new \Dotclear\Helper\Html\Form\Thead();
        $tbody   = new \Dotclear\Helper\Html\Form\Tbody();
        $tfoot   = new \Dotclear\Helper\Html\Form\Tfoot();

        $component->items([
            $caption,
            $thead,
            $tbody,
            $tfoot,
        ]);

        $this
            ->string($component->render())
            ->contains($caption->render())
            ->string($component->render())
            ->contains($thead->render())
            ->string($component->render())
            ->contains($tbody->render())
            ->string($component->render())
            ->contains($tfoot->render())
        ;
    }

    public function testPartsUnordered()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $thead   = new \Dotclear\Helper\Html\Form\Thead();
        $tbody   = new \Dotclear\Helper\Html\Form\Tbody();
        $tfoot   = new \Dotclear\Helper\Html\Form\Tfoot();

        $component->items([
            $tfoot,
            $tbody,
            $thead,
            $caption,
        ]);

        $this
            ->string($component->render())
            ->contains($caption->render())
            ->string($component->render())
            ->contains($thead->render())
            ->string($component->render())
            ->contains($tbody->render())
            ->string($component->render())
            ->contains($tfoot->render())
        ;
    }

    public function testDirectParts()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $thead   = new \Dotclear\Helper\Html\Form\Thead();
        $tbody   = new \Dotclear\Helper\Html\Form\Tbody();
        $tfoot   = new \Dotclear\Helper\Html\Form\Tfoot();

        $component
            ->caption($caption)
            ->thead($thead)
            ->tbody($tbody)
            ->tfoot($tfoot)
        ;

        $this
            ->string($component->render())
            ->contains($caption->render())
            ->string($component->render())
            ->contains($thead->render())
            ->string($component->render())
            ->contains($tbody->render())
            ->string($component->render())
            ->contains($tfoot->render())
        ;
    }

    public function testItemsIncludingCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $item    = new \Dotclear\Helper\Html\Form\Tbody(['myinput']);

        $component->items([
            $caption,
            $item,
        ]);

        $this
            ->string($component->render())
            ->contains($caption->render())
            ->contains($item->render())
        ;
    }

    public function testFieldsIncludingAttachedCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);  // Attached caption will come first before items

        $item = new \Dotclear\Helper\Html\Form\Tbody(['myinput']);

        $component->items([
            $item,
            $caption,
        ]);

        $this
            ->string($component->render())
            ->contains($caption->render())
            ->contains($item->render())
            ->contains($caption->render() . $item->render())
            ->notContains($item->render() . $caption->render())
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $this
            ->string($component->render())
            ->match('/<table.*?>(?:.*?\n*)?<\/table>/')
        ;
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table(null, 'div');

        $this
            ->string($component->render())
            ->match('/<div.*?>(?:.*?\n*)?<\/div>/')
        ;
    }
}
