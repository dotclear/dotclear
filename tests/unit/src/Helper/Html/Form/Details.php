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

class Details extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');

        $this
            ->string($component->render())
            ->match('/<details.*?>(?:.*?\n*)?<\/details>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('open')
        ;
    }

    public function testWithElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'div');

        $this
            ->string($component->render())
            ->match('/<div.*?>(?:.*?\n*)?<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'slot');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('details')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Details')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');

        $this
            ->string($component->getElement())
            ->isEqualTo('details')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }

    public function testAttachSummary()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);

        $this
            ->string($component->render())
            ->contains($summary->render())
        ;
    }

    public function testAttachNullSummary()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);
        $component->attachSummary(null);

        $this
            ->string($component->render())
            ->notContains($summary->render())
        ;
    }

    public function testDetachSummary()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);
        $component->detachSummary();

        $this
            ->string($component->render())
            ->notContains($summary->render())
        ;
    }

    public function testFields()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $field = new \Dotclear\Helper\Html\Form\Input(['myinput']);
        $component->items([
            $field,
        ]);

        $this
            ->string($component->render())
            ->contains($field->render())
        ;
    }

    public function testFieldsIncludingSummary()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $item    = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->items([
            $summary,
            $item,
        ]);

        $this
            ->string($component->render())
            ->contains($summary->render())
            ->contains($item->render())
        ;
    }

    public function testFieldsIncludingAttachedSummary()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);  // Attached summary will come first before items

        $item = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->items([
            $item,
            $summary,
        ]);

        $this
            ->string($component->render())
            ->contains($summary->render())
            ->contains($item->render())
            ->contains($summary->render() . $item->render())
            ->notContains($item->render() . $summary->render())
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $this
            ->string($component->render())
            ->match('/<details.*?>(?:.*?\n*)?<\/details>/')
        ;
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details(null, 'div');

        $this
            ->string($component->render())
            ->match('/<div.*?>(?:.*?\n*)?<\/div>/')
        ;
    }

    public function testAttributeFalseOpen()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();
        $component->open(false);

        $this
            ->string($component->render())
            ->match('/<details.*?>\n<\/details>/')
            ->notContains('open')
        ;
    }

    public function testAttributeTrueOpen()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();
        $component->open(true);

        $this
            ->string($component->render())
            ->match('/<details.*?>\n<\/details>/')
            ->contains('open')
        ;
    }
}
