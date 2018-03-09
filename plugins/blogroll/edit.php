<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$id = html::escapeHTML($_REQUEST['id']);

try {
    $rs = $blogroll->getLink($id);
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

if (!$core->error->flag() && $rs->isEmpty()) {
    $core->error->add(__('No such link or title'));
} else {
    $link_title = $rs->link_title;
    $link_href  = $rs->link_href;
    $link_desc  = $rs->link_desc;
    $link_lang  = $rs->link_lang;
    $link_xfn   = $rs->link_xfn;
}

# Update a link
if (isset($rs) && !$rs->is_cat && !empty($_POST['edit_link'])) {
    $link_title = html::escapeHTML($_POST['link_title']);
    $link_href  = html::escapeHTML($_POST['link_href']);
    $link_desc  = html::escapeHTML($_POST['link_desc']);
    $link_lang  = html::escapeHTML($_POST['link_lang']);

    $link_xfn = '';

    if (!empty($_POST['identity'])) {
        $link_xfn .= $_POST['identity'];
    } else {
        if (!empty($_POST['friendship'])) {
            $link_xfn .= ' ' . $_POST['friendship'];
        }
        if (!empty($_POST['physical'])) {
            $link_xfn .= ' met';
        }
        if (!empty($_POST['professional'])) {
            $link_xfn .= ' ' . implode(' ', $_POST['professional']);
        }
        if (!empty($_POST['geographical'])) {
            $link_xfn .= ' ' . $_POST['geographical'];
        }
        if (!empty($_POST['family'])) {
            $link_xfn .= ' ' . $_POST['family'];
        }
        if (!empty($_POST['romantic'])) {
            $link_xfn .= ' ' . implode(' ', $_POST['romantic']);
        }
    }

    try {
        $blogroll->updateLink($id, $link_title, $link_href, $link_desc, $link_lang, trim($link_xfn));
        dcPage::addSuccessNotice(__('Link has been successfully updated'));
        http::redirect($p_url . '&edit=1&id=' . $id);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Update a category
if (isset($rs) && $rs->is_cat && !empty($_POST['edit_cat'])) {
    $link_desc = html::escapeHTML($_POST['link_desc']);

    try {
        $blogroll->updateCategory($id, $link_desc);
        dcPage::addSuccessNotice(__('Category has been successfully updated'));
        http::redirect($p_url . '&edit=1&id=' . $id);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

?>
<html>
<head>
  <title>Blogroll</title>
</head>

<body>
<?php
echo dcPage::breadcrumb(
    array(
        html::escapeHTML($core->blog->name) => '',
        __('Blogroll')                      => $p_url
    )) .
dcPage::notices();
?>

<?php echo '<p><a class="back" href="' . $p_url . '">' . __('Return to blogroll') . '</a></p>'; ?>

<?php
if (isset($rs) && $rs->is_cat) {
    echo
    '<form action="' . $p_url . '" method="post">' .
    '<h3>' . __('Edit category') . '</h3>' .

    '<p><label for="link_desc" class="required classic"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
    form::field('link_desc', 30, 255, array(
        'default'    => html::escapeHTML($link_desc),
        'extra_html' => 'required placeholder="' . __('Title') . '"'
    )) .

    form::hidden('edit', 1) .
    form::hidden('id', $id) .
    $core->formNonce() .
    '<input type="submit" name="edit_cat" value="' . __('Save') . '"/></p>' .
        '</form>';
}
if (isset($rs) && !$rs->is_cat) {

    echo
    '<form action="' . $core->adminurl->get('admin.plugin') . '" method="post" class="two-cols fieldset">' .

    '<div class="col30 first-col">' .
    '<h3>' . __('Edit link') . '</h3>' .

    '<p><label for="link_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
    form::field('link_title', 30, 255, array(
        'default'    => html::escapeHTML($link_title),
        'extra_html' => 'required placeholder="' . __('Title') . '"'
    )) .
    '</p>' .

    '<p><label for="link_href" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('URL:') . '</label> ' .
    form::url('link_href', array(
        'size'       => 30,
        'default'    => html::escapeHTML($link_href),
        'extra_html' => 'required placeholder="' . __('URL') . '"'
    )) .
    '</p>' .

    '<p><label for="link_desc">' . __('Description:') . '</label> ' .
    form::field('link_desc', 30, 255, html::escapeHTML($link_desc)) . '</p>' .

    '<p><label for="link_lang">' . __('Language:') . '</label> ' .
    form::field('link_lang', 5, 5, html::escapeHTML($link_lang)) . '</p>' .
    '</div>' .

    # XFN nightmare
    '<div class="col70 last-col">' .
    '<h3>' . __('XFN information') . '</h3>' .
    '<div class="table-outer">' .
    '<table class="noborder">' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Me') . '</th>' .
    '<td><p>' . '<label class="classic">' .
    form::checkbox(array('identity'), 'me', ($link_xfn == 'me')) . ' ' .
    __('_xfn_Another link for myself') . '</label></p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Friendship') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::radio(array('friendship'), 'contact',
        strpos($link_xfn, 'contact') !== false) . __('_xfn_Contact') . '</label> ' .
    '<label class="classic">' . form::radio(array('friendship'), 'acquaintance',
        strpos($link_xfn, 'acquaintance') !== false) . __('_xfn_Acquaintance') . '</label> ' .
    '<label class="classic">' . form::radio(array('friendship'), 'friend',
        strpos($link_xfn, 'friend') !== false) . __('_xfn_Friend') . '</label> ' .
    '<label class="classic">' . form::radio(array('friendship'), '') . __('None') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Physical') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::checkbox(array('physical'), 'met',
        strpos($link_xfn, 'met') !== false) . __('_xfn_Met') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Professional') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::checkbox(array('professional[]'), 'co-worker',
        strpos($link_xfn, 'co-worker') !== false) . __('_xfn_Co-worker') . '</label> ' .
    '<label class="classic">' . form::checkbox(array('professional[]'), 'colleague',
        strpos($link_xfn, 'colleague') !== false) . __('_xfn_Colleague') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Geographical') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::radio(array('geographical'), 'co-resident',
        strpos($link_xfn, 'co-resident') !== false) . __('_xfn_Co-resident') . '</label> ' .
    '<label class="classic">' . form::radio(array('geographical'), 'neighbor',
        strpos($link_xfn, 'neighbor') !== false) . __('_xfn_Neighbor') . '</label> ' .
    '<label class="classic">' . form::radio(array('geographical'), '') . __('None') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Family') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::radio(array('family'), 'child',
        strpos($link_xfn, 'child') !== false) . __('_xfn_Child') . '</label> ' .
    '<label class="classic">' . form::radio(array('family'), 'parent',
        strpos($link_xfn, 'parent') !== false) . __('_xfn_Parent') . '</label> ' .
    '<label class="classic">' . form::radio(array('family'), 'sibling',
        strpos($link_xfn, 'sibling') !== false) . __('_xfn_Sibling') . '</label> ' .
    '<label class="classic">' . form::radio(array('family'), 'spouse',
        strpos($link_xfn, 'spouse') !== false) . __('_xfn_Spouse') . '</label> ' .
    '<label class="classic">' . form::radio(array('family'), 'kin',
        strpos($link_xfn, 'kin') !== false) . __('_xfn_Kin') . '</label> ' .
    '<label class="classic">' . form::radio(array('family'), '') . __('None') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Romantic') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::checkbox(array('romantic[]'), 'muse',
        strpos($link_xfn, 'muse') !== false) . __('_xfn_Muse') . '</label> ' .
    '<label class="classic">' . form::checkbox(array('romantic[]'), 'crush',
        strpos($link_xfn, 'crush') !== false) . __('_xfn_Crush') . '</label> ' .
    '<label class="classic">' . form::checkbox(array('romantic[]'), 'date',
        strpos($link_xfn, 'date') !== false) . __('_xfn_Date') . '</label> ' .
    '<label class="classic">' . form::checkbox(array('romantic[]'), 'sweetheart',
        strpos($link_xfn, 'sweetheart') !== false) . __('_xfn_Sweetheart') . '</label> ' .
    '</p></td>' .
    '</tr>' .
    '</table></div>' .

    '</div>' .
    '<p class="clear">' . form::hidden('p', 'blogroll') .
    form::hidden('edit', 1) .
    form::hidden('id', $id) .
    $core->formNonce() .
    '<input type="submit" name="edit_link" value="' . __('Save') . '"/></p>' .

        '</form>';
}
?>
</body>
</html>
