<?php
/**
 * @brief dcProxyV1, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcProxyV1
{
    public static function classAliases(array $aliases)
    {
        foreach ($aliases as $aliasName => $realName) {
            class_alias($realName, $aliasName);
        }
    }
}

// Classes aliases
dcProxyV1::classAliases([
    // alias → real name (including namespace if necessary, for both)

    // Deprecated since 2.26
    'Clearbricks' => 'Dotclear\Helper\Clearbricks',

    // Form helper
    'formButton'    => 'Dotclear\Helper\Html\Form\Button',
    'formCheckbox'  => 'Dotclear\Helper\Html\Form\Checkbox',
    'formColor'     => 'Dotclear\Helper\Html\Form\Color',
    'formComponent' => 'Dotclear\Helper\Html\Form\Component',
    'formDate'      => 'Dotclear\Helper\Html\Form\Date',
    'formDatetime'  => 'Dotclear\Helper\Html\Form\Datetime',
    'formDiv'       => 'Dotclear\Helper\Html\Form\Div',
    'formEmail'     => 'Dotclear\Helper\Html\Form\Email',
    'formFieldset'  => 'Dotclear\Helper\Html\Form\Fieldset',
    'formFile'      => 'Dotclear\Helper\Html\Form\File',
    'formForm'      => 'Dotclear\Helper\Html\Form\Form',
    'formHidden'    => 'Dotclear\Helper\Html\Form\Hidden',
    'formInput'     => 'Dotclear\Helper\Html\Form\Input',
    'formLabel'     => 'Dotclear\Helper\Html\Form\Label',
    'formLegend'    => 'Dotclear\Helper\Html\Form\Legend',
    'formLink'      => 'Dotclear\Helper\Html\Form\Link',
    'formNote'      => 'Dotclear\Helper\Html\Form\Note',
    'formNumber'    => 'Dotclear\Helper\Html\Form\Number',
    'formOptgroup'  => 'Dotclear\Helper\Html\Form\Optgroup',
    'formOption'    => 'Dotclear\Helper\Html\Form\Option',
    'formPara'      => 'Dotclear\Helper\Html\Form\Para',
    'formPassword'  => 'Dotclear\Helper\Html\Form\Password',
    'formRadio'     => 'Dotclear\Helper\Html\Form\Radio',
    'formSelect'    => 'Dotclear\Helper\Html\Form\Select',
    'formSubmit'    => 'Dotclear\Helper\Html\Form\Submit',
    'formText'      => 'Dotclear\Helper\Html\Form\Text',
    'formTextarea'  => 'Dotclear\Helper\Html\Form\Textarea',
    'formTime'      => 'Dotclear\Helper\Html\Form\Time',
    'formUrl'       => 'Dotclear\Helper\Html\Form\Url',

    // Diff helper
    'diff'          => 'Dotclear\Helper\Diff\Diff',
    'tidyDiff'      => 'Dotclear\Helper\Diff\TidyDiff',
    'tidyDiffChunk' => 'Dotclear\Helper\Diff\TidyDiffChunk',
    'tidyDiffLine'  => 'Dotclear\Helper\Diff\TidyDiffLine',

    // Crypt helper
    'crypt' => 'Dotclear\Helper\Crypt',

    // Mail helper
    'mail'       => 'Dotclear\Helper\Network\Mail\Mail',
    'socketMail' => 'Dotclear\Helper\Network\Mail\MailSocket',

    // Pager helper
    'pager' => 'Dotclear\Helper\Html\Pager',

    // Pager helper
    'xmlTag' => 'Dotclear\Helper\Html\XmlTag',

    // Rest helper
    'restServer' => 'Dotclear\Helper\RestServer',

    // Text helper
    'text' => 'Dotclear\Helper\Text',

    // Files and Path helpers
    'files' => 'Dotclear\Helper\File\Files',
    'path'  => 'Dotclear\Helper\File\Path',

    // Html helper
    'html' => 'Dotclear\Helper\Html\Html',
]);
