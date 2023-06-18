<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Exception;
use dcCore;
use Dotclear\Helper\Html\Html;
use form;

abstract class Module
{
    /**
     * Module type
     *
     * @var string
     */
    public $type;

    /**
     * Module ID (class name)
     *
     * @var string
     */
    public $id;

    /**
     * Module name
     *
     * @var string
     */
    public $name;

    /**
     * Module description
     *
     * @var string
     */
    public $description;

    /**
     * Import URL
     *
     * @var string
     */
    protected $import_url;

    /**
     * Export URL
     *
     * @var string
     */
    protected $export_url;

    /**
     * Module URL
     *
     * @var string
     */
    protected $url;

    /**
     * Constructs a new instance.
     *
     * @throws     Exception
     */
    public function __construct()
    {
        $this->setInfo();

        if (!in_array($this->type, ['import', 'export'])) {
            throw new Exception(sprintf('Unknown type for module %s', get_class($this)));
        }

        if (!$this->name) {
            $this->name = get_class($this);
        }

        $this->id  = get_class($this);
        $this->url = sprintf(urldecode(dcCore::app()->adminurl->get('admin.plugin', ['p' => 'importExport', 'type' => '%s', 'module' => '%s'], '&')), $this->type, $this->id);
    }

    /**
     * Initializes the module.
     */
    public function init()
    {
    }

    /**
     * Sets the module information.
     */
    abstract protected function setInfo();

    /**
     * Gets the module URL.
     *
     * @param      bool         $escape  The escape
     *
     * @return     string  The url.
     */
    final public function getURL(bool $escape = false): string
    {
        return $escape ? Html::escapeHTML($this->url) : $this->url;
    }

    /**
     * Processes the import/export.
     *
     * @param      string  $do     action
     */
    abstract public function process(string $do);

    /**
     * GUI for import/export module
     */
    abstract public function gui();

    /**
     * Return a progress bar
     *
     * @param      float     $percent  The percent
     *
     * @return     string
     */
    protected function progressBar(float $percent): string
    {
        $percent = trim((string) max(ceil($percent), 100));

        return '<div class="ie-progress"><progress id="file" max="100" value="' . $percent . '">' . $percent . '%</progress></div>';
    }

    /**
     * Return a hidden autosubmit input field
     *
     * @return     string
     */
    protected function autoSubmit(): string
    {
        return form::hidden(['autosubmit'], 1);
    }

    /**
     * Return a congratulation message
     *
     * @return     string
     */
    protected function congratMessage()
    {
        return
        '<h3>' . __('Congratulation!') . '</h3>' .
        '<p class="success">' . __('Your blog has been successfully imported. Welcome on Dotclear 2!') . '</p>' .
        '<ul>' .
        '<li>' .
        '<strong>' .
        '<a href="' . dcCore::app()->adminurl->decode('admin.post') . '">' . __('Why don\'t you blog this now?') . '</a>' .
        '</strong>' .
        '</li>' .
        '<li>' .
        __('or') .
        ' <a href="' . dcCore::app()->adminurl->decode('admin.home') . '">' . __('visit your dashboard') . '</a>' .
        '</li>' .
        '</ul>';
    }
}
