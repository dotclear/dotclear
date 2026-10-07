<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

class MenuItemEdit
{
    /**
     * @param string $type             Type of item
     * @param string $select           Additional choice if any
     * @param string $label            Menu item label
     * @param string $description      Menu item description
     * @param string $url              Menu item url
     * @param string $select_label     Displayed on further admin step only
     */
    public function __construct(
        protected string $type,
        protected string $select = '',
        protected string $label = '',
        protected string $description = '',
        protected string $url = '',
        protected string $select_label = ''
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSelect(): string
    {
        return $this->select;
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
        return $this->description;
    }

    public function setDescription(string $description = ''): void
    {
        $this->description = $description;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url = ''): void
    {
        $this->url = $url;
    }

    public function getSelectLabel(): string
    {
        return $this->select_label;
    }

    public function setSelectLabel(string $select_label = ''): void
    {
        $this->select_label = $select_label;
    }
}
