<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Form;

/**
 * @class Component
 * @brief HTML Forms creation helpers
 */
trait AriaAttributesTrait
{
    // Component properties

    // - ARIA attributes

    /**
     * role attribute
     */
    protected ?string $role = null;

    /**
     * ariaCurrent attribute
     *
     * @var null|bool|'page'|'step'|'location'|'date'|'time' $ariaCurrent
     */
    protected null|bool|string $ariaCurrent = null;

    /**
     * ariaDescribedby attribute
     */
    protected ?string $ariaDescribedby = null;

    /**
     * ariaDescribedby attribute
     */
    protected ?string $ariaDetails = null;

    /**
     * ariaDisabled attribute
     */
    protected ?bool $ariaDisabled = null;

    /**
     * ariaHidden attribute
     */
    protected ?bool $ariaHidden = null;

    /**
     * ariaLabel attribute
     */
    protected ?string $ariaLabel = null;

    /**
     * ariaLabelledby attribute
     */
    protected ?string $ariaLabelledby = null;

    // Setters

    /**
     * Set role attribute
     */
    public function role(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Set ariaCurrent attribute
     *
     * @param bool|'page'|'step'|'location'|'date'|'time' $ariaCurrent
     */
    public function ariaCurrent(bool|string $ariaCurrent): static
    {
        $this->ariaCurrent = $ariaCurrent;

        return $this;
    }

    /**
     * Set ariaDescribedby attribute
     */
    public function ariaDescribedby(string $ariaDescribedby): static
    {
        $this->ariaDescribedby = $ariaDescribedby;

        return $this;
    }

    /**
     * Set ariaDetails attribute
     */
    public function ariaDetails(?string $ariaDetails): static
    {
        $this->ariaDetails = $ariaDetails;

        return $this;
    }

    /**
     * Set ariaDisabled attribute
     */
    public function ariaDisabled(bool $ariaDisabled): static
    {
        $this->ariaDisabled = $ariaDisabled;

        return $this;
    }

    /**
     * Set ariaHidden attribute
     */
    public function ariaHidden(bool $ariaHidden): static
    {
        $this->ariaHidden = $ariaHidden;

        return $this;
    }

    /**
     * Set ariaLabel attribute
     */
    public function ariaLabel(string $ariaLabel): static
    {
        $this->ariaLabel = $ariaLabel;

        return $this;
    }

    /**
     * Set ariaLabelledby attribute
     */
    public function ariaLabelledby(string $ariaLabelledby): static
    {
        $this->ariaLabelledby = $ariaLabelledby;

        return $this;
    }
}
