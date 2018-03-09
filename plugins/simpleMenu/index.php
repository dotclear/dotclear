<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

dcPage::check('admin');

$page_title = __('Simple menu');

# Url de base
$p_url = $core->adminurl->get('admin.plugin.simpleMenu');

# Url du blog
$blog_url = html::stripHostURL($core->blog->url);

# Liste des catégories
$categories_label = array();
$rs               = $core->blog->getCategories(array('post_type' => 'post'));
$categories_combo = dcAdminCombos::getCategoriesCombo($rs, false, true);
$rs->moveStart();
while ($rs->fetch()) {
    $categories_label[$rs->cat_url] = html::escapeHTML($rs->cat_title);
}

# Liste des langues utilisées
$langs_combo = dcAdminCombos::getLangscombo(
    $core->blog->getLangs(array('order' => 'asc'))
);

# Liste des mois d'archive
$rs           = $core->blog->getDates(array('type' => 'month'));
$months_combo = array_merge(
    array(__('All months') => '-'),
    dcAdmincombos::getDatesCombo($rs)
);

$first_year = $last_year = 0;
while ($rs->fetch()) {
    if (($first_year == 0) || ($rs->year() < $first_year)) {
        $first_year = $rs->year();
    }

    if (($last_year == 0) || ($rs->year() > $last_year)) {
        $last_year = $rs->year();
    }

}
unset($rs);

# Liste des pages -- Doit être pris en charge plus tard par le plugin ?
$pages_combo = array();
try {
    $rs = $core->blog->getPosts(array('post_type' => 'page'));
    while ($rs->fetch()) {
        $pages_combo[$rs->post_title] = $rs->getURL();
    }
    unset($rs);
} catch (Exception $e) {}

# Liste des tags -- Doit être pris en charge plus tard par le plugin ?
$tags_combo = array();
try {
    $rs                         = $core->meta->getMetadata(array('meta_type' => 'tag'));
    $tags_combo[__('All tags')] = '-';
    while ($rs->fetch()) {
        $tags_combo[$rs->meta_id] = $rs->meta_id;
    }
    unset($rs);
} catch (Exception $e) {}

# Liste des types d'item de menu
$items         = new ArrayObject();
$items['home'] = new ArrayObject(array(__('Home'), false));

if (count($langs_combo) > 1) {
    $items['lang'] = new ArrayObject(array(__('Language'), true));
}
if (count($categories_combo)) {
    $items['category'] = new ArrayObject(array(__('Category'), true));
}
if (count($months_combo) > 1) {
    $items['archive'] = new ArrayObject(array(__('Archive'), true));
}
if ($core->plugins->moduleExists('pages')) {
    if (count($pages_combo)) {
        $items['pages'] = new ArrayObject(array(__('Page'), true));
    }

}
if ($core->plugins->moduleExists('tags')) {
    if (count($tags_combo) > 1) {
        $items['tags'] = new ArrayObject(array(__('Tags'), true));
    }

}

# --BEHAVIOR-- adminSimpleMenuAddType
# Should add an item to $items[<id>] as an array(<label>,<optional step (true or false)>)
$core->callBehavior('adminSimpleMenuAddType', $items);

$items['special'] = new ArrayObject(array(__('User defined'), false));

$items_combo = array();
foreach ($items as $k => $v) {
    $items_combo[$v[0]] = $k;
}

# Lecture menu existant
$menu = $core->blog->settings->system->get('simpleMenu');
if (!is_array($menu)) {
    $menu = array();
}

# Récupération état d'activation du menu
$menu_active = (boolean) $core->blog->settings->system->simpleMenu_active;

// Saving new configuration
if (!empty($_POST['saveconfig'])) {
    try
    {
        $menu_active = (empty($_POST['active'])) ? false : true;
        $core->blog->settings->system->put('simpleMenu_active', $menu_active, 'boolean');
        $core->blog->triggerBlog();

        // All done successfully, return to menu items list
        dcPage::addSuccessNotice(__('Configuration successfully updated.'));
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
} else {
    # Récupération paramètres postés
    $item_type        = isset($_POST['item_type']) ? $_POST['item_type'] : '';
    $item_select      = isset($_POST['item_select']) ? $_POST['item_select'] : '';
    $item_label       = isset($_POST['item_label']) ? $_POST['item_label'] : '';
    $item_descr       = isset($_POST['item_descr']) ? $_POST['item_descr'] : '';
    $item_url         = isset($_POST['item_url']) ? $_POST['item_url'] : '';
    $item_targetBlank = isset($_POST['item_targetBlank']) ? (empty($_POST['item_targetBlank'])) ? false : true : false;
    # Traitement
    $step = (!empty($_GET['add']) ? (integer) $_GET['add'] : 0);
    if (($step > 4) || ($step < 0)) {
        $step = 0;
    }

    if ($step) {

        # Récupération libellés des choix
        $item_type_label = isset($items[$item_type]) ? $items[$item_type][0] : '';

        switch ($step) {
            case 1:
                // First step, menu item type to be selected
                $item_type = $item_select = '';
                break;
            case 2:
                if ($items[$item_type][1]) {
                    // Second step (optional), menu item sub-type to be selected
                    $item_select = '';
                    break;
                }
            case 3:
                // Third step, menu item attributes to be changed or completed if necessary
                $item_select_label = '';
                $item_label        = __('Label');
                $item_descr        = __('Description');
                $item_url          = $blog_url;
                switch ($item_type) {
                    case 'home':
                        $item_label = __('Home');
                        $item_descr = __('Recent posts');
                        break;
                    case 'lang':
                        $item_select_label = array_search($item_select, $langs_combo);
                        $item_label        = $item_select_label;
                        $item_descr        = sprintf(__('Switch to %s language'), $item_select_label);
                        $item_url .= $core->url->getURLFor('lang', $item_select);
                        break;
                    case 'category':
                        $item_select_label = $categories_label[$item_select];
                        $item_label        = $item_select_label;
                        $item_descr        = __('Recent Posts from this category');
                        $item_url .= $core->url->getURLFor('category', $item_select);
                        break;
                    case 'archive':
                        $item_select_label = array_search($item_select, $months_combo);
                        if ($item_select == '-') {
                            $item_label = __('Archives');
                            $item_descr = $first_year . ($first_year != $last_year ? ' - ' . $last_year : '');
                            $item_url .= $core->url->getURLFor('archive');
                        } else {
                            $item_label = $item_select_label;
                            $item_descr = sprintf(__('Posts from %s'), $item_select_label);
                            $item_url .= $core->url->getURLFor('archive', substr($item_select, 0, 4) . '/' . substr($item_select, -2));
                        }
                        break;
                    case 'pages':
                        $item_select_label = array_search($item_select, $pages_combo);
                        $item_label        = $item_select_label;
                        $item_descr        = '';
                        $item_url          = html::stripHostURL($item_select);
                        break;
                    case 'tags':
                        $item_select_label = array_search($item_select, $tags_combo);
                        if ($item_select == '-') {
                            $item_label = __('All tags');
                            $item_descr = '';
                            $item_url .= $core->url->getURLFor('tags');
                        } else {
                            $item_label = $item_select_label;
                            $item_descr = sprintf(__('Recent posts for %s tag'), $item_select_label);
                            $item_url .= $core->url->getURLFor('tag', $item_select);
                        }
                        break;
                    case 'special':
                        break;
                    default:
                        # --BEHAVIOR-- adminSimpleMenuBeforeEdit
                        # Should modify if necessary $item_label, $item_descr and $item_url
                        # Should set if necessary $item_select_label (displayed on further admin step only)
                        $core->callBehavior('adminSimpleMenuBeforeEdit', $item_type, $item_select,
                            array(&$item_label, &$item_descr, &$item_url, &$item_select_label));
                        break;
                }
                break;
            case 4:
                // Fourth step, menu item to be added
                try {
                    if (($item_label != '') && ($item_url != '')) {
                        // Add new item menu in menu array
                        $menu[] = array(
                            'label'       => $item_label,
                            'descr'       => $item_descr,
                            'url'         => $item_url,
                            'targetBlank' => $item_targetBlank
                        );

                        // Save menu in blog settings
                        $core->blog->settings->system->put('simpleMenu', $menu);
                        $core->blog->triggerBlog();

                        // All done successfully, return to menu items list
                        dcPage::addSuccessNotice(__('Menu item has been successfully added.'));
                        http::redirect($p_url);
                    } else {
                        $step              = 3;
                        $item_select_label = $item_label;
                        dcPage::addErrorNotice(__('Label and URL of menu item are mandatory.'));
                    }
                } catch (Exception $e) {
                    $core->error->add($e->getMessage());
                }
                break;
        }
    } else {

        # Remove selected menu items
        if (!empty($_POST['removeaction'])) {
            try {
                if (!empty($_POST['items_selected'])) {
                    foreach ($_POST['items_selected'] as $k => $v) {
                        $menu[$v]['label'] = '';
                    }
                    $newmenu = array();
                    foreach ($menu as $k => $v) {
                        if ($v['label']) {
                            $newmenu[] = array(
                                'label'       => $v['label'],
                                'descr'       => $v['descr'],
                                'url'         => $v['url'],
                                'targetBlank' => $v['targetBlank']
                            );
                        }
                    }
                    $menu = $newmenu;
                    // Save menu in blog settings
                    $core->blog->settings->system->put('simpleMenu', $menu);
                    $core->blog->triggerBlog();

                    // All done successfully, return to menu items list
                    dcPage::addSuccessNotice(__('Menu items have been successfully removed.'));
                    http::redirect($p_url);
                } else {
                    throw new Exception(__('No menu items selected.'));
                }
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }

        # Update menu items
        if (!empty($_POST['updateaction'])) {
            try {
                foreach ($_POST['items_label'] as $k => $v) {
                    if (!$v) {
                        throw new Exception(__('Label is mandatory.'));
                    }

                }
                foreach ($_POST['items_url'] as $k => $v) {
                    if (!$v) {
                        throw new Exception(__('URL is mandatory.'));
                    }

                }
                $newmenu = array();
                for ($i = 0; $i < count($_POST['items_label']); $i++) {
                    $newmenu[] = array(
                        'label'       => $_POST['items_label'][$i],
                        'descr'       => $_POST['items_descr'][$i],
                        'url'         => $_POST['items_url'][$i],
                        'targetBlank' => (empty($_POST['items_targetBlank' . $i])) ? false : true
                    );
                }
                $menu = $newmenu;
                // Save menu in blog settings
                $core->blog->settings->system->put('simpleMenu', $menu);
                $core->blog->triggerBlog();

                // All done successfully, return to menu items list
                dcPage::addSuccessNotice(__('Menu items have been successfully updated.'));
                http::redirect($p_url);
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }

        # Order menu items
        $order = array();
        if (empty($_POST['im_order']) && !empty($_POST['order'])) {
            $order = $_POST['order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['im_order'])) {
            $order = $_POST['im_order'];
            if (substr($order, -1) == ',') {
                $order = substr($order, 0, strlen($order) - 1);
            }
            $order = explode(',', $order);
        }

        if (!empty($_POST['updateaction']) && !empty($order)) {
            try {
                $newmenu = array();
                foreach ($order as $i => $k) {
                    $newmenu[] = array(
                        'label' => $menu[$k]['label'],
                        'descr' => $menu[$k]['descr'],
                        'url'   => $menu[$k]['url']);
                }
                $menu = $newmenu;
                // Save menu in blog settings
                $core->blog->settings->system->put('simpleMenu', $menu);
                $core->blog->triggerBlog();

                // All done successfully, return to menu items list
                dcPage::addSuccessNotice(__('Menu items have been successfully updated.'));
                http::redirect($p_url);
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }

    }
}

# Display
?>

<html>
<head>
    <title><?php echo $page_title; ?></title>
    <?php
$core->auth->user_prefs->addWorkspace('accessibility');
if (!$core->auth->user_prefs->accessibility->nodragdrop) {
    echo
    dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
    dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
    dcPage::jsLoad(dcPage::getPF('simpleMenu/js/simplemenu.js'));
}
echo dcPage::jsConfirmClose('settings', 'menuitemsappend', 'additem', 'menuitems');
?>
</head>

<body>

<?php

if ($step) {
    switch ($step) {
        case 1:
            $step_label = __('Step #1');
            break;
        case 2:
            if ($items[$item_type][1]) {
                $step_label = __('Step #2');
                break;
            }
        case 3:
            if ($items[$item_type][1]) {
                $step_label = __('Step #3');
            } else {
                $step_label = __('Step #2');
            }
            break;
    }
    echo dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            $page_title                         => $p_url,
            __('Add item')                      => '',
            $step_label                         => ''
        ),
        array(
            'hl_pos' => -2)
    ) .
    dcPage::notices();
} else {
    echo dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            $page_title                         => ''
        )) .
    dcPage::notices();
}

if ($step) {
    // Formulaire d'ajout d'un item
    switch ($step) {
        case 1:
            // Selection du type d'item
            echo '<form id="additem" action="' . $p_url . '&amp;add=2" method="post">';
            echo '<fieldset><legend>' . __('Select type') . '</legend>';
            echo '<p class="field"><label for="item_type" class="classic">' . __('Type of item menu:') . '</label>' . form::combo('item_type', $items_combo) . '</p>';
            echo '<p>' . $core->formNonce() . '<input type="submit" name="appendaction" value="' . __('Continue...') . '" />' . '</p>';
            echo '</fieldset>';
            echo '</form>';
            break;
        case 2:
            if ($items[$item_type][1]) {
                // Choix à faire
                echo '<form id="additem" action="' . $p_url . '&amp;add=3" method="post">';
                echo '<fieldset><legend>' . $item_type_label . '</legend>';
                switch ($item_type) {
                    case 'lang':
                        echo '<p class="field"><label for="item_select" class="classic">' . __('Select language:') . '</label>' .
                        form::combo('item_select', $langs_combo);
                        break;
                    case 'category':
                        echo '<p class="field"><label for="item_select" class="classic">' . __('Select category:') . '</label>' .
                        form::combo('item_select', $categories_combo);
                        break;
                    case 'archive':
                        echo '<p class="field"><label for="item_select" class="classic">' . __('Select month (if necessary):') . '</label>' .
                        form::combo('item_select', $months_combo);
                        break;
                    case 'pages':
                        echo '<p class="field"><label for="item_select" class="classic">' . __('Select page:') . '</label>' .
                        form::combo('item_select', $pages_combo);
                        break;
                    case 'tags':
                        echo '<p class="field"><label for="item_select" class="classic">' . __('Select tag (if necessary):') . '</label>' .
                        form::combo('item_select', $tags_combo);
                        break;
                    default:
                        echo
                        # --BEHAVIOR-- adminSimpleMenuSelect
                        # Optional step once $item_type known : should provide a field using 'item_select' as id
                        $core->callBehavior('adminSimpleMenuSelect', $item_type, 'item_select');
                }
                echo form::hidden('item_type', $item_type);
                echo '<p>' . $core->formNonce() . '<input type="submit" name="appendaction" value="' . __('Continue...') . '" /></p>';
                echo '</fieldset>';
                echo '</form>';
                break;
            }
        case 3:
            // Libellé et description
            echo '<form id="additem" action="' . $p_url . '&amp;add=4" method="post">';
            echo '<fieldset><legend>' . $item_type_label . ($item_select_label != '' ? ' (' . $item_select_label . ')' : '') . '</legend>';
            echo '<p class="field"><label for="item_label" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
            __('Label of item menu:') . '</label>' .
            form::field('item_label', 20, 255, array(
                'default'    => $item_label,
                'extra_html' => 'required placeholder="' . __('Label') . '"'
            )) .
                '</p>';
            echo '<p class="field"><label for="item_descr" class="classic">' .
            __('Description of item menu:') . '</label>' . form::field('item_descr', 30, 255, $item_descr) . '</p>';
            echo '<p class="field"><label for="item_url" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' .
            __('URL of item menu:') . '</label>' .
            form::field('item_url', 40, 255, array(
                'default'    => $item_url,
                'extra_html' => 'required placeholder="' . __('URL') . '"'
            )) .
                '</p>';
            echo form::hidden('item_type', $item_type) . form::hidden('item_select', $item_select);
            echo '<p class="field"><label for="item_descr" class="classic">' .
            __('Open URL on a new tab') . ':</label>' . form::checkbox('item_targetBlank', 'blank') . '</p>';
            echo '<p>' . $core->formNonce() . '<input type="submit" name="appendaction" value="' . __('Add this item') . '" /></p>';
            echo '</fieldset>';
            echo '</form>';
            break;
    }
}

// Formulaire d'activation
if (!$step) {
    echo '<form id="settings" action="' . $p_url . '" method="post">' .
    '<p>' . form::checkbox('active', 1, $menu_active) .
    '<label class="classic" for="active">' . __('Enable simple menu for this blog') . '</label>' . '</p>' .
    '<p>' . $core->formNonce() . '<input type="submit" name="saveconfig" value="' . __('Save configuration') . '" />' . '</p>' .
        '</form>';
}

// Liste des items
if (!$step) {
    echo '<form id="menuitemsappend" action="' . $p_url . '&amp;add=1" method="post">';
    echo '<p class="top-add">' . $core->formNonce() . '<input class="button add" type="submit" name="appendaction" value="' . __('Add an item') . '" /></p>';
    echo '</form>';
}

if (count($menu)) {
    if (!$step) {
        echo '<form id="menuitems" action="' . $p_url . '" method="post">';
    }
    // Entête table
    echo
    '<div class="table-outer">' .
    '<table class="dragable">' .
    '<caption>' . __('Menu items list') . '</caption>' .
        '<thead>' .
        '<tr>';
    if (!$step) {
        echo '<th scope="col"></th>';
        echo '<th scope="col"></th>';
    }
    echo
    '<th scope="col">' . __('Label') . '</th>' .
    '<th scope="col">' . __('Description') . '</th>' .
    '<th scope="col">' . __('URL') . '</th>' .
    '<th scope="col">' . __('Open URL on a new tab') . '</th>' .
        '</tr>' .
        '</thead>' .
        '<tbody' . (!$step ? ' id="menuitemslist"' : '') . '>';
    $count = 0;
    foreach ($menu as $i => $m) {
        echo '<tr class="line" id="l_' . $i . '">';

        //because targetBlank can not exists. This value has been added after this plugin creation.
        if ((isset($m['targetBlank'])) && ($m['targetBlank'])) {
            $targetBlank    = true;
            $targetBlankStr = 'X';
        } else {
            $targetBlank    = false;
            $targetBlankStr = '';
        }

        if (!$step) {
            $count++;
            echo '<td class="handle minimal">' .
            form::number(array('order[' . $i . ']'), array(
                'min'        => 1,
                'default'    => $count,
                'class'      => 'position',
                'extra_html' => 'title="' . sprintf(__('position of %s'), html::escapeHTML(__($m['label']))) . '"'
            )) .
            form::hidden(array('dynorder[]', 'dynorder-' . $i), $i) . '</td>';
            echo '<td class="minimal">' . form::checkbox(array('items_selected[]', 'ims-' . $i), $i) . '</td>';
            echo '<td class="nowrap" scope="row">' . form::field(array('items_label[]', 'iml-' . $i), '', 255, html::escapeHTML(__($m['label']))) . '</td>';
            echo '<td class="nowrap">' . form::field(array('items_descr[]', 'imd-' . $i), '30', 255, html::escapeHTML(__($m['descr']))) . '</td>';
            echo '<td class="nowrap">' . form::field(array('items_url[]', 'imu-' . $i), '30', 255, html::escapeHTML($m['url'])) . '</td>';
            echo '<td class="nowrap">' . form::checkbox('items_targetBlank' . $i, 'blank', $targetBlank) . '</td>';
        } else {
            echo '<td class="nowrap" scope="row">' . html::escapeHTML(__($m['label'])) . '</td>';
            echo '<td class="nowrap">' . html::escapeHTML(__($m['descr'])) . '</td>';
            echo '<td class="nowrap">' . html::escapeHTML($m['url']) . '</td>';
            echo '<td class="nowrap">' . $targetBlankStr . '</td>';

        }
        echo '</tr>';
    }
    echo '</tbody>' .
        '</table></div>';
    if (!$step) {
        echo '<div class="two-cols">';
        echo '<p class="col">' . form::hidden('im_order', '') . $core->formNonce();
        echo '<input type="submit" name="updateaction" value="' . __('Update menu') . '" />' . '</p>';
        echo '<p class="col right">' . '<input id="remove-action" type="submit" class="delete" name="removeaction" ' .
        'value="' . __('Delete selected menu items') . '" ' .
        'onclick="return window.confirm(\'' . html::escapeJS(__('Are you sure you want to remove selected menu items?')) . '\');" />' .
            '</p>';
        echo '</div>';
        echo '</form>';
    }
} else {
    echo
    '<p>' . __('No menu items so far.') . '</p>';
}

dcPage::helpBlock('simpleMenu');
?>

</body>
</html>
