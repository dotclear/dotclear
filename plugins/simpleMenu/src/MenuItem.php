<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

/**
 * @brief   The module menu item object.
 * @ingroup simpleMenu
 *
 * @phpstan-type TSimpleMenuItem array{
 *     label: string,
 *     descr: string,
 *     url: string,
 *     targetBlank: bool,
 *     data: string,
 *     disabled: bool
 * }
 */
class MenuItem
{
    /**
     * @param string $label        Label of menu item
     * @param string $descripion   Description (may be used as link title and/or complement for label)
     * @param string $url          Menu item URL
     * @param bool   $target_blank Set to true if URL should be opened in a new window/tab
     * @param string $data         Data which be added as data-menuitem attribute
     * @param bool   $disabled     Set to true to disabled this menu item
     */
    public function __construct(
        protected string $label,
        protected string $descripion = '',
        protected string $url = '',
        protected bool $target_blank = false,
        protected string $data = '',
        protected bool $disabled = false
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getDescription(): string
    {
        return $this->descripion;
    }

    public function setDescription(string $descripion = ''): void
    {
        $this->descripion = $descripion;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url = ''): void
    {
        $this->url = $url;
    }

    public function getTargetBlank(): bool
    {
        return $this->target_blank;
    }

    public function setTargetBlank(bool $target_blank = false): void
    {
        $this->target_blank = $target_blank;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data = ''): void
    {
        $this->data = $data;
    }

    public function getDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled = false): void
    {
        $this->disabled = $disabled;
    }

    /**
     * Get an associative array of value
     *
     * Note that the array shape of Menu Item is Dotclear 2.38 and previous release compatible
     *
     * @return TSimpleMenuItem
     */
    public function getArray(): array
    {
        return [
            'label'       => $this->label,
            'descr'       => $this->descripion,
            'url'         => $this->url,
            'targetBlank' => $this->target_blank,
            'data'        => $this->data,
            'disabled'    => $this->disabled,
        ];
    }

    /**
     * Load a item from setting information (best effort with default values)
     *
     * @param  array<array-key, mixed>  $item
     */
    public static function load(array $item): self
    {
        $label        = isset($item['label'])       && is_string($label = $item['label']) ? $label : '';
        $description  = isset($item['descr'])       && is_string($description = $item['descr']) ? $description : '';
        $url          = isset($item['url'])         && is_string($url = $item['url']) ? $url : '';
        $target_blank = isset($item['targetBlank']) && $item['targetBlank'];
        $data         = isset($item['data'])        && is_string($data = $item['data']) ? $data : '';
        $disabled     = isset($item['disabled'])    && $item['disabled'];

        return new self($label, $description, $url, $target_blank, $data, $disabled);
    }
}
