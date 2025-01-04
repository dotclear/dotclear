<?php

declare(strict_types=1);

/**
 * @namespace   Dotclear.Helper.Stack
 * @brief       List filters helpers.
 */

namespace Dotclear\Helper\Stack;

use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       Generic class for admin list filters form.
 *
 * @since       2.20
 * @package     Dotclear
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
class Filter
{
    /**
     * The filter properties.
     *
     * @var     array<string, mixed>     $properties
     */
    protected $properties = [
        'id'      => '',
        'value'   => null,
        'form'    => 'none',
        'prime'   => false,
        'title'   => '',
        'options' => [],
        'html'    => '',
        'params'  => [],
    ];

    /**
     * Constructs a new filter.
     *
     * @param   string  $id     The filter id
     * @param   mixed   $value  The filter value
     */
    public function __construct(string $id, $value = null)
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new Exception('not a valid id');
        }
        $this->properties['id']    = $id;
        $this->properties['value'] = $value;
    }

    /**
     * Magic isset filter properties.
     *
     * @param   string  $property   The property
     *
     * @return  bool    True if it is set
     */
    public function __isset(string $property): bool
    {
        return isset($this->properties[$property]);
    }

    /**
     * Magic get.
     *
     * @param   string  $property   The property
     *
     * @return  mixed   Property
     */
    public function __get(string $property)
    {
        return $this->get($property);
    }

    /**
     * Get a filter property.
     *
     * @param   string  $property   The property
     *
     * @return  mixed   The value
     */
    public function get(string $property)
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Magic set.
     *
     * @param   string  $property   The property
     * @param   mixed   $value      The value
     *
     * @return  Filter  The filter instance
     */
    public function __set(string $property, $value)
    {
        return $this->set($property, $value);   // @phpstan-ignore-line
    }

    /**
     * Set a property value.
     *
     * @param   string  $property   The property
     * @param   mixed   $value      The value
     *
     * @return  Filter  The filter instance
     */
    public function set(string $property, $value)
    {
        if (isset($this->properties[$property]) && method_exists($this, $property)) {
            return call_user_func([$this, $property], $value);  // @phpstan-ignore-line
        }

        return $this;
    }

    /**
     * Set filter form type.
     *
     * @param   string  $type   The type
     *
     * @return  Filter  The filter instance
     */
    public function form(string $type): Filter
    {
        if (in_array($type, ['none', 'input', 'select', 'html'])) {
            $this->properties['form'] = $type;
        }

        return $this;
    }

    /**
     * Set filter form title.
     *
     * @param   string  $title  The title
     *
     * @return  Filter  The filter instance
     */
    public function title(string $title): Filter
    {
        $this->properties['title'] = $title;

        return $this;
    }

    /**
     * Set filter form options.
     *
     * If filter form is a select box, this is the select options
     *
     * @param   array<mixed>    $options    The options
     * @param   bool            $set_form   Auto set form type
     *
     * @return  Filter  The filter instance
     */
    public function options(array $options, bool $set_form = true): Filter
    {
        $this->properties['options'] = $options;
        if ($set_form) {
            $this->form('select');
        }

        return $this;
    }

    /**
     * Set filter value.
     *
     * @param   mixed   $value  The value
     *
     * @return  Filter  The filter instance
     */
    public function value($value): Filter
    {
        $this->properties['value'] = $value;

        return $this;
    }

    /**
     * Set filter column in form.
     *
     * @param   bool    $prime  First column
     *
     * @return  Filter  The filter instance
     */
    public function prime(bool $prime): Filter
    {
        $this->properties['prime'] = $prime;

        return $this;
    }

    /**
     * Set filter html contents.
     *
     * @param   string  $contents   The contents
     * @param   bool    $set_form   Auto set form type
     *
     * @return  Filter  The filter instance
     */
    public function html(string $contents, bool $set_form = true): Filter
    {
        $this->properties['html'] = $contents;
        if ($set_form) {
            $this->form('html');
        }

        return $this;
    }

    /**
     * Set filter param (list query param).
     *
     * @param   string|null     $name  The param name
     * @param   mixed           $value The param value
     *
     * @return  Filter  The filter instance
     */
    public function param(?string $name = null, $value = null): Filter
    {
        # filter id as param name
        if ($name === null) {
            $name = $this->properties['id'];
        }
        # filter value as param value
        if (null === $value) {
            $value = fn ($f) => $f[0];
        }
        $this->properties['params'][] = [$name, $value];

        return $this;
    }

    /**
     * Parse the filter properties.
     *
     * Only input and select forms are parsed
     */
    public function parse(): void
    {
        # form select
        if ($this->form == 'select') {
            # _GET value
            if ($this->value === null) {
                $get = $_GET[$this->id] ?? '';
                if ($get === '' || !in_array($get, $this->options, true)) {
                    $get = '';
                }
                $this->value($get);
            }
            # HTML field
            $select = (new Select($this->id))
                ->default(Html::escapeHTML($this->value))
                ->items($this->options);

            $label = (new Label($this->title, 2, $this->id))
                ->class('ib');

            $this->html($label->render($select->render()), false);

            # form input
        } elseif ($this->form == 'input') {
            # _GET value
            if ($this->value === null) {
                $this->value($_GET[$this->id] ?? '');
            }
            # HTML field
            $input = (new Input($this->id))
                ->size(20)
                ->maxlength(255)
                ->value(Html::escapeHTML($this->value));

            $label = (new Label($this->title, 2, $this->id))
                ->class('ib');

            $this->html($label->render($input->render()), false);
        }
    }
}
