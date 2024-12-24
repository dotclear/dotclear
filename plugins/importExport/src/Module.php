<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Exception;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The abstract import export module handler.
 * @ingroup importExport
 */
abstract class Module
{
    /**
     * Module type.
     *
     * @var     string  $type
     */
    public string $type;

    /**
     * Module ID (class name).
     *
     * @var     string  $id
     */
    public string $id;

    /**
     * Module name.
     *
     * @var     string  $name
     */
    public string $name;

    /**
     * Module description.
     *
     * @var     string  $description
     */
    public string $description;

    /**
     * Import URL.
     *
     * @var     string  $import_url
     */
    protected string $import_url;

    /**
     * Export URL.
     *
     * @var     string  $export_url
     */
    protected string $export_url;

    /**
     * Module URL.
     *
     * @var     string  $url
     */
    protected string $url;

    /**
     * Constructs a new instance.
     *
     * @throws  Exception
     */
    public function __construct()
    {
        $this->setInfo();

        if (!in_array($this->type, ['import', 'export'])) {
            throw new Exception(sprintf('Unknown type for module %s', static::class));
        }

        if (!$this->name) {
            $this->name = static::class;
        }

        $this->id  = static::class;
        $this->url = sprintf(urldecode(App::backend()->url()->get('admin.plugin', ['p' => 'importExport', 'type' => '%s', 'module' => '%s'], '&')), $this->type, $this->id);
    }

    /**
     * Initializes the module.
     */
    public function init(): void
    {
    }

    /**
     * Sets the module information.
     */
    abstract protected function setInfo(): void;

    /**
     * Gets the module URL.
     *
     * @param   bool    $escape     The escape
     *
     * @return  string  The url.
     */
    final public function getURL(bool $escape = false): string
    {
        return $escape ? Html::escapeHTML($this->url) : $this->url;
    }

    /**
     * Processes the import/export.
     *
     * @param   string  $do     action
     */
    abstract public function process(string $do): void;

    /**
     * GUI for import/export module.
     */
    abstract public function gui(): void;

    /**
     * Return a progress bar.
     *
     * @param   float   $percent    The percent
     *
     * @return  string
     */
    protected function progressBar(float $percent): string
    {
        $percent = trim((string) max(ceil($percent), 100));

        return (new Div())
            ->class('ie-progress')
            ->items([
                (new Text('progress', $percent))
                    ->class('ie-progress')
                    ->id('file')
                    ->max(100)
                    ->value($percent),
            ])
        ->render();
    }

    /**
     * Return a hidden autosubmit input field.
     *
     * @return  string
     */
    protected function autoSubmit(): string
    {
        return (new Hidden(['autosubmit'], '1'))->render();
    }

    /**
     * Return a congratulation message.
     *
     * @return  string
     */
    protected function congratMessage()
    {
        return (new Set())->items([
            (new Text('h3', __('Congratulation!'))),
            (new Para())
                ->class('success')
                ->items([
                    (new Text(null, __('Your blog has been successfully imported. Welcome on Dotclear 2!'))),
                ]),
            (new Ul())->items([
                (new Li())->items([
                    (new Text('strong', (new Link())
                        ->href(App::backend()
                        ->url()->get('admin.post'))
                        ->text(__('Why don\'t you blog this now?'))
                    ->render())),
                ]),
                (new Li())->items([
                    (new Text(null, __('or') . ' ')),
                    (new Text(null, (new Link())
                        ->href(App::backend()
                        ->url()->get('admin.home'))
                        ->text(__('visit your dashboard'))
                    ->render())),
                ]),
            ]),
        ])
        ->render();
    }
}
