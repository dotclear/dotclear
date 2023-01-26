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
class publicWidgets
{
    /**
     * tpl:Widgets [attributes] : Displays trackback title (tpl value)
     *
     * attributes:
     *
     *      - type                Type of widgets (nav, extra, custom), default to all
     *      - disable             Comma separated list of widget to disable, default to empty
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function tplWidgets($attr)
    {
        $type = $attr['type'] ?? '';

        # widgets to disable
        $disable = isset($attr['disable']) ? trim((string) $attr['disable']) : '';

        if ($type === '') {
            $res = "publicWidgets::widgetsHandler(defaultWidgets::WIDGETS_NAV,'" . addslashes($disable) . "');" . "\n" .
            "   publicWidgets::widgetsHandler(defaultWidgets::WIDGETS_EXTRA,'" . addslashes($disable) . "');" . "\n" .
            "   publicWidgets::widgetsHandler(defaultWidgets::WIDGETS_CUSTOM,'" . addslashes($disable) . "');" . "\n";
        } else {
            if (!in_array($type, [defaultWidgets::WIDGETS_NAV, defaultWidgets::WIDGETS_EXTRA, defaultWidgets::WIDGETS_CUSTOM])) {
                $type = defaultWidgets::WIDGETS_NAV;
            }
            $res = "publicWidgets::widgetsHandler('" . addslashes($type) . "','" . addslashes($disable) . "');";
        }

        return '<?php ' . $res . ' ?>';
    }

    public static function widgetsHandler(string $type, string $disable = '')
    {
        $wtype   = 'widgets_' . $type;
        $widgets = dcCore::app()->blog->settings->widgets->{$wtype};

        if (!$widgets) {
            // If widgets value is empty, get defaults
            $widgets = self::defaultWidgets($type);
        } else {
            // Otherwise, load widgets
            $widgets = dcWidgets::load($widgets);
        }

        if ($widgets->isEmpty()) {
            // Widgets are empty, don't show anything
            return;
        }

        $disable = preg_split('/\s*,\s*/', $disable, -1, PREG_SPLIT_NO_EMPTY);
        $disable = array_flip($disable);

        foreach ($widgets->elements() as $k => $w) {
            if (isset($disable[$w->id()])) {
                continue;
            }
            echo $w->call($k);
        }
    }

    /**
     * tpl:IfWidgets : Include content only if test succeed (tpl block)
     *
     * attributes:
     *
     *      - type                Type of widgets (nav, extra, custom), default to all
     *      - disable             Comma separated list of widget to disable, default to empty
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function tplIfWidgets(ArrayObject $attr, string $content): string
    {
        $type = $attr['type'] ?? '';

        # widgets to disable
        $disable = isset($attr['disable']) ? trim((string) $attr['disable']) : '';

        if ($type == '') {
            $res = "publicWidgets::ifWidgetsHandler(defaultWidgets::WIDGETS_NAV,'" . addslashes($disable) . "') &&" . "\n" .
            "   publicWidgets::ifWidgetsHandler(defaultWidgets::WIDGETS_EXTRA,'" . addslashes($disable) . "') &&" . "\n" .
            "   publicWidgets::ifWidgetsHandler(defaultWidgets::WIDGETS_CUSTOM,'" . addslashes($disable) . "')" . "\n";
        } else {
            if (!in_array($type, [defaultWidgets::WIDGETS_NAV, defaultWidgets::WIDGETS_EXTRA, defaultWidgets::WIDGETS_CUSTOM])) {
                $type = defaultWidgets::WIDGETS_NAV;
            }
            $res = "publicWidgets::ifWidgetsHandler('" . addslashes($type) . "','" . addslashes($disable) . "')";
        }

        return '<?php if(' . $res . ') : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * tplIfWidgets helper
     *
     * @param      string  $type   The type
     *
     * @return     bool
     */
    public static function ifWidgetsHandler(string $type): bool
    {
        $wtype   = 'widgets_' . $type;
        $widgets = dcCore::app()->blog->settings->widgets->{$wtype};

        if (!$widgets) {
            // If widgets value is empty, get defaults
            $widgets = self::defaultWidgets($type);
        } else {
            // Otherwise, load widgets
            $widgets = dcWidgets::load($widgets);
        }

        return (!$widgets->isEmpty());
    }

    /**
     * Get default widgets list helper
     *
     * @param      string     $type   The type
     *
     * @return     dcWidgets
     */
    private static function defaultWidgets(string $type): dcWidgets
    {
        $widgets = new dcWidgets();

        if (isset(dcCore::app()->default_widgets[$type])) {
            $widgets = dcCore::app()->default_widgets[$type];
        }

        return $widgets;
    }

    /**
     * tpl:Widget [attributes] : Includes a widget (tpl block)
     *
     * attributes:
     *
     *      - id      widget ID
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content (widget optional settings)
     *
     * @return     string
     */
    public static function tplWidget(ArrayObject $attr, string $content): string
    {
        if (!isset($attr['id']) || !(dcCore::app()->widgets->{$attr['id']} instanceof dcWidget)) {
            return '';
        }

        # We change tpl:lang syntax, we need it
        $content = preg_replace('/\{\{tpl:lang\s+(.*?)\}\}/msu', '{tpl:lang $1}', $content);

        # We remove every {{tpl:
        $content = preg_replace('/\{\{tpl:.*?\}\}/msu', '', $content);

        return
        "<?php publicWidgets::widgetHandler('" . addslashes($attr['id']) . "','" . str_replace("'", "\\'", $content) . "'); ?>";
    }

    /**
     * Render widget
     *
     * @param      string  $id     The widget identifier
     * @param      string  $xml    The xml (widget optional settings)
     */
    public static function widgetHandler(string $id, $xml): void
    {
        if (!(dcCore::app()->widgets->{$id} instanceof dcWidget)) {
            return;
        }

        $xml = '<?xml version="1.0" encoding="utf-8" ?><widget>' . $xml . '</widget>';
        $xml = @simplexml_load_string($xml);
        if (!($xml instanceof SimpleXMLElement)) {
            echo 'Invalid widget XML fragment';

            return;
        }

        $widget = clone dcCore::app()->widgets->{$id};

        foreach ($xml->setting as $e) {
            if (empty($e['name'])) {
                continue;
            }

            $setting = (string) $e['name'];
            if ($e->count() > 0) {
                $text = preg_replace('#^<setting[^>]*>(.*)</setting>$#msu', '\1', (string) $e->asXML());
            } else {
                $text = $e;
            }
            $widget->{$setting} = preg_replace_callback('/\{tpl:lang (.*?)\}/msu', fn ($m) => __($m[1]), $text);
        }

        echo $widget->call(0);
    }
}
