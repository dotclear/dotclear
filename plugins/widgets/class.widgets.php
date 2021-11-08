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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcWidgets
{
    private $__widgets = [];

    public static function load($s)
    {
        $o = @unserialize(base64_decode($s));

        if ($o instanceof self) {
            return $o;
        }

        return self::loadArray($o, $GLOBALS['__widgets']);
    }

    public function store()
    {
        $serialized = [];
        foreach ($this->__widgets as $pos => $w) {
            $serialized[] = ($w->serialize($pos));
        }

        return base64_encode(serialize($serialized));
    }

    public function create($id, $name, $callback, $append_callback = null, $desc = '')
    {
        $this->__widgets[$id]                  = new dcWidgetExt($id, $name, $callback, $desc);
        $this->__widgets[$id]->append_callback = $append_callback;

        return $this->__widgets[$id];
    }

    public function append($widget)
    {
        if ($widget instanceof dcWidget) {
            if (is_callable($widget->append_callback)) {
                call_user_func($widget->append_callback, $widget);
            }
            $this->__widgets[] = $widget;
        }
    }

    public function isEmpty()
    {
        return count($this->__widgets) == 0;
    }

    public function elements($sorted = false)
    {
        if ($sorted) {
            uasort($this->__widgets, ['self', 'sort']);
        }

        return $this->__widgets;
    }

    public function __get($id)
    {
        if (!isset($this->__widgets[$id])) {
            return;
        }

        return $this->__widgets[$id];
    }

    public function __wakeup()
    {
        foreach ($this->__widgets as $i => $w) {
            if (!($w instanceof dcWidget)) {
                unset($this->__widgets[$i]);
            }
        }
    }

    public static function loadArray($A, $widgets)
    {
        if (!($widgets instanceof self)) {
            return false;
        }

        uasort($A, ['self', 'arraySort']);

        $result = new self();
        foreach ($A as $v) {
            if ($widgets->{$v['id']} != null) {
                $w = clone $widgets->{$v['id']};

                # Settings
                unset($v['id'], $v['order']);

                foreach ($v as $sid => $s) {
                    $w->{$sid} = $s;
                }

                $result->append($w);
            }
        }

        return $result;
    }

    private static function arraySort($a, $b)
    {
        if ($a['order'] == $b['order']) {
            return 0;
        }

        return $a['order'] > $b['order'] ? 1 : -1;
    }

    private static function sort($a, $b)
    {
        $c = dcUtils::removeDiacritics(mb_strtolower($a->name()));
        $d = dcUtils::removeDiacritics(mb_strtolower($b->name()));
        if ($c == $d) {
            return 0;
        }

        return ($c < $d) ? -1 : 1;
    }
}

class dcWidget
{
    private $id;
    private $name;
    private $desc;
    private $public_callback = null;
    public $append_callback  = null;
    private $settings        = [];

    public function serialize($order)
    {
        $values = [];
        foreach ($this->settings as $k => $v) {
            $values[$k] = $v['value'];
        }

        $values['id']    = $this->id;
        $values['order'] = $order;

        return $values;
    }

    public function __construct($id, $name, $callback, $desc = '')
    {
        $this->public_callback = $callback;
        $this->id              = $id;
        $this->name            = $name;
        $this->desc            = $desc;
    }

    public function id()
    {
        return $this->id;
    }

    public function name()
    {
        return $this->name;
    }

    public function desc()
    {
        return $this->desc;
    }

    public function getCallback()
    {
        return $this->public_callback;
    }

    public function call($i = 0)
    {
        if (is_callable($this->public_callback)) {
            return call_user_func($this->public_callback, $this, $i);
        }

        return '<p>Callback not found for widget ' . $this->id . '</p>';
    }

    /* Widget rendering tool
    --------------------------------------------------- */
    public function renderDiv($content_only, $class, $attr, $content)
    {
        if ($content_only) {
            return $content;
        }
        $ret = '<div class="widget' . ($class ? ' ' . html::escapeHTML($class) : '') . '"' . ($attr ? ' ' . $attr : '') . '>' . "\n";
        $ret .= $content . "\n";
        $ret .= '</div>' . "\n";

        return $ret;
    }

    public function renderTitle($title)
    {
        global $core;

        if (!$title) {
            return '';
        }

        $wtscheme = $core->themes->moduleInfo($core->blog->settings->system->theme, 'widgettitleformat');
        if (empty($wtscheme)) {
            $tplset = $core->themes->moduleInfo($core->blog->settings->system->theme, 'tplset');
            if (empty($tplset) || $tplset == DC_DEFAULT_TPLSET) {
                // Use H2 for mustek based themes
                $wtscheme = '<h2>%s</h2>';
            } else {
                // Use H3 for dotty based themes
                $wtscheme = '<h3>%s</h3>';
            }
        }
        $ret = sprintf($wtscheme, $title);

        return $ret;
    }

    public function renderSubtitle($title, $render = true)
    {
        global $core;

        if (!$title && $render) {
            return '';
        }

        $wtscheme = $core->themes->moduleInfo($core->blog->settings->system->theme, 'widgetsubtitleformat');
        if (empty($wtscheme)) {
            $tplset = $core->themes->moduleInfo($core->blog->settings->system->theme, 'tplset');
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

        $ret = sprintf($wtscheme, $title);

        return $ret;
    }

    /* Widget settings
    --------------------------------------------------- */
    public function __get($n)
    {
        if (isset($this->settings[$n])) {
            return $this->settings[$n]['value'];
        }
    }

    public function __set($n, $v)
    {
        if (isset($this->settings[$n])) {
            $this->settings[$n]['value'] = $v;
        }
    }

    public function setting($name, $title, $value, $type = 'text')
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
            'number'   => false
        ];

        if (!array_key_exists($type, $types)) {
            return false;
        }

        $index = 4; // 1st optional argument (after type)

        if ($types[$type] && func_num_args() > $index) {
            $options = func_get_arg($index);
            if (!is_array($options)) {
                return false;
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
            'value' => $value
        ];

        if (isset($options)) {
            $this->settings[$name]['options'] = $options;
        }
        if (isset($opts)) {
            $this->settings[$name]['opts'] = $opts;
        }

        return $this;
    }

    public function settings()
    {
        return $this->settings;
    }

    public function formSettings($pr = '', &$i = 0)
    {
        $res = '';
        foreach ($this->settings as $id => $s) {
            $res .= $this->formSetting($id, $s, $pr, $i);
            $i++;
        }

        return $res;
    }

    public function formSetting($id, $s, $pr = '', &$i = 0)
    {
        global $core;

        $res   = '';
        $wfid  = 'wf-' . $i;
        $iname = $pr ? $pr . '[' . $id . ']' : $id;
        $class = (isset($s['opts']) && isset($s['opts']['class']) ? ' ' . $s['opts']['class'] : '');
        switch ($s['type']) {
            case 'text':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::field([$iname, $wfid], 20, 255, [
                    'default'    => html::escapeHTML($s['value']),
                    'class'      => 'maximal' . $class,
                    'extra_html' => 'lang="' . $core->auth->getInfo('user_lang') . '" spellcheck="true"'
                ]) .
                '</p>';

                break;
            case 'textarea':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::textarea([$iname, $wfid], 30, 8, [
                    'default'    => html::escapeHTML($s['value']),
                    'class'      => 'maximal' . $class,
                    'extra_html' => 'lang="' . $core->auth->getInfo('user_lang') . '" spellcheck="true"'
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
                    'default' => $s['value']
                ]) .
                '</p>';

                break;
            case 'email':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::email([$iname, $wfid], [
                    'default'      => html::escapeHTML($s['value']),
                    'autocomplete' => 'email'
                ]) .
                '</p>';

                break;
            case 'number':
                $res .= '<p><label for="' . $wfid . '">' . $s['title'] . '</label> ' .
                form::number([$iname, $wfid], [
                    'default' => $s['value']
                ]) .
                '</p>';

                break;
        }

        return $res;
    }
}

class dcWidgetExt extends dcWidget
{
    public const ALL_PAGES   = 0; // Widget displayed on every page
    public const HOME_ONLY   = 1; // Widget displayed on home page only
    public const EXCEPT_HOME = 2; // Widget displayed on every page but home page

    public function addTitle($title = '')
    {
        return $this->setting('title', __('Title (optional)') . ' :', $title);
    }

    public function addHomeOnly()
    {
        return $this->setting(
            'homeonly',
            __('Display on:'),
            self::ALL_PAGES,
            'combo',
            [__('All pages') => self::ALL_PAGES, __('Home page only') => self::HOME_ONLY, __('Except on home page') => self::EXCEPT_HOME]
        );
    }

    public function checkHomeOnly($type, $alt_not_home = 1, $alt_home = 0)
    {
        global $core;

        /* @phpstan-ignore-next-line */
        if (($this->homeonly == self::HOME_ONLY && !$core->url->isHome($type) && $alt_not_home) || ($this->homeonly == self::EXCEPT_HOME && ($core->url->isHome($type) || $alt_home))) {
            return false;
        }

        return true;
    }

    public function addContentOnly($content_only = 0)
    {
        return $this->setting('content_only', __('Content only'), $content_only, 'check');
    }

    public function addClass($class = '')
    {
        return $this->setting('class', __('CSS class:'), $class);
    }

    public function addOffline($offline = 0)
    {
        return $this->setting('offline', __('Offline'), $offline, 'check');
    }
}
