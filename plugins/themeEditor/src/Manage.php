<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use Dotclear\App;
use Dotclear\Helper\Diff\Diff;
use Dotclear\Helper\Diff\TidyDiff;
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\XmlTag;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief   The module backend manage process.
 * @ingroup themeEditor
 */
class Manage
{
    use TraitProcess;

    // Local static properties

    /**
     * Current edited theme (Module)
     */
    private static ModuleDefine $theme;

    /**
     * Theme editor instance
     */
    private static ThemeEditor $editor;

    /**
     * Use syntaxic color?
     */
    private static bool $colorsyntax;

    /**
     * Syntaxic color theme
     */
    private static string $colorsyntax_theme;

    /**
     * Current edited file descriptor
     *
     * @var array{c: string|null, w: bool, type: string, f: string} $file
     */
    private static array $file;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $file_default = [
            'c'    => null,
            'w'    => false,
            'type' => '',
            'f'    => '',
        ];

        self::$file = $file_default;

        // Get interface setting
        self::$colorsyntax       = App::auth()->prefs()->get('interface')->getBool('colorsyntax', false);
        self::$colorsyntax_theme = App::auth()->prefs()->get('interface')->getStr('colorsyntax_theme') ?? '';

        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        $system_theme = App::blog()->settings()->get('system')->getStr('theme', false);
        self::$theme  = App::themes()->getDefine($system_theme);
        self::$editor = new ThemeEditor();

        try {
            try {
                if (!empty($_REQUEST['tpl']) && is_string($_REQUEST['tpl'])) {
                    self::$file = self::$editor->getFileContent('tpl', $_REQUEST['tpl']);
                } elseif (!empty($_REQUEST['css']) && is_string($_REQUEST['css'])) {
                    self::$file = self::$editor->getFileContent('css', $_REQUEST['css']);
                } elseif (!empty($_REQUEST['js']) && is_string($_REQUEST['js'])) {
                    self::$file = self::$editor->getFileContent('js', $_REQUEST['js']);
                } elseif (!empty($_REQUEST['po']) && is_string($_REQUEST['po'])) {
                    self::$file = self::$editor->getFileContent('po', $_REQUEST['po']);
                } elseif (!empty($_REQUEST['php']) && is_string($_REQUEST['php'])) {
                    self::$file = self::$editor->getFileContent('php', $_REQUEST['php']);
                }
            } catch (Exception $e) {
                self::$file = $file_default;

                throw $e;
            }

            if (!empty($_POST['write'])) {
                // Write file

                // Overwrite content with new one
                self::$file['c'] = is_string($file_content = $_POST['file_content']) ? $file_content : '';

                self::$editor->writeFile(
                    self::$file['type'],
                    self::$file['f'],
                    self::$file['c']
                );

                $content = self::$file['c'];
                if ($content !== '' && !preg_match('/\\n$/D', $content)) {
                    // The file is not empty and does not end with a newline, add it
                    $content .= "\n";
                    self::$file['c'] = $content;
                }

                App::backend()->notices()->addSuccessNotice(__('The file has been saved.'));
            }

            if (!empty($_POST['delete'])) {
                // Delete file

                $type = self::$file['type'];
                $file = self::$file['f'];

                self::$editor->deleteFile($type, $file);
                App::backend()->notices()->addSuccessNotice(__('The file has been reset.'));
                My::redirect([
                    $type => $file,
                ]);
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (self::$editor->devMode()) {
            App::backend()->notices()->addWarningNotice(__('The theme editor is in development mode, theme files will be overwritten!'));
        }

        $head = '';
        if (self::$colorsyntax) {
            $head .= App::backend()->page()->jsJson('dotclear_colorsyntax', ['colorsyntax' => self::$colorsyntax]);
        }

        $head .= App::backend()->page()->jsJson('theme_editor_msg', [
            'confirm_reset_file' => __('Are you sure you want to reset this file?'),
        ]) .
            My::jsLoad('script') .
            App::backend()->page()->jsConfirmClose('file-form');
        if (self::$colorsyntax) {
            $head .= App::backend()->page()->jsLoadCodeMirror(self::$colorsyntax_theme);
        }

        $head .= My::cssLoad('style');

        App::backend()->page()->openModule(__('Edit theme files'), $head);

        echo
        App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Blog appearance')                 => App::backend()->url()->get('admin.blog.theme'),
                __('Edit theme files')                => '',
            ]
        ) .
        App::backend()->notices()->getNotices();

        $theme_name = is_string($theme_name = self::$theme->get('name')) ? $theme_name : self::$theme->getId();

        echo (new Para())
            ->items([
                (new Text(null, sprintf(
                    __('Your current theme on this blog is "%s".'),
                    (new Strong(Html::escapeHTML($theme_name)))->render()
                ))),
            ])
        ->render();

        $editorMode = self::$colorsyntax ? self::getEditorMode() : '';

        if (self::$file['c'] === null) {
            $items = [
                (new Note())->text(__('Please select a file to edit.')),
            ];
        } else {
            $deletable = self::$editor->deletableFile(self::$file['type'], self::$file['f']);
            if (self::$editor->devMode() && !$deletable) {
                $deleteButton = (new None());
            } else {
                $deleteButton = (new Submit(['delete'], __('Reset')))
                    ->class(['delete', $deletable ? '' : 'hide']);
            }

            $original = '';
            $diff     = '';
            $parent   = self::$editor->getParent(self::$file['type'], self::$file['f']);
            if ($parent !== '') {
                $destination = self::$editor->getDestinationFile(self::$file['type'], self::$file['f']);
                if ($destination !== false && $destination !== $parent) {
                    $original = (string) file_get_contents($parent);
                    $diff     = self::getDiffNode(self::$file['c'], $original, 'diff');
                }
            }

            $items = [
                (new Form())
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->id('file-form')
                    ->fields([
                        (new Text('h3', __('File editor'))),
                        (new Para())
                            ->items([
                                (new Textarea('file_content', Html::escapeHTML(self::$file['c'])))
                                    ->cols(72)
                                    ->rows(25)
                                    ->class('maximal')
                                    ->disabled(!self::$file['w'])
                                    ->label((new Label(sprintf(
                                        __('Editing file %s'),
                                        (new Strong(self::$file['f']))->render()
                                    ), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->class(self::$file['w'] ? 'form-buttons' : '')
                            ->items(self::$file['w'] ? [
                                ...My::hiddenFields(),
                                (new Submit(['write'], __('Save') . ' (s)'))
                                    ->accesskey('s'),
                                $deleteButton,
                                self::$file['type'] ?
                                    (new Hidden([self::$file['type']], self::$file['f'])) :
                                    (new None()),
                                (new Textarea('original', $original))
                                    ->extra('hidden'),
                                (new Textarea('diff', $diff instanceof XmlTag ? $diff->toXML() : ''))
                                    ->extra('hidden'),
                                (new Note())
                                    ->class('info')
                                    ->text(__('If you use <code>url(...)</code> in your CSS files, be sure to use <code>url(index.php?tf=...)</code> to correctly load theme resources (imported CSS, images, etc.), except for URL types in the form <code>data:image</code>.<br>Example: do <code>@import url(index.php?tf=css/layout.css);</code> instead of <code>@import url(css/layout.css);</code>.')),
                            ] : [
                                (new Note())
                                    ->class('warning')
                                    ->text(__('This file is not overloadable. Please check your var folder permissions.')),
                            ]),
                        // Diff rendered
                        $diff instanceof XmlTag ? self::renderDiff($diff) : (new None()),
                    ]),
                self::$colorsyntax ?
                    (new Text(null, App::backend()->page()->jsJson('theme_editor_mode', ['mode' => $editorMode]) . My::jsLoad('mode') . App::backend()->page()->jsRunCodeMirror('editor', 'file_content', 'dotclear', self::$colorsyntax_theme))) :
                    (new None()),
            ];
        }

        echo (new Div())
            ->id('file-box')
            ->items([
                (new Div())
                    ->id('file-editor')
                    ->items($items),
                (new Div())
                    ->id('file-chooser')
                    ->items([
                        (new Text('h3', __('Templates files'))),
                        (new Text(null, self::$editor->filesList(
                            'tpl',
                            (new Link())->href(App::backend()->getPageURL() . '&tpl=%2$s')->text('%1$s')->class('tpl-link')->render()
                        ))),
                        (new Text('h3', __('CSS files'))),
                        (new Text(null, self::$editor->filesList(
                            'css',
                            (new Link())->href(App::backend()->getPageURL() . '&css=%2$s')->text('%1$s')->class('css-link')->render()
                        ))),
                        (new Text('h3', __('JavaScript files'))),
                        (new Text(null, self::$editor->filesList(
                            'js',
                            (new Link())->href(App::backend()->getPageURL() . '&js=%2$s')->text('%1$s')->class('js-link')->render()
                        ))),
                        (new Text('h3', __('Locales files'))),
                        (new Text(null, self::$editor->filesList(
                            'po',
                            (new Link())->href(App::backend()->getPageURL() . '&po=%2$s')->text('%1$s')->class('po-link')->render()
                        ))),
                        (new Text('h3', __('PHP files'))),
                        (new Text(null, self::$editor->filesList(
                            'php',
                            (new Link())->href(App::backend()->getPageURL() . '&php=%2$s')->text('%1$s')->class('php-link')->render()
                        ))),
                    ]),
            ])
        ->render();

        App::backend()->page()->helpBlock(My::id());

        App::backend()->page()->closeModule();
    }

    private static function getEditorMode(): string
    {
        $modes = [
            'css' => 'css',
            'js'  => 'javascript',
            'po'  => 'text/plain',
            'php' => 'php',
        ];

        foreach ($modes as $request => $mode) {
            if (isset($_REQUEST[$request]) && !empty($_REQUEST[$request])) {
                return $mode;
            }
        }

        return 'text/html';
    }

    /**
     * Builds a diff node (XML).
     *
     * @param      string  $src    The source
     * @param      string  $dst    The destination
     * @param      string  $root   The root
     *
     * @return     XmlTag  The node.
     */
    private static function getDiffNode(string $src, string $dst, string $root): XmlTag
    {
        $uniDiff  = Diff::uniDiff($src, $dst);
        $tidyDiff = new TidyDiff(htmlspecialchars($uniDiff), true);

        $rev = new XmlTag($root);

        foreach ($tidyDiff->getChunks() as $k => $chunk) {
            foreach ($chunk->getLines() as $line) {
                switch ($line->type) {
                    case 'context':
                        $node        = new XmlTag('context');
                        $node->oline = $line->lines[0];
                        $node->nline = $line->lines[1];
                        $node->insertNode($line->content);
                        $rev->insertNode($node);

                        break;
                    case 'delete':
                        $node        = new XmlTag('delete');
                        $node->oline = $line->lines[0];
                        $content     = str_replace(['\0', '\1'], ['<del>', '</del>'], $line->content);
                        $node->insertNode($content);
                        $rev->insertNode($node);

                        break;
                    case 'insert':
                        $node        = new XmlTag('insert');
                        $node->nline = $line->lines[1];
                        $content     = str_replace(['\0', '\1'], ['<ins>', '</ins>'], $line->content);
                        $node->insertNode($content);
                        $rev->insertNode($node);

                        break;
                }
            }

            if ($k < count($tidyDiff->getChunks()) - 1) {
                $node = new XmlTag('skip');
                $rev->insertNode($node);
            }
        }

        return $rev;
    }

    private static function renderDiff(XmlTag $diff): Details|None
    {
        try {
            $trNodes  = [];
            $previous = '';
            $index    = 0;

            $nodes = $diff->getNodes();

            foreach ($nodes as $node) {
                if ($node instanceof XmlTag) {
                    $name = $node->getName();
                    $diff = $node->getNode(0);
                    if ($diff instanceof XmlTag) {
                        $diff = $diff->toXML();
                    }

                    $classes = [];

                    if ($name === 'skip') {
                        $currentLineNumber  = '…';
                        $modifiedLineNumber = '…';
                    } else {
                        $currentLineNumber  = is_numeric($currentLineNumber = $node->oline) ? (string) $currentLineNumber : '';
                        $modifiedLineNumber = is_numeric($modifiedLineNumber = $node->nline) ? (string) $modifiedLineNumber : '';
                    }

                    if (in_array($name, ['skip', 'context', 'insert', 'delete'], true)) {
                        $classes[] = 'diff-' . $name;
                    }

                    if ($name !== $previous && ($previous === '' || $previous === 'context')) {
                        $classes[] = 'diff-first';
                    }

                    $next = count($nodes) > $index + 1 && $nodes[$index + 1] instanceof XmlTag ? $nodes[$index + 1]->name : '';

                    if ($name !== $next && $next !== 'insert' && $next !== 'delete') {
                        $classes[] = 'diff-last';
                    }

                    $previous = $name;

                    // Prepare line

                    $trNode = new Tr();

                    $tdCurrentNode  = new Td();
                    $tdModifiedNode = new Td();

                    $tdDiffNode = new Td();

                    $tdCurrentNode->class(['minimal', 'diff-col-line', 'count']);
                    $tdCurrentNode->text($currentLineNumber);

                    $tdModifiedNode->class(['minimal', 'diff-col-line', 'count']);
                    $tdModifiedNode->text($modifiedLineNumber);

                    $tdDiffNode->class($classes);
                    $tdDiffNode->text($diff);

                    $trNode->items([
                        $tdCurrentNode,
                        $tdModifiedNode,
                        $tdDiffNode,
                    ]);

                    $trNodes[] = $trNode;

                    $index++;
                }
            }

            if ($trNodes === []) {
                return new None();
            }

            return (new Details('render_diff'))
                ->summary((new Summary(__('Modifications'))))
                ->items([
                    (new Table('table_diff'))
                        ->thead((new Thead())
                            ->rows([
                                (new Tr())
                                    ->items([
                                        (new Th())
                                            ->class(['minimal', 'diff-line-number', 'nowrap'])
                                            ->text(__('Current')),
                                        (new Th())
                                            ->class(['minimal', 'diff-line-number', 'nowrap'])
                                            ->text(__('Parent')),
                                        (new Th())
                                            ->class('maximal')
                                            ->text(__('Line content')),
                                    ]),
                            ]))
                        ->tbody((new Tbody())
                            ->rows($trNodes)),
                ]);
        } catch (Exception) {
            return new None();
        }
    }
}
