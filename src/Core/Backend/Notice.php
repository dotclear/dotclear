<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

class Notice
{
    /**
     * @param string                    $type       Notice type (see Backend/Notices)
     * @param string                    $ts         Notice timestamp
     * @param string                    $msg        Notice message
     * @param string                    $format     Notice format (text, html, …)
     * @param string                    $class      Notice class
     * @param bool                      $use_ts     Use timestamp for display
     * @param bool                      $div        Use a DIV element for display
     * @param array<string, mixed>      $options    Notice options (3rd party usage)
     */
    public function __construct(
        protected string $type = '',
        protected string $ts = '',
        protected string $msg = '',
        protected string $format = '',
        protected string $class = '',
        protected bool $use_ts = true,
        protected bool $div = false,
        protected array $options = []
    ) {
    }

    /**
     * Get Notice type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get Notice timestamp
     */
    public function getTs(): string
    {
        return $this->ts;
    }

    /**
     * Get Notice message
     */
    public function getMsg(): string
    {
        return $this->msg;
    }

    /**
     * Get Notice format
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Get Notice class
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Use notice timestamp to display notice?
     */
    public function useTs(): bool
    {
        return $this->use_ts;
    }

    /**
     * Use a DIV element to display notice?
     */
    public function useDiv(): bool
    {
        return $this->div;
    }

    /**
     * Get Notice options
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
