<?php
/**
 * @brief dcCKEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

header('Content-type: text/javascript');

if (!empty($_GET['context'])) {
    $context = $_GET['context'];
} else {
    $context = '';
}

$__extraPlugins = new ArrayObject();
$core->callBehavior('ckeditorExtraPlugins', $__extraPlugins, $context);
$extraPlugins = $__extraPlugins->getArrayCopy();

?>

(function($) {
    $.toolbarPopup = function toolbarPopup(url) {
        if (dotclear.admin_base_url != '') {
            var pos = url.indexOf(dotclear.admin_base_url);
            if (pos == -1) {
                url = dotclear.admin_base_url + url;
            }
        }

        var args = Array.prototype.slice.call(arguments);
        var width = 520, height = 420;
        if (args[1]!==undefined) {
            width = args[1].width || width;
            height = args[1].height || height;
        }

        var popup_params = 'alwaysRaised=yes,dependent=yes,toolbar=yes,';
        popup_params += 'height='+height+',width='+width+',menubar=no,resizable=yes,scrollbars=yes,status=no';
        var popup_link = window.open(url,'dc_popup', popup_params);
    };

    $.stripBaseURL = function stripBaseURL(url) {
        if (dotclear.base_url != '') {
            var pos = url.indexOf(dotclear.base_url);
            if (pos == 0) {
                url = url.substr(dotclear.base_url.length);
            }
        }

        return url;
    };

    /* Retrieve editor from popup */
    $.active_editor = null;
    $.getEditorName = function getEditorName() {
        return $.active_editor;
    }
    chainHandler(window,'onbeforeunload',function(e) {
        if (e == undefined && window.event) {
            e = window.event;
        }

        var editor = CKEDITOR.instances[$.getEditorName()];
        if (editor !== undefined && !confirmClosePage.formSubmit && editor.checkDirty()) {
            e.returnValue = confirmClosePage.prompt;
            return confirmClosePage.prompt;
        }
        return false;
    });
})(jQuery);

$(function() {
    /* By default ckeditor load related resources with a timestamp to avoid cache problem when upgrading itself
     * load_plugin_file.php does not allow other param that file to load (pf param), so remove timestamp
     */

    CKEDITOR.timestamp = '';

<?php if (!isset($dcckeditor_disable_native_spellchecker) || $dcckeditor_disable_native_spellchecker): ?>
    CKEDITOR.config.disableNativeSpellChecker = true;
<?php else: ?>
    CKEDITOR.config.disableNativeSpellChecker = false;
<?php endif;?>

    CKEDITOR.config.skin = 'dotclear,'+dotclear.dcckeditor_plugin_url+'/js/ckeditor-skins/dotclear/';
    CKEDITOR.config.baseHref = dotclear.base_url;
    CKEDITOR.config.height = '<?php echo $core->auth->getOption('edit_size') * 14; ?>px';

<?php if (!empty($dcckeditor_cancollapse_button)): ?>
    CKEDITOR.config.toolbarCanCollapse = true;
<?php endif;?>

    CKEDITOR.plugins.addExternal('entrylink',dotclear.dcckeditor_plugin_url+'/js/ckeditor-plugins/entrylink/');
    CKEDITOR.plugins.addExternal('dclink',dotclear.dcckeditor_plugin_url+'/js/ckeditor-plugins/dclink/');
    CKEDITOR.plugins.addExternal('media',dotclear.dcckeditor_plugin_url+'/js/ckeditor-plugins/media/');
    CKEDITOR.plugins.addExternal('img',dotclear.dcckeditor_plugin_url+'/js/ckeditor-plugins/img/');

<?php if (!empty($dcckeditor_textcolor_button) || !empty($dcckeditor_background_textcolor_button)): ?>
    // button add "More Colors..." can be added if colordialog plugin is enabled
    CKEDITOR.config.colorButton_enableMore = true;
<?php endif;?>

<?php
if (!empty($extraPlugins) && count($extraPlugins) > 0) {
    foreach ($extraPlugins as $plugin) {
        printf("\tCKEDITOR.plugins.addExternal('%s','%s');\n", $plugin['name'], $plugin['url']);
    }
}
?>
    if (dotclear.ckeditor_context === undefined || dotclear.ckeditor_tags_context[dotclear.ckeditor_context] === undefined) {
        return;
    }
    $(dotclear.ckeditor_tags_context[dotclear.ckeditor_context].join(',')).ckeditor({

<?php
$defautExtraPlugins = 'entrylink,dclink,media,justify,colorbutton,format,img';
if (!empty($extraPlugins) && count($extraPlugins) > 0) {
    foreach ($extraPlugins as $plugin) {
        $defautExtraPlugins .= ',' . $plugin['name'];
    }
}
?>
        extraPlugins: '<?php echo $defautExtraPlugins; ?>',

        keystrokes: [
            [ CKEDITOR.CTRL + (CKEDITOR.env.mac ? CKEDITOR.ALT : CKEDITOR.SHIFT) +
                dotclear.msg.link_accesskey.toUpperCase().charCodeAt(0),'dcLinkCommand' ],    // Ctrl+Alt+l
            [ CKEDITOR.CTRL + (CKEDITOR.env.mac ? CKEDITOR.ALT : CKEDITOR.SHIFT) +
                dotclear.msg.img_select_accesskey.toUpperCase().charCodeAt(0),'mediaCommand' ],    // Ctrl+Alt+m
        ],

<?php if (!empty($dcckeditor_format_select)): ?>
        // format tags

    <?php if (!empty($dcckeditor_format_tags)): ?>
        format_tags: '<?php echo $dcckeditor_format_tags; ?>',
    <?php else: ?>
        format_tags: 'p;h1;h2;h3;h4;h5;h6;pre;address',
    <?php endif;?>

        // following definition are needed to be specialized
        format_p: { element: 'p' },
        format_h1: { element: 'h1' },
        format_h2: { element: 'h2' },
        format_h3: { element: 'h3' },
        format_h4: { element: 'h4' },
        format_h5: { element: 'h5' },
        format_h6: { element: 'h6' },
        format_pre: { element: 'pre' },
        format_address: { element: 'address' },
<?php endif;?>

        entities: false,
        removeButtons: '',
        allowedContent: true,
        toolbar: [
            {
                name: 'basicstyles',
                items: [

<?php if (!empty($dcckeditor_format_select)): ?>
                    'Format',
<?php endif;?>

                    'Bold','Italic','Underline','Strike','Subscript','Superscript','Code','Blockquote',

<?php if (!empty($dcckeditor_list_buttons)): ?>
                    'NumberedList','BulletedList',
<?php endif;?>

                    'RemoveFormat'
                ]
            },

<?php if (!empty($dcckeditor_clipboard_buttons)): ?>
            {
                name: 'clipoard',
                items: ['Cut','Copy','Paste','PasteText','PasteFromWord']
            },
<?php endif;?>

<?php if (!empty($dcckeditor_alignment_buttons)): ?>
            {
                name: 'paragraph',
                items: ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']
            },
<?php endif;?>

<?php if (!empty($dcckeditor_table_button)): ?>
            {
                name: 'table',
                items: ['Table']
            },
<?php endif;?>

            {
                name: 'custom',
                items: [
                    'EntryLink','dcLink','Media','img','-',
                    'Source'

<?php if (!empty($dcckeditor_textcolor_button)): ?>
                    ,'TextColor'
<?php endif;?>

<?php if (!empty($dcckeditor_background_textcolor_button)): ?>
                    ,'BGColor'
<?php endif;?>
                ]
            },
            {
                name: 'special',
                items: [
                    'Maximize'
                ]
            },

<?php // add extra buttons comming from dotclear plugins
if (!empty($extraPlugins) && count($extraPlugins) > 0) {
    $extraPlugins_str = "{name: 'extra', items: [%s]},\n";
    $extra_icons      = '';
    foreach ($extraPlugins as $plugin) {
        $extra_icons .= sprintf("'%s',", $plugin['button']);
    }
    printf($extraPlugins_str, $extra_icons);
}
?>
        ]
    });

    CKEDITOR.on('instanceLoaded',function(e) {

        // Retrieve textarea element of the instance, then its line-height (in px) and rows values,
        // then apply line-height * rows (min = 6) to the inner height of the instance.

        var ta = document.getElementById(e.editor.name);
        if (ta !== undefined) {
            var ta_rows = ta.rows;
            var ta_line_height = parseFloat(window.getComputedStyle(ta,null).getPropertyValue('line-height'));
            if (ta_rows > 0 && ta_line_height > 0) {
                var ta_height = String(Math.max(ta_rows,6) * ta_line_height);
                e.editor.resize('100%',ta_height,true);
            }
        }

    });

    CKEDITOR.on('instanceReady',function(e) {
        if (typeof dotclear_htmlFontSize !== 'undefined') {
            e.editor.document.$.documentElement.style.setProperty('--html-font-size',dotclear_htmlFontSize);
        }

        if ($('label[for="post_excerpt"] input').attr('aria-label') == dotclear.img_minus_alt) {
            $('#cke_post_excerpt').removeClass('hide');
        } else {
            $('#cke_post_excerpt').addClass('hide');
        }

        $('#excerpt-area label').click(function() {
            $('#cke_post_excerpt').toggleClass('hide',$('#post_excerpt').hasClass('hide'));
        });

    });

    // @TODO: find a better way to retrieve active editor
    for (var id in CKEDITOR.instances) {
        CKEDITOR.instances[id].on('focus',function(e) {
            $.active_editor = e.editor.name;
        });
    }
});
