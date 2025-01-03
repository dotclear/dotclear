<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Color;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The widgets element handler.
 * @ingroup widgets
 *
 * Common widget properties
 *
 * @property    string $class
 * @property    bool   $content_only
 * @property    int    $homeonly
 * @property    bool   $offline
 * @property    string $title
 */
class WidgetsElement
{
    // Constants

    /**
     * Widget displayed on every page.
     *
     * @var     int     ALL_PAGES
     */
    public const ALL_PAGES = 0;

    /**
     * Widget displayed on home page only.
     *
     * @var     int     HOME_ONLY
     */
    public const HOME_ONLY = 1;

    /**
     * Widget displayed on every page but home page.
     *
     * @var     int     EXCEPT_HOME
     */
    public const EXCEPT_HOME = 2;

    /**
     * Widget callback.
     *
     * @var     null|callable   $public_callback
     */
    private $public_callback;

    /**
     * Widget append callback.
     *
     * @var     null|callable   $append_callback
     */
    public $append_callback;

    /**
     * Widget settings.
     *
     * @var     array<string, array<'title'|'type'|'value'|'options'|'opts', mixed>>   $settings
     */
    protected array $settings;

    /**
     * Constructs a new instance.
     *
     * @param   string          $id         The widget ID
     * @param   string          $name       The widget name
     * @param   callable        $callback   The widget callback
     * @param   string          $desc       The widget description
     */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        $callback,
        private readonly string $desc = ''
    ) {
        if (!is_callable($callback)) {  // @phpstan-ignore-line
            $widget = new ArrayObject(['id' => $id, 'callback' => $callback]);
            # --BEHAVIOR-- widgetGetCallback -- ArrayObject
            App::behavior()->callBehavior('widgetGetCallback', $widget);
            $callback = is_callable($widget['callback']) ? $widget['callback'] : null;
        }

        $this->public_callback = $callback;
    }

    /**
     * Get array of widget settings
     *
     * @param   int     $order  The order
     *
     * @return  array<string, mixed>
     */
    public function serialize(int $order): array
    {
        $values = [];
        foreach ($this->settings as $k => $v) {
            $values[$k] = $v['value'];
        }

        $values['id']    = $this->id;
        $values['order'] = $order;

        return $values;
    }

    /**
     * Get widget ID.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get widget name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get widget description.
     */
    public function desc(): string
    {
        return $this->desc;
    }

    /**
     * Gets the widget callback.
     */
    public function getCallback(): ?callable
    {
        return $this->public_callback;
    }

    /**
     * Call a widget callback.
     *
     * @param   mixed   $i
     *
     * @return  string
     */
    public function call($i = 0)
    {
        if (!is_null($this->public_callback)) {
            return call_user_func($this->public_callback, $this, $i);
        }

        return (new Note())->text('Callback not found for widget ' . $this->id)->render();
    }

    /**
     * Widget rendering tool.
     *
     * @param   bool    $content_only   The content only
     * @param   string  $class          The class
     * @param   string  $attr           The attribute
     * @param   string  $content        The content
     */
    public function renderDiv(bool $content_only, string $class, string $attr, string $content): string
    {
        if ($content_only) {
            return $content;
        }

        /*
         * widgetcontainerformat, if defined for the theme in his _define.php,
         * is a sprintf string format in which:
         * - %1$s is the class(es) affected to the container
         * - %2$s is the additional attributes affected to the container
         * - %3$s is the content of the widget
         *
         * Don't forget to set widgettitleformat and widgetsubtitleformat if necessary (see default rendering below)
        */
        $wtscheme = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'widgetcontainerformat');
        if (empty($wtscheme)) {
            $wtscheme = '<div class="%1$s" %2$s>%3$s</div>';
        }

        // Keep only unique classes
        $class = trim(implode(' ', array_unique(explode(' ', 'widget ' . $class))));

        return sprintf($wtscheme . "\n", Html::escapeHTML($class), $attr, $content);
    }

    /**
     * Render widget title.
     *
     * @param   null|string     $title  The title
     */
    public function renderTitle(?string $title): string
    {
        if (!$title) {
            return '';
        }

        $wtscheme = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'widgettitleformat');
        if (empty($wtscheme)) {
            $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
            /**
             * @todo should be reviewed as the default tpl set (mustek) may change in future
             *
             * Use H2 for mustek based themes and H3 for dotty based themes
             */
            $wtscheme = empty($tplset) || $tplset == App::config()->defaultTplset() ? '<h2>%s</h2>' : '<h3>%s</h3>';
        }

        return sprintf($wtscheme, $title);
    }

    /**
     * Render widget subtitle.
     *
     * @param   null|string     $title      The title
     * @param   bool            $render     The render
     *
     * @return  string
     */
    public function renderSubtitle(?string $title, $render = true)
    {
        if (!$title && $render) {
            return '';
        }

        $wtscheme = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'widgetsubtitleformat');
        if (empty($wtscheme)) {
            $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
            /**
             * @todo should be reviewed as the default tpl set (mustek) may change in future
             *
             * Use H3 for mustek based themes and H4 for dotty based themes
             */
            $wtscheme = empty($tplset) || $tplset == App::config()->defaultTplset() ? '<h3>%s</h3>' : '<h4>%s</h4>';
        }
        if (!$render) {
            return $wtscheme;
        }

        return sprintf($wtscheme, $title);
    }

    // Widget settings

    /**
     * Gets the specified setting value.
     *
     * @param   string  $n  The setting name
     *
     * @return  mixed
     */
    public function __get(string $n)
    {
        return $this->get($n);
    }

    /**
     * Gets the specified setting value.
     *
     * @param   string  $n  The setting name
     *
     * @return  mixed
     */
    public function get(string $n)
    {
        if (isset($this->settings[$n])) {
            return $this->settings[$n]['value'];
        }

        return null;
    }

    /**
     * Set the specified setting value.
     *
     * @param   string  $n  The setting name
     * @param   mixed   $v  The new value
     */
    public function __set(string $n, $v)
    {
        $this->set($n, $v);
    }

    /**
     * Set the specified setting value.
     *
     * @param   string  $n  The setting name
     * @param   mixed   $v  The new value
     */
    public function set(string $n, $v): void
    {
        if (isset($this->settings[$n])) {
            $this->settings[$n]['value'] = $v;
        }
    }

    /**
     * Store a setting.
     *
     * @param   string  $name   The name
     * @param   string  $title  The title
     * @param   mixed   $value  The value
     * @param   string  $type   The type
     */
    public function setting(string $name, string $title, $value, string $type = 'text'): self
    {
        $types = [
            // type (string) => list of items may be provided (boolean)
            'text'     => false,
            'textarea' => false,
            'check'    => false,
            'radio'    => true,
            'combo'    => true,
            'color'    => false,
            'email'    => false,
            'number'   => false,
        ];

        if (!array_key_exists($type, $types)) {
            return $this;
        }

        $index = 4; // 1st optional argument (after type)

        if ($types[$type] && func_num_args() > $index) {
            $options = func_get_arg($index);
            if (!is_array($options)) {
                return $this;
            }
            $index++;
        }

        // If any, the last argument should be an array (key → value) of opts
        if (func_num_args() > $index) {
            $opts = func_get_arg($index);
        }

        $this->settings[$name] = [
            'title' => $title,
            'type'  => $type,
            'value' => $value,
        ];

        if (isset($options)) {
            $this->settings[$name]['options'] = $options;
        }
        if (isset($opts)) {
            $this->settings[$name]['opts'] = $opts;
        }

        return $this;
    }

    /**
     * Get widget settings.
     *
     * @return  array<string, mixed>
     */
    public function settings(): array
    {
        return $this->settings;
    }

    /**
     * Get widget settings form.
     *
     * @param   string  $pr     The prefix
     * @param   int     $i      The index
     */
    public function formSettings(string $pr = '', int &$i = 0): string
    {
        $res = '';
        foreach ($this->settings as $id => $s) {
            $res .= $this->formSetting($id, $s, $pr, $i)->render();
            $i++;
        }

        return $res;
    }

    /**
     * Get a widget setting field.
     *
     * @param   string                  $id     The identifier
     * @param   array<string, mixed>    $s      The setting
     * @param   string                  $pr     The prefix
     * @param   int                     $i      The index
     */
    public function formSetting(string $id, array $s, string $pr = '', int &$i = 0): Set
    {
        $wfid  = 'wf-' . $i;
        $iname = $pr !== '' ? $pr . '[' . $id . ']' : $id;
        $class = (isset($s['opts']) && isset($s['opts']['class']) ? ' ' . $s['opts']['class'] : '');
        switch ($s['type']) {
            case 'text':
                $setting = (new Para())
                    ->items([
                        (new Input([$iname, $wfid]))
                            ->size(20)
                            ->maxlength(255)
                            ->value(Html::escapeHTML((string) $s['value']))
                            ->class(['maximal', $class])
                            ->lang(App::auth()->getInfo('user_lang'))
                            ->spellcheck(true)
                            ->label(new Label($s['title'], Label::IL_TF)),
                    ]);

                break;
            case 'textarea':
                $setting = (new Para())
                    ->items([
                        (new Textarea([$iname, $wfid]))
                            ->rows(30)
                            ->cols(8)
                            ->value(Html::escapeHTML($s['value']))
                            ->class(['maximal', $class])
                            ->lang(App::auth()->getInfo('user_lang'))
                            ->spellcheck(true)
                            ->label(new Label($s['title'], Label::IL_TF)),
                    ]);

                break;
            case 'check':
                $setting = (new Para())
                    ->items([
                        (new Hidden([$iname], '0')),
                        (new Checkbox([$iname, $wfid], (bool) $s['value']))
                            ->value('1')
                            ->class($class)
                            ->label(new Label($s['title'], Label::IL_FT)),
                    ]);

                break;
            case 'radio':
                $radios = [];
                if (!empty($s['options'])) {
                    foreach ($s['options'] as $k => $v) {
                        $radios[] = (new Radio([$iname, $wfid . '-' . $k], $s['value'] == $v[1]))
                            ->value($v[1])
                            ->class($class)
                            ->label(new Label($k, Label::IL_FT));
                    }
                }
                $setting = (new Para())
                    ->items($radios);

                break;
            case 'combo':
                $setting = (new Para())
                    ->items([
                        (new Select([$iname, $wfid]))
                            ->items($s['options'])
                            ->default((string) $s['value'])
                            ->class($class)
                            ->label(new Label($s['title'], Label::IL_TF)),
                    ]);

                break;
            case 'color':
                $setting = (new Para())
                    ->items([
                        (new Color([$iname, $wfid]))
                            ->value(Html::escapeHTML($s['value']))
                            ->class($class)
                            ->label(new Label($s['title'], Label::IL_TF)),
                    ]);

                break;
            case 'email':
                $setting = (new Para())
                    ->items([
                        (new Email([$iname, $wfid]))
                            ->value(Html::escapeHTML($s['value']))
                            ->autocomplete('email')
                            ->class($class)
                            ->label(new Label($s['title'], Label::IL_TF)),
                    ]);

                break;
            case 'number':
                $setting = (new Para())
                    ->items([
                        (new Number([$iname, $wfid]))
                            ->value($s['value'])
                            ->class($class)
                            ->label(new Label($s['title'], Label::IL_TF)),
                    ]);

                break;
            default:
                $setting = (new None());

                break;
        }

        return (new Set())
            ->items([$setting]);
    }

    // Widget helpers

    /**
     * Adds a title setting.
     *
     * @param   string  $title  The title
     */
    public function addTitle(string $title = ''): self
    {
        return $this->setting('title', __('Title (optional)') . ' :', $title);
    }

    /**
     * Adds a home only setting.
     *
     * @param   null|array<string, mixed>     $options
     */
    public function addHomeOnly(?array $options = null): self
    {
        $list = [
            __('All pages')           => self::ALL_PAGES,
            __('Home page only')      => self::HOME_ONLY,
            __('Except on home page') => self::EXCEPT_HOME, ];

        if ($options !== null) {
            $list = array_merge($list, $options);
        }

        return $this->setting(
            'homeonly',
            __('Display on:'),
            self::ALL_PAGES,
            'combo',
            $list
        );
    }

    /**
     * Check if the widget should be displayed, depending on its homeonly setting.
     *
     * @param   string  $type           The type
     * @param   int     $alt_not_home   Alternate not home test value
     * @param   int     $alt_home       Alternate home test value
     */
    public function checkHomeOnly(string $type, $alt_not_home = 1, $alt_home = 0): bool
    {
        return !(isset($this->settings['homeonly']) && isset($this->settings['homeonly']['value']) && ($this->settings['homeonly']['value'] == self::HOME_ONLY && !App::url()->isHome($type) && $alt_not_home || $this->settings['homeonly']['value'] == self::EXCEPT_HOME && (App::url()->isHome($type) || $alt_home)));
    }

    /**
     * Adds a content only setting.
     *
     * @param   int     $content_only   The content only flag
     */
    public function addContentOnly(int $content_only = 0): self
    {
        return $this->setting('content_only', __('Content only'), $content_only, 'check');
    }

    /**
     * Adds a class setting.
     *
     * @param   string  $class  The class
     */
    public function addClass(string $class = ''): self
    {
        return $this->setting('class', __('CSS class:'), $class);
    }

    /**
     * Adds an offline setting.
     *
     * @param   int     $offline    The offline flag
     */
    public function addOffline(int $offline = 0): self
    {
        return $this->setting('offline', __('Offline'), $offline, 'check');
    }

    /**
     * Determines if setting is offline.
     */
    public function isOffline(): bool
    {
        return (bool) ($this->settings['offline']['value'] ?? false);
    }
}
