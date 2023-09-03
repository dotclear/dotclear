<?php
/**
 * @brief widgets, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\widgets;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Html;
use form;

class WidgetsElement
{
    // Constants

    public const ALL_PAGES   = 0; // Widget displayed on every page
    public const HOME_ONLY   = 1; // Widget displayed on home page only
    public const EXCEPT_HOME = 2; // Widget displayed on every page but home page
    /**
     * Widget ID
     */
    private string $id;

    /**
     * Widget name
     */
    private string $name;

    /**
     * Widget description
     */
    private string $desc;

    /**
     * Widget callback
     *
     * @var null|callable
     */
    private $public_callback = null;

    /**
     * Widget append callback
     *
     * @var null|callable
     */
    public $append_callback = null;

    /**
     * Widget settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Get array of widget settings
     *
     * @param      int  $order  The order
     *
     * @return     array
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
     * Constructs a new instance.
     *
     * @param      string           $id        The identifier
     * @param      string           $name      The name
     * @param      callable|array   $callback  The callback
     * @param      string           $desc      The description
     */
    public function __construct(string $id, string $name, $callback, string $desc = '')
    {
        if (!is_callable($callback)) {
            $widget = new ArrayObject(['id' => $id, 'callback' => $callback]);
            # --BEHAVIOR-- widgetGetCallback -- ArrayObject
            App::behavior()->callBehavior('widgetGetCallback', $widget);
            $callback = is_callable($widget['callback']) ? $widget['callback'] : null;
        }

        $this->public_callback = $callback;
        $this->id              = $id;
        $this->name            = $name;
        $this->desc            = $desc;
    }

    /**
     * Get widget ID
     *
     * @return     string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get widget name
     *
     * @return     string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get widget description
     *
     * @return     string
     */
    public function desc(): string
    {
        return $this->desc;
    }

    /**
     * Gets the widget callback.
     *
     * @return     null|callable  The callback.
     */
    public function getCallback(): ?callable
    {
        return $this->public_callback;
    }

    /**
     * Call a widget callback
     *
     * @param      mixed     $i
     *
     * @return     string
     */
    public function call($i = 0)
    {
        if (!is_null($this->public_callback)) {
            return call_user_func($this->public_callback, $this, $i);
        }

        return '<p>Callback not found for widget ' . $this->id . '</p>';
    }

    /**
     * Widget rendering tool
     *
     * @param      bool    $content_only  The content only
     * @param      string  $class         The class
     * @param      string  $attr          The attribute
     * @param      string  $content       The content
     *
     * @return     string
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
        $class = trim(implode(' ', array_unique(explode(' ', 'widget' . ' ' . $class))));

        return sprintf($wtscheme . "\n", Html::escapeHTML($class), $attr, $content);
    }

    /**
     * Render widget title
     *
     * @param      null|string  $title  The title
     *
     * @return     string
     */
    public function renderTitle(?string $title): string
    {
        if (!$title) {
            return '';
        }

        $wtscheme = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'widgettitleformat');
        if (empty($wtscheme)) {
            $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
            if (empty($tplset) || $tplset == DC_DEFAULT_TPLSET) {
                // Use H2 for mustek based themes
                $wtscheme = '<h2>%s</h2>';
            } else {
                // Use H3 for dotty based themes
                $wtscheme = '<h3>%s</h3>';
            }
        }

        return sprintf($wtscheme, $title);
    }

    /**
     * Render widget subtitle
     *
     * @param      null|string  $title   The title
     * @param      bool         $render  The render
     *
     * @return     string
     */
    public function renderSubtitle(?string $title, $render = true)
    {
        if (!$title && $render) {
            return '';
        }

        $wtscheme = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'widgetsubtitleformat');
        if (empty($wtscheme)) {
            $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
            if (empty($tplset) || $tplset == DC_DEFAULT_TPLSET) {
                // Use H2 for mustek based themes
                $wtscheme = '<h3>%s</h3>';
            } else {
                // Use H3 for dotty based themes
                $wtscheme = '<h4>%s</h4>';
            }
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
     * @param      string  $n      The setting name
     *
     * @return     mixed
     */
    public function __get(string $n)
    {
        if (isset($this->settings[$n])) {
            return $this->settings[$n]['value'];
        }
    }

    /**
     * Set the specified setting value
     *
     * @param      string  $n      The setting name
     * @param      mixed   $v      The new value
     */
    public function __set(string $n, $v)
    {
        if (isset($this->settings[$n])) {
            $this->settings[$n]['value'] = $v;
        }
    }

    /**
     * Store a setting
     *
     * @param      string     $name             The name
     * @param      string     $title            The title
     * @param      mixed      $value            The value
     * @param      string     $type             The type
     *
     * @return     self
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
     * Get widget settings
     *
     * @return     array
     */
    public function settings(): array
    {
        return $this->settings;
    }

    /**
     * Get widget settings form
     *
     * @param      string  $pr     The prefix
     * @param      int     $i      The index
     *
     * @return     string
     */
    public function formSettings(string $pr = '', int &$i = 0): string
    {
        $res = '';
        foreach ($this->settings as $id => $s) {
            $res .= $this->formSetting($id, $s, $pr, $i);
            $i++;
        }

        return $res;
    }

    /**
     * Get a widget setting field
     *
     * @param      string       $id     The identifier
     * @param      array        $s      The setting
     * @param      string       $pr     The prefix
     * @param      int          $i      The index
     *
     * @return     string
     */
    public function formSetting(string $id, array $s, string $pr = '', int &$i = 0): string
    {
        $res   = '';
        $wfid  = 'wf-' . $i;
        $iname = $pr ? $pr . '[' . $id . ']' : $id;
        $class = (isset($s['opts']) && isset($s['opts']['class']) ? ' ' . $s['opts']['class'] : '');
        switch ($s['type']) {
            case 'text':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::field([$iname, $wfid], 20, 255, [
                    'default'    => Html::escapeHTML((string) $s['value']),
                    'class'      => 'maximal' . $class,
                    'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]) .
                '</p>';

                break;
            case 'textarea':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::textarea([$iname, $wfid], 30, 8, [
                    'default'    => Html::escapeHTML($s['value']),
                    'class'      => 'maximal' . $class,
                    'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]) .
                '</p>';

                break;
            case 'check':
                $res .= '<p>' . form::hidden([$iname], '0') .
                '<label class="classic" for="' . $wfid . '">' .
                form::checkbox([$iname, $wfid], '1', $s['value'], $class) . ' ' . $s['title'] .
                '</label></p>';

                break;
            case 'radio':
                $res .= '<p>' . ($s['title'] ? '<label class="classic">' . $s['title'] . '</label><br/>' : '');
                if (!empty($s['options'])) {
                    foreach ($s['options'] as $k => $v) {
                        $res .= $k > 0 ? '<br/>' : '';
                        $res .= '<label class="classic" for="' . $wfid . '-' . $k . '">' .
                        form::radio([$iname, $wfid . '-' . $k], $v[1], $s['value'] == $v[1], $class) . ' ' . $v[0] .
                            '</label>';
                    }
                }
                $res .= '</p>';

                break;
            case 'combo':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::combo([$iname, $wfid], $s['options'], $s['value'], $class) .
                '</p>';

                break;
            case 'color':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::color([$iname, $wfid], [
                    'default' => $s['value'],
                ]) .
                '</p>';

                break;
            case 'email':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::email([$iname, $wfid], [
                    'default'      => Html::escapeHTML($s['value']),
                    'autocomplete' => 'email',
                ]) .
                '</p>';

                break;
            case 'number':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::number([$iname, $wfid], [
                    'default' => $s['value'],
                ]) .
                '</p>';

                break;
        }

        return $res;
    }

    // Widget helpers

    /**
     * Adds a title setting.
     *
     * @param      string  $title  The title
     *
     * @return     self
     */
    public function addTitle(string $title = ''): self
    {
        return $this->setting('title', __('Title (optional)') . ' :', $title);
    }

    /**
     * Adds a home only setting.
     *
     * @return     self
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
     * Check if the widget should be displayed, depending on its homeonly setting
     *
     * @param      string  $type          The type
     * @param      int     $alt_not_home  Alternate not home test value
     * @param      int     $alt_home      Alternate home test value
     *
     * @return     bool
     */
    public function checkHomeOnly($type, $alt_not_home = 1, $alt_home = 0)
    {
        if (isset($this->settings['homeonly']) && isset($this->settings['homeonly']['value'])) {
            if (($this->settings['homeonly']['value'] == self::HOME_ONLY && !App::url()->isHome($type) && $alt_not_home) || ($this->settings['homeonly']['value'] == self::EXCEPT_HOME && (App::url()->isHome($type) || $alt_home))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adds a content only setting.
     *
     * @param      int     $content_only  The content only flag
     *
     * @return     self
     */
    public function addContentOnly(int $content_only = 0): self
    {
        return $this->setting('content_only', __('Content only'), $content_only, 'check');
    }

    /**
     * Adds a class setting.
     *
     * @param      string  $class  The class
     *
     * @return     self
     */
    public function addClass(string $class = ''): self
    {
        return $this->setting('class', __('CSS class:'), $class);
    }

    /**
     * Adds an offline setting.
     *
     * @param      int     $offline  The offline flag
     *
     * @return     self
     */
    public function addOffline(int $offline = 0): self
    {
        return $this->setting('offline', __('Offline'), $offline, 'check');
    }

    /**
     * Determines if setting is offline.
     *
     * @return     bool  True if offline, False otherwise.
     */
    public function isOffline(): bool
    {
        return (bool) ($this->settings['offline']['value'] ?? false);
    }
}
