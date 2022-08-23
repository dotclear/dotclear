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

include __DIR__ . '/_default_widgets.php';
require_once __DIR__ . '/_widgets_functions.php';

dcCore::app()->tpl->addValue('Widgets', ['publicWidgets', 'tplWidgets']);
dcCore::app()->tpl->addBlock('Widget', ['publicWidgets', 'tplWidget']);
dcCore::app()->tpl->addBlock('IfWidgets', ['publicWidgets', 'tplIfWidgets']);

class publicWidgets
{
    public static function tplWidgets($attr)
    {
        $type = $attr['type'] ?? '';

        # widgets to disable
        $disable = isset($attr['disable']) ? trim((string) $attr['disable']) : '';

        if ($type == '') {
            $res = "publicWidgets::widgetsHandler('nav','" . addslashes($disable) . "');" . "\n" .
            "   publicWidgets::widgetsHandler('extra','" . addslashes($disable) . "');" . "\n" .
            "   publicWidgets::widgetsHandler('custom','" . addslashes($disable) . "');" . "\n";
        } else {
            if (!in_array($type, ['nav', 'extra', 'custom'])) {
                $type = 'nav';
            }
            $res = "publicWidgets::widgetsHandler('" . addslashes($type) . "','" . addslashes($disable) . "');";
        }

        return '<?php ' . $res . ' ?>';
    }

    public static function widgetsHandler($type, $disable = '')
    {
        $wtype = 'widgets_' . $type;
        dcCore::app()->blog->settings->addNameSpace('widgets');
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

    public static function tplIfWidgets($attr, $content)
    {
        $type = $attr['type'] ?? '';

        # widgets to disable
        $disable = isset($attr['disable']) ? trim((string) $attr['disable']) : '';

        if ($type == '') {
            $res = "publicWidgets::ifWidgetsHandler('nav','" . addslashes($disable) . "') &&" . "\n" .
            "   publicWidgets::ifWidgetsHandler('extra','" . addslashes($disable) . "') &&" . "\n" .
            "   publicWidgets::ifWidgetsHandler('custom','" . addslashes($disable) . "')" . "\n";
        } else {
            if (!in_array($type, ['nav', 'extra', 'custom'])) {
                $type = 'nav';
            }
            $res = "publicWidgets::ifWidgetsHandler('" . addslashes($type) . "','" . addslashes($disable) . "')";
        }

        return '<?php if(' . $res . ') : ?>' . $content . '<?php endif; ?>';
    }

    public static function ifWidgetsHandler($type)
    {
        $wtype = 'widgets_' . $type;
        dcCore::app()->blog->settings->addNameSpace('widgets');
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

    private static function defaultWidgets($type)
    {
        $w = new dcWidgets();

        if (isset(dcCore::app()->default_widgets[$type])) {
            $w = dcCore::app()->default_widgets[$type];
        }

        return $w;
    }

    public static function tplWidget($attr, $content)
    {
        if (!isset($attr['id']) || !(dcCore::app()->widgets->{$attr['id']} instanceof dcWidget)) {
            return;
        }

        # We change tpl:lang syntax, we need it
        $content = preg_replace('/\{\{tpl:lang\s+(.*?)\}\}/msu', '{tpl:lang $1}', $content);

        # We remove every {{tpl:
        $content = preg_replace('/\{\{tpl:.*?\}\}/msu', '', $content);

        return
        "<?php publicWidgets::widgetHandler('" . addslashes($attr['id']) . "','" . str_replace("'", "\\'", $content) . "'); ?>";
    }

    public static function widgetHandler($id, $xml)
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

        $w = clone dcCore::app()->widgets->{$id};

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
            $w->{$setting} = preg_replace_callback('/\{tpl:lang (.*?)\}/msu', fn ($m) => __($m[1]), $text);
        }

        echo $w->call(0);
    }
}
