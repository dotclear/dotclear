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

use ArrayObject;

class Favorite
{
    /**
     * @param string                                    $id           favorite id
     * @param ?string                                   $title        favorite title (localized)
     * @param ?string                                   $url          favorite URL
     * @param null|string|array{0: string, 1?: string}  $small_icon   favorite small icon(s) (for menu)
     * @param null|string|array{0: string, 1?: string}  $large_icon   favorite large icon(s) (for dashboard)
     * @param string|bool|null                          $permissions  comma-separated list of permissions, if not set : no restriction
     * @param ?callable                                 $dashboard_cb callback to modify title if dynamic
     * @param ?callable                                 $active_cb    callback to tell whether current page matches favorite or not
     * @param bool                                      $active       is favorite currently active
     */
    public function __construct(
        protected readonly string $id,
        protected ?string $title,
        protected readonly ?string $url,
        protected null|string|array $small_icon,
        protected null|string|array $large_icon,
        protected readonly null|string|bool $permissions = null,
        protected readonly mixed $dashboard_cb = null,
        protected readonly mixed $active_cb = null,
        protected bool $active = false
    ) {
    }

    /**
     * Return favorite id
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Return favorite title
     */
    public function title(): ?string
    {
        return $this->title;
    }

    /**
     * Return favorite URL
     */
    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * Return favorite small icon
     *
     * @return null|string|array{0: string, 1?: string}
     */
    public function smallIcon(): null|string|array
    {
        return $this->small_icon;
    }

    /**
     * Return favorite large icon
     *
     * @return null|string|array{0: string, 1?: string}
     */
    public function largeIcon(): null|string|array
    {
        return $this->large_icon;
    }

    /**
     * Return favorite permissions
     */
    public function permissions(): null|string|bool
    {
        return $this->permissions;
    }

    /**
     * Return favorite dashboard callback
     */
    public function dashboardCallback(): ?callable
    {
        return $this->dashboard_cb;
    }

    /**
     * Set dashboard title if possible using defined callback
     */
    public function callDashboardCallback(): void
    {
        if (is_callable($this->dashboard_cb)) {
            // Prepare modifiable favorite properties
            $data = new ArrayObject([
                'title'      => $this->title,
                'small-icon' => $this->small_icon,
                'large-icon' => $this->large_icon,
            ]);

            call_user_func($this->dashboard_cb, $data);

            // Store new values if provided
            if ($data->offsetExists('title') && is_string($data['title'])) {
                $this->title = $data['title'];
            }
            if ($data->offsetExists('small-icon') && (is_array($data['small-icon']) || is_string($data['small-icon']))) {
                $this->small_icon = $data['small-icon'];
            }
            if ($data->offsetExists('large-icon') && (is_array($data['large-icon']) || is_string($data['large-icon']))) {
                $this->large_icon = $data['large-icon'];
            }
        }
    }

    /**
     * Return favorite active callback
     */
    public function activeCallback(): ?callable
    {
        return $this->active_cb;
    }

    /**
     * Return favorite active status using defined callback
     *
     * @param   string                  $url        URL part before query string
     * @param   array<string, mixed>    $request    Usually $_REQUEST array
     */
    public function callActiveCallback(string $url, array $request): bool
    {
        return is_callable($this->active_cb) ? call_user_func($this->active_cb, $url, $request) : false;
    }

    /**
     * Return favorite active status
     */
    public function active(): bool
    {
        return $this->active;
    }

    /**
     * Set favorite active status
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
