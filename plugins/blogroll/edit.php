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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$id = html::escapeHTML($_REQUEST['id']);

$rs = null;

try {
    $rs = $blogroll->getLink($id);  // @phpstan-ignore-line
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

if (!$core->error->flag() && $rs->isEmpty()) {
    $link_title = '';
    $link_href  = '';
    $link_desc  = '';
    $link_lang  = '';
    $link_xfn   = '';
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
        $blogroll->updateLink($id, $link_title, $link_href, $link_desc, $link_lang, trim($link_xfn));   // @phpstan-ignore-line
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
        $blogroll->updateCategory($id, $link_desc); // @phpstan-ignore-line
        dcPage::addSuccessNotice(__('Category has been successfully updated'));
        http::redirect($p_url . '&edit=1&id=' . $id);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Languages combo
$links      = $blogroll->getLangs(['order' => 'asc']);  // @phpstan-ignore-line
$lang_combo = dcAdminCombos::getLangsCombo($links, true);

?>
<html>
<head>
  <title>Blogroll</title>
</head>

<body>
<?php
echo dcPage::breadcrumb(
    [
        html::escapeHTML($core->blog->name) => '',
        __('Blogroll')                      => $p_url
    ]) .
dcPage::notices();
?>

<?php echo '<p><a class="back" href="' . $p_url . '">' . __('Return to blogroll') . '</a></p>'; ?>

<?php
if (isset($rs) && $rs->is_cat) {
    echo
    '<form action="' . $p_url . '" method="post">' .
    '<h3>' . __('Edit category') . '</h3>' .

    '<p><label for="link_desc" class="required classic"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label> ' .
    form::field('link_desc', 30, 255, [
        'default'    => html::escapeHTML($link_desc),
        'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . $core->auth->getInfo('user_lang') . '" spellcheck="true"'
    ]) .

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
    form::field('link_title', 30, 255, [
        'default'    => html::escapeHTML($link_title),
        'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . $core->auth->getInfo('user_lang') . '" spellcheck="true"'
    ]) .
    '</p>' .

    '<p><label for="link_href" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('URL:') . '</label> ' .
    form::url('link_href', [
        'size'       => 30,
        'default'    => html::escapeHTML($link_href),
        'extra_html' => 'required placeholder="' . __('URL') . '"'
    ]) .
    '</p>' .

    '<p><label for="link_desc">' . __('Description:') . '</label> ' .
    form::field('link_desc', 30, 255,
        [
            'default'    => html::escapeHTML($link_desc),
            'extra_html' => 'lang="' . $core->auth->getInfo('user_lang') . '" spellcheck="true"'
        ]) . '</p>' .

    '<p><label for="link_lang">' . __('Language:') . '</label> ' .
    form::combo('link_lang', $lang_combo, $link_lang) .
    '</p>' .

    '</div>' .

    # XFN nightmare
    '<div class="col70 last-col">' .
    '<h3>' . __('XFN information') . '</h3>' .
    '<p class="clear form-note">' . __('More information on <a href="https://en.wikipedia.org/wiki/XHTML_Friends_Network">Wikipedia</a> website') . '</p>' .

    '<div class="table-outer">' .
    '<table class="noborder">' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Me') . '</th>' .
    '<td><p>' . '<label class="classic">' .
    form::checkbox(['identity'], 'me', ($link_xfn == 'me')) . ' ' .
    __('_xfn_Another link for myself') . '</label></p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Friendship') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::radio(['friendship'], 'contact',
        strpos($link_xfn, 'contact') !== false) . __('_xfn_Contact') . '</label> ' .
    '<label class="classic">' . form::radio(['friendship'], 'acquaintance',
        strpos($link_xfn, 'acquaintance') !== false) . __('_xfn_Acquaintance') . '</label> ' .
    '<label class="classic">' . form::radio(['friendship'], 'friend',
        strpos($link_xfn, 'friend') !== false) . __('_xfn_Friend') . '</label> ' .
    '<label class="classic">' . form::radio(['friendship'], '') . __('None') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Physical') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::checkbox(['physical'], 'met',
        strpos($link_xfn, 'met') !== false) . __('_xfn_Met') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Professional') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::checkbox(['professional[]'], 'co-worker',
        strpos($link_xfn, 'co-worker') !== false) . __('_xfn_Co-worker') . '</label> ' .
    '<label class="classic">' . form::checkbox(['professional[]'], 'colleague',
        strpos($link_xfn, 'colleague') !== false) . __('_xfn_Colleague') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Geographical') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::radio(['geographical'], 'co-resident',
        strpos($link_xfn, 'co-resident') !== false) . __('_xfn_Co-resident') . '</label> ' .
    '<label class="classic">' . form::radio(['geographical'], 'neighbor',
        strpos($link_xfn, 'neighbor') !== false) . __('_xfn_Neighbor') . '</label> ' .
    '<label class="classic">' . form::radio(['geographical'], '') . __('None') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Family') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::radio(['family'], 'child',
        strpos($link_xfn, 'child') !== false) . __('_xfn_Child') . '</label> ' .
    '<label class="classic">' . form::radio(['family'], 'parent',
        strpos($link_xfn, 'parent') !== false) . __('_xfn_Parent') . '</label> ' .
    '<label class="classic">' . form::radio(['family'], 'sibling',
        strpos($link_xfn, 'sibling') !== false) . __('_xfn_Sibling') . '</label> ' .
    '<label class="classic">' . form::radio(['family'], 'spouse',
        strpos($link_xfn, 'spouse') !== false) . __('_xfn_Spouse') . '</label> ' .
    '<label class="classic">' . form::radio(['family'], 'kin',
        strpos($link_xfn, 'kin') !== false) . __('_xfn_Kin') . '</label> ' .
    '<label class="classic">' . form::radio(['family'], '') . __('None') . '</label>' .
    '</p></td>' .
    '</tr>' .

    '<tr class="line">' .
    '<th>' . __('_xfn_Romantic') . '</th>' .
    '<td><p>' .
    '<label class="classic">' . form::checkbox(['romantic[]'], 'muse',
        strpos($link_xfn, 'muse') !== false) . __('_xfn_Muse') . '</label> ' .
    '<label class="classic">' . form::checkbox(['romantic[]'], 'crush',
        strpos($link_xfn, 'crush') !== false) . __('_xfn_Crush') . '</label> ' .
    '<label class="classic">' . form::checkbox(['romantic[]'], 'date',
        strpos($link_xfn, 'date') !== false) . __('_xfn_Date') . '</label> ' .
    '<label class="classic">' . form::checkbox(['romantic[]'], 'sweetheart',
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
