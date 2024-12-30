<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html;

/* History
 * -------
 *
 * Contributor(s):
 * Stephanie Booth
 * Mathieu Pillard
 * Christophe Bonijol
 * Jean-Charles Bagneris
 * Nicolas Chachereau
 * Jérôme Lipowicz
 * Franck Paul
 *
 * Version : 3.2.24
 * Release date : 2021-10-25
 *
 * History :
 *
 * 3.2.24 - Franck
 * => Ajout support bloc détail (|summary en première ligne du bloc, | en dernière ligne du bloc, contenu du bloc libre)
 *
 * 3.2.23 - Franck
 * => Ajout support attributs supplémentaires (§attributs§) pour les éléments en ligne (sans imbrication)
 * => Ajout support ;;span;;
 *
 * 3.2.22 - Franck
 * => Ajout support attributs supplémentaires (§§attributs[|attributs parent]§§ en fin de 1re ligne) pour les blocs
 * => Ajout support ,,indice,,
 *
 * 3.2.21 - Franck
 * => Suppression du support _indice_ (conflit fréquent avec les noms de fichier/URL/…)
 *
 * 3.2.20 - Franck
 * => Suppression des p entourant les figures ou les liens incluants une figure
 *
 * 3.2.19 - Franck
 * => abbr, img, em, strong, i, code, del, ins, mark, sup are only elements converted inside a link text
 *
 * 3.2.18 - Franck
 * => Def lists required at least a space after : or =
 *
 * 3.2.17 - Franck
 * => Added ££text|lang££ support which gives an <i>…</i>
 *
 * 3.2.16 - Franck
 * => Added _indice_ support
 *
 * 3.2.15 - Franck
 * => Added ^exponant^ support
 *
 * 3.2.14 - Franck
 * => Ajout de la gestion d'un fichier externe d'acronymes (fusionné avec le fichier existant)
 *
 * 3.2.13 - Franck
 * => Added = <term>, : <definition> support (definition list)
 *
 * 3.2.12 - Franck
 * => PHP 7.2 compliance
 *
 * 3.2.11 - Franck
 * => Added ) aside block support (HTML5 only)
 *
 * 3.2.10 - Franck
 * => Added ""marked text"" support (HTML5 only)
 *
 * 3.2.9 - Franck
 * => <a name="anchor"></a> est remplacé par <a id="anchor"></a> pour assurer la compatibilité avec HTML5
 *
 * 3.2.8 - Franck
 * => <acronym> est remplacé par <abbr> pour assurer la compatibilité avec HTML5
 *
 * 3.2.7 - Franck
 * => Les styles d'alignement des images sont modifiables via les options
 *
 * 3.2.6 - Franck
 * => Added ``inline html`` support
 *
 * 3.2.5 - Franck
 * => Changed longdesc by title in images
 *
 * 3.2.4 - Olivier
 * => Auto links
 * => Code cleanup
 *
 * 3.2.3 - Olivier
 * => PHP5 Strict
 *
 * 3.2.2 - Olivier
 * => Changement de la gestion des URL spéciales
 *
 * 3.2.1 - Olivier
 * => Changement syntaxe des macros
 *
 * 3.2 - Olivier
 * => Changement de fonctionnement des macros
 * => Passage de fonctions externes pour les macros et les mots wiki
 *
 * 3.1d - Jérôme Lipowicz
 * => antispam
 * - Olivier
 * => centrage d'image
 *
 * 3.1c - Olivier
 * => Possibilité d'échaper les | dans les marqueurs avec \
 *
 * 3.1b - Nicolas Chachereau
 * => Changement de regexp pour la correction syntaxique
 *
 * 3.1a - Olivier
 * => Bug du Call-time pass-by-reference
 *
 * 3.1 - Olivier
 * => Ajout des macros «««..»»»
 * => Ajout des blocs vides øøø
 * => Ajout du niveau de titre paramétrable
 * => Option de blocage du parseur dans les <pre>
 * => Titres au format setext (experimental, désactivé)
 *
 * 3.0 - Olivier
 * => Récriture du parseur inline, plus d'erreur XHTML
 * => Ajout d'une vérification d'intégrité pour les listes
 * => Les acronymes sont maintenant dans un fichier texte
 * => Ajout d'un tag images ((..)), del --..-- et ins ++..++
 * => Plus possible de faire des liens JS [lien|javascript:...]
 * => Ajout des notes de bas de page §§...§§
 * => Ajout des mots wiki
 *
 * 2.5 - Olivier
 * => Récriture du code, plus besoin du saut de ligne entre blocs !=
 *
 * 2.0 - Stephanie
 * => correction des PCRE et ajout de fonctionnalités
 * - Mathieu
 * => ajout du strip-tags, implementation des options, reconnaissance automatique d'url, etc.
 * - Olivier
 * => changement de active_link en active_urls
 * => ajout des options pour les blocs
 * => intégration de l'aide dans le code, avec les options
 * => début de quelque chose pour la reconnaissance auto d'url (avec Mat)
 */

/**
 * @class WikiToHtml
 */
class WikiToHtml
{
    // Constants

    public const VERSION = '3.2.23';

    public const MACRO_FN_PREFIX = 'macro:';

    private const MACRO_PREFIX = '##########MACRO#';
    private const MACRO_SUFFIX = '#';

    // Properties

    /**
     * Stack of options
     *
     * @var array<string, mixed>
     */
    public array $opt;

    /**
     * Stack of accronyms
     *
     * @var array<string, string>
     */
    public array $acro_table;

    /**
     * Stack of footnotes
     *
     * @var array<string, string>
     */
    public array $foot_notes = [];

    /**
     * Stack of macros
     *
     * @var array<int, string>
     */
    public array $macros = [];

    /**
     * Stack of registered functions
     *
     * @var array<string, callable>
     */
    public array $functions = [];

    /**
     * Stack of Wiki content lines
     *
     * @var array<string>
     */
    public array $wiki_lines;

    /**
     * Inline tags
     *
     * @var array<string, array<string>> of name => [ opening string, closing string ]
     */
    public array $tags;

    /**
     * User-defined inline tags
     *
     * Will be merged with self::$tags
     *
     * @var        array<string, array<string>>
     */
    public array $custom_tags = [];

    /**
     * Inline tags, opening strings
     *
     * @var array<string, string> of name => opening string
     */
    public array $open_tags;

    /**
     * Inline tags, closing strings
     *
     * Populate from self::$tags
     *
     * @var array<string, string> of name => closing string
     */
    public array $close_tags;

    /**
     * All opening and closing strings known
     *
     * Populate from self::$tags
     *
     * @var array<string>
     */
    public array $all_tags;

    /**
     * Copy of self::$all_tags with escaped strings (\\string)
     *
     * @var array<string>
     */
    public array $escape_table;

    /**
     * PCRE pattern with all opening and closing strings known
     *
     * Populate from self::$all_tags
     */
    public string $tag_pattern;

    /**
     * Full line tags
     *
     * Populate from self::$tags
     *
     * @var array<string, string> of name => opening string (at begining of line)
     */
    public array $linetags;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        # Mise en place des options
        $this->setOpt('active_title', 1); # Activation des titres !!!
        $this->setOpt('active_setext_title', 0); # Activation des titres setext (EXPERIMENTAL)
        $this->setOpt('active_hr', 1); # Activation des <hr>
        $this->setOpt('active_lists', 1); # Activation des listes
        $this->setOpt('active_defl', 1); # Activation des listes de définition
        $this->setOpt('active_quote', 1); # Activation du <blockquote>
        $this->setOpt('active_pre', 1); # Activation du <pre>
        $this->setOpt('active_empty', 1); # Activation du bloc vide øøø
        $this->setOpt('active_auto_urls', 0); # Activation de la reconnaissance d'url
        $this->setOpt('active_auto_br', 0); # Activation du saut de ligne automatique (dans les paragraphes)
        $this->setOpt('active_antispam', 1); # Activation de l'antispam pour les emails
        $this->setOpt('active_urls', 1); # Activation des liens []
        $this->setOpt('active_auto_img', 1); # Activation des images automatiques dans les liens []
        $this->setOpt('active_img', 1); # Activation des images (())
        $this->setOpt('active_anchor', 1); # Activation des ancres ~...~
        $this->setOpt('active_em', 1); # Activation du <em> ''...''
        $this->setOpt('active_strong', 1); # Activation du <strong> __...__
        $this->setOpt('active_br', 1); # Activation du <br> %%%
        $this->setOpt('active_q', 1); # Activation du <q> {{...}}
        $this->setOpt('active_code', 1); # Activation du <code> @@...@@
        $this->setOpt('active_acronym', 1); # Activation des acronymes
        $this->setOpt('active_ins', 1); # Activation des <ins> ++..++
        $this->setOpt('active_del', 1); # Activation des <del> --..--
        $this->setOpt('active_inline_html', 1); # Activation du HTML inline ``...``
        $this->setOpt('active_footnotes', 1); # Activation des notes de bas de page
        $this->setOpt('active_wikiwords', 0); # Activation des mots wiki
        $this->setOpt('active_macros', 1); # Activation des macros /// ///
        $this->setOpt('active_mark', 1); # Activation des <mark> ""..""
        $this->setOpt('active_aside', 1); # Activation du <aside>
        $this->setOpt('active_sup', 1); # Activation du <sup> ^..^
        $this->setOpt('active_sub', 1); # Activation du <sub> ,,..,,
        $this->setOpt('active_i', 1); # Activation du <i> ££..££
        $this->setOpt('active_span', 1); # Activation du <span> ;;..;;
        $this->setOpt('active_details', 1); #activation du <details> |sommaire ...

        $this->setOpt('parse_pre', 1); # Parser l'intérieur de blocs <pre> ?

        $this->setOpt('active_fr_syntax', 1); # Corrections syntaxe FR

        $this->setOpt('first_title_level', 3); # Premier niveau de titre <h..>

        $this->setOpt('note_prefix', 'wiki-footnote');
        $this->setOpt('note_str', '<div class="footnotes"><h4>Notes</h4>%s</div>');
        $this->setOpt('note_str_single', '<div class="footnotes"><h4>Note</h4>%s</div>');
        $this->setOpt(
            'words_pattern',
            '((?<![A-Za-z0-9])([A-Z][a-z]+){2,}(?![A-Za-z0-9]))'
        );

        $this->setOpt(
            'auto_url_pattern',
            '%(?<![\[\|])(http://|https://|ftp://|news:)([^"\s\)!]+)%msu'
        );

        $this->setOpt('acronyms_file', __DIR__ . '/acronyms.txt');

        $this->setOpt('img_style_left', 'style="float:left; margin: 0 1em 1em 0;"');
        $this->setOpt('img_style_center', 'style="display:block; margin:0 auto;"');
        $this->setOpt('img_style_right', 'style="float:right; margin: 0 0 1em 1em;"');

        $this->acro_table = $this->__getAcronyms();

        /*
         * Macro syntax:
         *
         *  ///macro_name
         *  ...
         *  ///
         */
        $this->registerFunction(self::MACRO_FN_PREFIX . 'html', $this->__macroHTML(...));
    }

    /**
     * Sets the option.
     *
     * @param      string  $option  The option
     * @param      mixed   $value   The value
     */
    public function setOpt(string $option, $value): void
    {
        $this->opt[$option] = $value;

        if ($option === 'acronyms_file' && isset($this->opt[$option]) && file_exists($this->opt[$option])) {
            // Parse and merge new acronyms
            $this->acro_table = [...$this->acro_table, ...$this->__getAcronyms()];
        }
    }

    /**
     * Sets the options.
     *
     * @param      array<string, mixed>  $options  The options
     */
    public function setOpts(array $options): void
    {
        foreach ($options as $k => $v) {
            $this->opt[$k] = $v;
        }
    }

    /**
     * Gets the option.
     *
     * @param      string  $option  The option
     *
     * @return     mixed  The option.
     */
    public function getOpt(string $option)
    {
        return (empty($this->opt[$option])) ? false : $this->opt[$option];
    }

    /**
     * Register a function
     *
     * @param      string     $type   The type
     * @param      callable   $name   The name
     */
    public function registerFunction(string $type, $name): void
    {
        if (is_callable($name)) {   // @phpstan-ignore-line
            $this->functions[$type] = $name;
        }
    }

    /**
     * Convert wiki string to HTML
     */
    public function transform(string $wiki): string
    {
        # Initialisation des tags
        $this->__initTags();
        $this->foot_notes = [];

        # Récupération des macros
        if ($this->getOpt('active_macros')) {
            $wiki = preg_replace_callback('#^///(.*?)///($|\r)#ms', $this->__getMacro(...), $wiki);
        }

        # Vérification du niveau de titre
        if ($this->getOpt('first_title_level') > 4) {
            $this->setOpt('first_title_level', 4);
        }

        $html = str_replace("\r", '', (string) $wiki);

        $escape_pattern = [];

        # traitement des titres à la setext
        if ($this->getOpt('active_setext_title') && $this->getOpt('active_title')) {
            $html = preg_replace('/^(.*)\n[=]{5,}$/m', '!!!$1', (string) $html);
            $html = preg_replace('/^(.*)\n[-]{5,}$/m', '!!$1', (string) $html);
        }

        # Transformation des mots Wiki
        if ($this->getOpt('active_wikiwords') && $this->getOpt('words_pattern')) {
            $html = preg_replace('/' . $this->getOpt('words_pattern') . '/msu', '¶¶¶$1¶¶¶', (string) $html);
        }

        # Transformation des URLs automatiques
        if ($this->getOpt('active_auto_urls')) {
            $active_urls = $this->getOpt('active_urls');

            $this->setOpt('active_urls', 1);
            $this->__initTags();

            # If urls are not active, escape URLs tags
            if (!$active_urls) {
                $html = preg_replace(
                    '%(?<!\\\\)([' . preg_quote(implode('', $this->tags['a'])) . '])%msU',  // @phpstan-ignore-line
                    '\\\$1',
                    (string) $html
                );
            }

            # Transforms urls while preserving tags.
            $tree = preg_split($this->tag_pattern, (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($tree) {
                foreach ($tree as &$leaf) {
                    $leaf = preg_replace($this->getOpt('auto_url_pattern'), '[$1$2]', $leaf);
                }
                unset($leaf);
                $html = implode('', $tree);
            }
        }

        $this->wiki_lines   = explode("\n", (string) $html);
        $this->wiki_lines[] = '';

        # Parse les blocs
        $html = $this->__parseBlocks();

        # Line break
        if ($this->getOpt('active_br')) {
            $html             = preg_replace('/(?<!\\\)%%%/', '<br>', $html);
            $escape_pattern[] = '%%%';
        }

        # Nettoyage des \s en trop
        $html = preg_replace('/([\s]+)(<\/p>|<\/li>|<\/pre>)/u', '$2', (string) $html);
        $html = preg_replace('/(<li>)([\s]+)/u', '$1', (string) $html);

        # On vire les escapes
        if ($escape_pattern !== []) {
            $html = preg_replace('/\\\(' . implode('|', $escape_pattern) . ')/', '$1', (string) $html);
        }

        # On vire les ¶¶¶MotWiki¶¶¶ qui sont resté (dans les url...)
        if ($this->getOpt('active_wikiwords') && $this->getOpt('words_pattern')) {
            $html = preg_replace('/¶¶¶' . $this->getOpt('words_pattern') . '¶¶¶/msu', '$1', (string) $html);
        }

        # On remet les macros
        if ($this->getOpt('active_macros')) {
            $macro_pattern = '/^' . self::MACRO_PREFIX . '(\d+)' . self::MACRO_SUFFIX . '$/ms';
            $html          = preg_replace_callback($macro_pattern, $this->__putMacro(...), (string) $html);
        }

        # Auto line break dans les paragraphes
        if ($this->getOpt('active_auto_br')) {
            $html = preg_replace_callback('%(<p>)(.*?)(</p>)%msu', $this->__autoBR(...), (string) $html);
        }

        # Remove wrapping p around figure
        # Adapted from https://micahjon.com/2016/removing-wrapping-p-paragraph-tags-around-images-wordpress/
        $ret = (string) $html;
        while (preg_match('/<p>((?:.(?!p>))*?)(<a[^>]*>)?\s*(<figure[^>]*>)(.*?)(<\/figure>)\s*(<\/a>)?(.*?)<\/p>/msu', (string) $ret)) {
            $ret = preg_replace_callback(
                '/<p>((?:.(?!p>))*?)(<a[^>]*>)?\s*(<figure[^>]*>)(.*?)(<\/figure>)\s*(<\/a>)?(.*?)<\/p>/msu',
                function ($matches): string {
                    $figure = $matches[2] . $matches[3] . $matches[4] . $matches[5] . $matches[6];
                    $before = trim((string) $matches[1]);
                    if ($before !== '') {
                        $before = '<p>' . $before . '</p>';
                    }
                    $after = trim((string) $matches[7]);
                    if ($after !== '') {
                        $after = '<p>' . $after . '</p>';
                    }

                    return $before . $figure . $after;
                },
                (string) $ret
            );
        }
        if (!is_null($ret)) {
            $html = $ret;
        }

        # On ajoute les notes
        if ($this->foot_notes !== []) {
            $html_notes  = '';
            $note_number = 1;
            foreach ($this->foot_notes as $k => $v) {
                $html_notes .= "\n" . '<p>[<a href="#rev-' . $k . '" id="' . $k . '">' . $note_number . '</a>] ' . $v . '</p>';
                $note_number++;
            }
            $html .= sprintf("\n" . (count($this->foot_notes) > 1 ? $this->getOpt('note_str') : $this->getOpt('note_str_single')) . "\n", $html_notes);
        }

        return (string) $html;
    }

    /* PRIVATE
    --------------------------------------------------- */

    /**
     * Initializes the tags.
     */
    private function __initTags(): void
    {
        $this->tags = [
            'em'     => ["''", "''"],
            'strong' => ['__', '__'],
            'abbr'   => ['??', '??'],
            'a'      => ['[', ']'],
            'img'    => ['((', '))'],
            'q'      => ['{{', '}}'],
            'code'   => ['@@', '@@'],
            'anchor' => ['~', '~'],
            'del'    => ['--', '--'],
            'ins'    => ['++', '++'],
            'inline' => ['``', '``'],
            'note'   => ['$$', '$$'],
            'word'   => ['¶¶¶', '¶¶¶'],
            'mark'   => ['""', '""'],
            'sup'    => ['^', '^'],
            'sub'    => [',,', ',,'],
            'i'      => ['££', '££'],
            'span'   => [';;', ';;'],
            ...$this->custom_tags,
        ];

        $this->linetags = [
            'empty'   => 'øøø',
            'title'   => '([!]{1,4})',
            'hr'      => '[-]{4}[- ]',
            'quote'   => '(&gt;|;:)',
            'lists'   => '([*#]+)',
            'defl'    => '([=|:]{1} )',
            'pre'     => '[ ]{1}',
            'aside'   => '[\)]{1}',
            'details' => '[\|]{1}',
        ];

        # Suppression des tags selon les options
        if (!$this->getOpt('active_urls')) {
            unset($this->tags['a']);
        }
        if (!$this->getOpt('active_img')) {
            unset($this->tags['img']);
        }
        if (!$this->getOpt('active_anchor')) {
            unset($this->tags['anchor']);
        }
        if (!$this->getOpt('active_em')) {
            unset($this->tags['em']);
        }
        if (!$this->getOpt('active_strong')) {
            unset($this->tags['strong']);
        }
        if (!$this->getOpt('active_q')) {
            unset($this->tags['q']);
        }
        if (!$this->getOpt('active_code')) {
            unset($this->tags['code']);
        }
        if (!$this->getOpt('active_acronym')) {
            unset($this->tags['abbr']);
        }
        if (!$this->getOpt('active_ins')) {
            unset($this->tags['ins']);
        }
        if (!$this->getOpt('active_del')) {
            unset($this->tags['del']);
        }
        if (!$this->getOpt('active_inline_html')) {
            unset($this->tags['inline']);
        }
        if (!$this->getOpt('active_footnotes')) {
            unset($this->tags['note']);
        }
        if (!$this->getOpt('active_wikiwords')) {
            unset($this->tags['word']);
        }
        if (!$this->getOpt('active_mark')) {
            unset($this->tags['mark']);
        }
        if (!$this->getOpt('active_sup')) {
            unset($this->tags['sup']);
        }
        if (!$this->getOpt('active_sub')) {
            unset($this->tags['sub']);
        }
        if (!$this->getOpt('active_i')) {
            unset($this->tags['i']);
        }
        if (!$this->getOpt('active_span')) {
            unset($this->tags['span']);
        }

        # Suppression des tags de début de ligne selon les options
        if (!$this->getOpt('active_empty')) {
            unset($this->linetags['empty']);
        }
        if (!$this->getOpt('active_title')) {
            unset($this->linetags['title']);
        }
        if (!$this->getOpt('active_hr')) {
            unset($this->linetags['hr']);
        }
        if (!$this->getOpt('active_quote')) {
            unset($this->linetags['quote']);
        }
        if (!$this->getOpt('active_lists')) {
            unset($this->linetags['lists']);
        }
        if (!$this->getOpt('active_defl')) {
            unset($this->linetags['defl']);
        }
        if (!$this->getOpt('active_pre')) {
            unset($this->linetags['pre']);
        }
        if (!$this->getOpt('active_aside')) {
            unset($this->linetags['aside']);
        }
        if (!$this->getOpt('active_details')) {
            unset($this->linetags['details']);
        }

        $this->open_tags   = $this->__getTags();
        $this->close_tags  = $this->__getTags(false);
        $this->all_tags    = $this->__getAllTags();
        $this->tag_pattern = $this->__getTagsPattern();

        $this->escape_table = $this->all_tags;

        array_walk($this->escape_table, function (&$a): void {$a = '\\' . $a;});
    }

    /**
     * Gets the tags.
     *
     * @param      bool   $open   The opening strings if true, else closing strings of inline tags
     *
     * @return     array<string, string>  The tags.
     */
    private function __getTags(bool $open = true): array
    {
        $res = [];
        foreach ($this->tags as $k => $v) {
            $res[$k] = ($open) ? $v[0] : $v[1];
        }

        return $res;
    }

    /**
     * Gets all opening and closing strings of inline tags.
     *
     * @return     array<string>  All tags.
     */
    private function __getAllTags(): array
    {
        $res = [];
        foreach ($this->tags as $v) {
            $res[] = $v[0];
            $res[] = $v[1];
        }

        return array_values(array_unique($res));
    }

    /**
     * Gets the inline tags opening and closing strings pattern.
     *
     * @return     string  The tags pattern.
     */
    private function __getTagsPattern(): string
    {
        $res = $this->all_tags;

        array_walk($res, function (&$a): void {$a = preg_quote($a, '/');});

        return '/(?<!\\\)(' . implode('|', $res) . ')/';
    }

    /* Blocs
       --------------------------------------------------- */

    /**
     * Parse blocks
     */
    private function __parseBlocks(): string
    {
        $mode = null;
        $type = null;
        $attr = null;

        $html = '';

        $nb_lines = count($this->wiki_lines);

        for ($i = 0; $i < $nb_lines; $i++) {
            $previous_mode = $mode;
            $previous_type = $type;

            $line = $this->__getLine($i, $type, $mode, $attr);

            if ($type != 'pre' || $this->getOpt('parse_pre')) {
                // Parse current line
                $line = is_string($line) ? $this->__inlineWalk($line) : '';
            }

            // Close previously opened block if necessary
            $html .= $this->__closeLine($type, $mode, $previous_type, $previous_mode);

            // Open a new block if necessary
            $html .= $this->__openLine($type, $mode, $previous_type, $previous_mode, $attr);

            # P dans les blockquotes et les asides
            if (($type == 'blockquote' || $type == 'aside') && trim((string) $line) === '' && $previous_type === $type) {
                $html .= "</p>\n<p>";
            }

            # Correction de la syntaxe FR dans tous sauf pre et hr
            # Sur idée de Christophe Bonijol
            # Changement de regex (Nicolas Chachereau)
            if ($this->getOpt('active_fr_syntax') && $type != null && $type !== 'pre' && $type !== 'hr') {
                $line = preg_replace('%[ ]+([:?!;\x{00BB}](\s|$))%u', '&nbsp;$1', (string) $line);
                $line = preg_replace('%(\x{00AB})[ ]+%u', '$1&nbsp;', (string) $line);
            }

            $html .= $line;
        }

        return trim($html);
    }

    /**
     * Parse a full line
     *
     * @param      int          $i      Wiki line index
     * @param      null|string  $type   The type
     * @param      null|string  $mode   The mode
     * @param      null|string  $attr   The attribute
     *
     * @return     false|string|null  The line.
     */
    private function __getLine(int $i, ?string &$type, ?string &$mode, ?string &$attr): false|string|null
    {
        $current_type = $type;
        $current_mode = $mode;

        $type = null;
        $mode = null;
        $attr = null;

        // Ligne vide
        if (empty($this->wiki_lines[$i])) {
            return false;
        }

        $line = htmlspecialchars($this->wiki_lines[$i], ENT_NOQUOTES);

        // Ligne vide
        if ($this->getOpt('active_empty') && preg_match('/^øøø(.*)$/', $line, $cap)) {
            // Peut contenir un numéro de macro
            $type = null;
            $line = trim($cap[1]);
        }
        // Titre
        elseif ($this->getOpt('active_title') && preg_match('/^([!]{1,4})(.*?)(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'title';
            $mode = (string) strlen($cap[1]);
            $line = trim($cap[2]);
            if (isset($cap[4])) {
                // Attribut HTML présent
                $attr = $cap[4];
            }
        }
        // Ligne HR
        elseif ($this->getOpt('active_hr') && preg_match('/^[-]{4}[- ]*?(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'hr';
            $line = null;
            if (isset($cap[2])) {
                // Attribut HTML présent
                $attr = $cap[2];
            }
        }
        // Blockquote
        elseif ($this->getOpt('active_quote') && preg_match('/^(&gt;|;:)(.*?)(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'blockquote';
            $line = trim($cap[2]);
            if (isset($cap[4])) {
                // Attribut HTML présent
                $attr = $cap[4];
            }
        }
        // Liste
        elseif ($this->getOpt('active_lists') && preg_match('/^([*#]+)(.*?)(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'list';
            $mode = $cap[1];
            if (isset($cap[4])) {
                // Attribut HTML présent
                $attr = $cap[4];
            }
            $valid = true;

            // Vérification d'intégrité
            $dl    = ($type != $current_type) ? 0 : strlen((string) $current_mode);
            $d     = strlen($mode);
            $delta = $d - $dl;

            if ($delta < 0 && !str_starts_with((string) $current_mode, $mode)) {
                $valid = false;
            }
            if ($delta > 0 && $type == $current_type && !str_starts_with($mode, (string) $current_mode)) {
                $valid = false;
            }
            if ($delta == 0 && $mode != $current_mode) {
                $valid = false;
            }
            if ($delta > 1) {
                $valid = false;
            }

            if (!$valid) {
                $type = 'p';
                $mode = null;
                $line = '<br>' . $line;
            } else {
                $line = trim($cap[2]);
            }
        } elseif ($this->getOpt('active_defl') && preg_match('/^([=|:]{1}) (.*?)(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'defl';
            $mode = $cap[1];
            $line = trim($cap[2]);
            if (isset($cap[4])) {
                // Attribut HTML présent
                $attr = $cap[4];
            }
        }
        // Préformaté
        elseif ($this->getOpt('active_pre') && preg_match('/^[ ]{1}(.*?)(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'pre';
            $line = $cap[1];
            if (isset($cap[3])) {
                // Attribut HTML présent
                $attr = trim($cap[3]);
            }
        }
        // Aside
        elseif ($this->getOpt('active_aside') && preg_match('/^[\)]{1}(.*?)(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'aside';
            $line = trim($cap[1]);
            if (isset($cap[3])) {
                // Attribut HTML présent
                $attr = $cap[3];
            }
        }
        // Details
        elseif ($this->getOpt('active_details') && preg_match('/^[\|]{1}(.*?)(§§(.*)§§)?$/', $line, $cap)) {
            $type = 'details';
            $line = trim($cap[1]);
            $mode = $line === '' ? '0' : '1';
            if (isset($cap[3])) {
                // Attribut HTML présent
                $attr = $cap[3];
            }
        }
        // Paragraphe
        else {
            $type = 'p';
            if (preg_match('/^\\\((?:(' . implode('|', $this->linetags) . ')).*)$/', $line, $cap)) {
                $line = $cap[1];
            }
            if (preg_match('/^(.*?)(§§(.*)§§)?$/', $line, $cap)) {
                $line = $cap[1];
                if (isset($cap[3])) {
                    // Attribut HTML présent
                    $attr = $cap[3];
                }
            }
            $line = trim($line);
        }

        return $line;
    }

    /**
     * Cope with opening HTML part of a block
     *
     * @param      null|string      $type           The type
     * @param      null|string      $mode           The mode
     * @param      null|string      $previous_type  The pre type
     * @param      null|string      $previous_mode  The pre mode
     * @param      null|string      $attr           The attribute
     */
    private function __openLine(?string $type, ?string $mode, ?string $previous_type, ?string $previous_mode, ?string $attr = null): string
    {
        $open = ($type !== $previous_type);

        $attr_parent = $attr_child = '';
        if ($attr && $attrs = $this->__splitTagsAttr($attr)) {
            $attr_child  = $attrs[0] ? ' ' . $attrs[0] : '';
            $attr_parent = isset($attrs[1]) ? ' ' . $attrs[1] : '';
        }

        if ($open && $type == 'p') {
            return "\n<p" . $attr_child . '>';
        } elseif ($open && $type == 'blockquote') {
            return "\n<blockquote" . $attr_child . '><p>';
        } elseif (($open || $mode !== $previous_mode) && $type == 'title') {
            $fl = $this->getOpt('first_title_level');
            $fl += 3;
            $l = $fl - (int) $mode;

            return "\n<h" . ($l) . $attr_child . '>';
        } elseif ($open && $type == 'pre') {
            return "\n<pre" . $attr_child . '>';
        } elseif ($open && $type == 'aside') {
            return "\n<aside" . $attr_child . '><p>';
        } elseif ($open && $type == 'details' && $mode == '0') {
            return "\n</details>";
        } elseif ($open && $type == 'details' && $mode == '1') {
            return "\n<details" . $attr_child . '><summary>';
        } elseif ($open && $type == 'hr') {
            return "\n<hr" . $attr_child . '>';
        } elseif ($type == 'list') {
            $dl    = ($open) ? 0 : strlen((string) $previous_mode);
            $d     = strlen((string) $mode);
            $delta = $d - $dl;
            $res   = '';

            if ($delta > 0) {
                if (str_ends_with((string) $mode, '*')) {
                    $res .= '<ul' . $attr_parent . ">\n";
                } else {
                    $res .= '<ol' . $attr_parent . ">\n";
                }
            } elseif ($delta < 0) {
                $res .= "</li>\n";
                for ($j = 0; $j < abs($delta); $j++) {
                    if (substr((string) $previous_mode, (-$j - 1), 1) === '*') {
                        $res .= "</ul>\n</li>\n";
                    } else {
                        $res .= "</ol>\n</li>\n";
                    }
                }
            } else {
                $res .= "</li>\n";
            }

            return $res . '<li' . $attr_child . '>';
        } elseif ($type == 'defl') {
            $res = ($previous_mode !== '=' && $previous_mode !== ':' ? '<dl' . $attr_parent . ">\n" : '');
            if ($previous_mode == '=') {
                $res .= "</dt>\n";
            } elseif ($previous_mode == ':') {
                $res .= "</dd>\n";
            }
            if ($mode == '=') {
                $res .= '<dt' . $attr_child . '>';
            } else {
                $res .= '<dd' . $attr_child . '>';
            }

            return $res;
        }

        return '';
    }

    /**
     * Cope with closing HTML part of a block
     *
     * @param      null|string  $type           The type
     * @param      null|string  $mode           The mode
     * @param      null|string  $previous_type  The pre type
     * @param      null|string  $previous_mode  The pre mode
     */
    private function __closeLine(?string $type, ?string $mode, ?string $previous_type, ?string $previous_mode): string
    {
        $close = ($type !== $previous_type);

        if ($close && $previous_type == 'p') {
            return "</p>\n";
        } elseif ($close && $previous_type == 'blockquote') {
            return "</p></blockquote>\n";
        } elseif (($close || $mode !== $previous_mode) && $previous_type == 'title') {
            $fl = $this->getOpt('first_title_level');
            $fl += 3;
            $l = $fl - (int) $previous_mode;

            return '</h' . ($l) . ">\n";
        } elseif ($close && $previous_type == 'pre') {
            return "</pre>\n";
        } elseif ($close && $previous_type == 'aside') {
            return "</p></aside>\n";
        } elseif ($close && $previous_type == 'details' && $previous_mode == '1') {
            return "</summary>\n";
        } elseif ($close && $previous_type == 'list') {
            $res = '';
            for ($j = 0; $j < strlen((string) $previous_mode); $j++) {
                if (substr((string) $previous_mode, (-$j - 1), 1) === '*') {
                    $res .= "</li>\n</ul>\n";
                } else {
                    $res .= "</li>\n</ol>\n";
                }
            }

            return $res;
        } elseif ($close && $previous_type == 'defl') {
            $res = '';
            if ($previous_mode == '=') {
                $res .= "</dt>\n</dl>\n";
            } else {
                $res .= "</dd>\n</dl>\n";
            }

            return $res;
        }

        return "\n";
    }

    /* Inline
       --------------------------------------------------- */

    /**
     * Parse inline tags in a line
     *
     * @param      string           $str         The string
     * @param      array<string>    $allow_only  The allow only
     */
    private function __inlineWalk(string $str, ?array $allow_only = null): string
    {
        $tree = preg_split($this->tag_pattern, $str, -1, PREG_SPLIT_DELIM_CAPTURE);

        $html = '';
        if ($tree) {
            $counter = count($tree);
            for ($i = 0; $i < $counter; $i++) {
                $attr = '';
                if (in_array($tree[$i], $this->open_tags) && ($allow_only == null || in_array(array_search($tree[$i], $this->open_tags), $allow_only))) {
                    $tag      = array_search($tree[$i], $this->open_tags);
                    $tag_type = 'open';

                    if ($tag) {
                        if (($tidy = $this->__makeTag($tree, $tag, $i, $i, $attr, $tag_type)) !== false) {
                            if ($tag !== '') {
                                $html .= '<' . $tag . $attr . '>';
                            }
                            $html .= $tidy;
                        } else {
                            $html .= $tree[$i];
                        }
                    }
                } else {
                    $html .= $tree[$i];
                }
            }
        }

        // Suppression des echappements
        $html = str_replace($this->escape_table, $this->all_tags, $html);

        return $html;
    }

    /**
     * Parse an inline tag.
     *
     * @param      array<string>        $tree               The tree
     * @param      string               $tag                The tag
     * @param      int                  $position           The position
     * @param      int                  $next_position      The next position
     * @param      string               $attr               The attribute
     * @param      string               $type               The type
     *
     * @return     bool|string|null
     */
    private function __makeTag(array &$tree, string &$tag, int $position, int &$next_position, string &$attr, string &$type)
    {
        $html   = '';
        $closed = false;

        $itag = $this->close_tags[$tag];

        // Recherche fermeture
        $counter = count($tree);
        for ($i = $position + 1; $i < $counter; $i++) {
            if ($tree[$i] == $itag) {
                $closed = true;

                break;
            }
        }

        // Résultat
        if ($closed) {
            $counter = count($tree);
            for ($i = $position + 1; $i < $counter; $i++) {
                if ($tree[$i] != $itag) {
                    $html .= $tree[$i];
                } else {
                    switch ($tag) {
                        case 'a':
                            $html = $this->__parseLink($html, $tag, $attr, $type);

                            break;
                        case 'img':
                            $type = 'close';
                            if (($html = $this->__parseImg($html, $attr, $tag)) !== null) {
                                $type = 'open';
                            }

                            break;
                        case 'abbr':
                            $html = $this->__parseAcronym($html, $attr);

                            break;
                        case 'q':
                            $html = $this->__parseQ($html, $attr);

                            break;
                        case 'i':
                            $html = $this->__parseI($html, $attr);

                            break;
                        case 'anchor':
                            $tag  = 'a';
                            $html = $this->__parseAnchor($html, $attr);

                            break;
                        case 'note':
                            $tag  = '';
                            $html = $this->__parseNote($html);

                            break;
                        case 'inline':
                            $tag  = '';
                            $html = $this->__parseInlineHTML($html);

                            break;
                        case 'word':
                            $html = $this->parseWikiWord($html, $tag);

                            break;
                        default:
                            $html = $this->__inlineWalk($html);

                            break;
                    }

                    if ($type === 'open' && $tag !== '') {
                        $html .= '</' . $tag . '>';
                    }
                    $next_position = $i;

                    break;
                }

                // Recherche attributs
                if (preg_match('/^(.*?)(§(.*)§)?$/', $html, $cap)) {
                    $html = $cap[1];
                    if (isset($cap[3])) {
                        $attr .= ' ' . $cap[3];
                    }
                }
            }

            return $html;
        }

        return false;
    }

    /**
     * Splits a tags attribute.
     *
     * @param      string  $str    The string
     *
     * @return     array<string>   of tags attributes
     */
    private function __splitTagsAttr(string $str): array
    {
        $res = preg_split('/(?<!\\\)\|/', $str);

        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k] = str_replace("\|", '|', $v);
            }
        } else {
            $res = [];
        }

        return $res;
    }

    /**
     * Antispam helper (Jérôme Lipowicz)
     *
     * @param      string  $str    The string
     */
    private function __antiSpam(string $str): string
    {
        $encoded = bin2hex($str);
        $encoded = chunk_split($encoded, 2, '%');

        return '%' . substr($encoded, 0, strlen($encoded) - 1);
    }

    /**
     * Parse an URI
     *
     * @param      string  $str    The string
     * @param      string  $tag    The tag
     * @param      string  $attr   The attribute
     * @param      string  $type   The type
     */
    private function __parseLink(string $str, string &$tag, string &$attr, string &$type): ?string
    {
        $n_str    = $this->__inlineWalk($str, ['abbr', 'img', 'em', 'strong', 'i', 'code', 'del', 'ins', 'mark', 'sup', 'sub', 'span']);
        $data     = $this->__splitTagsAttr($n_str);
        $no_image = false;
        $url      = '';
        $content  = '';
        $lang     = '';
        $title    = '';

        // Only URL in data
        if (count($data) == 1) {
            $url     = trim($str);
            $content = strlen($url) > 35 ? substr($url, 0, 35) . '...' : $url;
            $lang    = '';
            $title   = $url;
        } elseif (count($data) > 1) {
            $url      = trim($data[1]);
            $content  = $data[0];
            $lang     = (empty($data[2])) ? '' : $this->protectAttr($data[2], true);
            $title    = (empty($data[3])) ? '' : $data[3];
            $no_image = isset($data[4]) && $data[4] !== '' && (bool) $data[4];
        }

        // Remplacement si URL spéciale
        $this->__specialUrls($url, $content, $lang, $title);

        // On vire les &nbsp; dans l'url
        $url = str_replace('&nbsp;', ' ', $url);

        if (preg_match('/^(.+)[.](gif|jpg|jpeg|png)$/', $url) && !$no_image && $this->getOpt('active_auto_img')) {
            // On ajoute les dimensions de l'image si locale
            // Idée de Stephanie
            $img_size = null;
            if (!preg_match('#[a-zA-Z]+://#', $url)) {
                $path_img = preg_match('#^/#', $url) ? $_SERVER['DOCUMENT_ROOT'] . $url : $url;
                $img_size = @getimagesize($path_img);
            }

            $attr .= ' src="' . $this->protectAttr($this->protectUrls($url)) . '"' .
            $attr .= (count($data) > 1) ? ' alt="' . $this->protectAttr($content) . '"' : ' alt=""';
            $attr .= ($lang !== '') ? ' lang="' . $lang . '"' : '';
            $attr .= ($title !== '') ? ' title="' . $this->protectAttr($title) . '"' : '';
            $attr .= (is_array($img_size)) ? ' ' . $img_size[3] : '';

            $tag  = 'img';
            $type = 'close';

            return null;
        }
        if ($this->getOpt('active_antispam') && preg_match('/^mailto:/', $url)) {
            $content = $content == $url ? preg_replace('%^mailto:%', '', $content) : $content;
            $url     = 'mailto:' . $this->__antiSpam(substr($url, 7));
        }

        $attr .= ' href="' . $this->protectAttr($this->protectUrls($url)) . '"';
        $attr .= $lang !== '' ? ' hreflang="' . $lang . '"' : '';
        $attr .= ($title) ? ' title="' . $this->protectAttr($title) . '"' : '';

        return $content;
    }

    /**
     * Cope with special URI
     *
     * @param      string  $url      The url
     * @param      string  $content  The content
     * @param      string  $lang     The language
     * @param      string  $title    The title
     */
    private function __specialUrls(string &$url, string &$content, string &$lang, string &$title): void
    {
        foreach ($this->functions as $k => $v) {
            if (str_starts_with($k, 'url:') && str_starts_with($url, substr($k, 4))) {
                $res = call_user_func($v, $url, $content);

                $url     = $res['url']     ?? $url;
                $content = $res['content'] ?? $content;
                $lang    = $res['lang']    ?? $lang;
                $title   = $res['title']   ?? $title;

                break;
            }
        }
    }

    /**
     * Parge an image
     *
     * @param      string  $str    The string
     * @param      string  $attr   The attribute
     * @param      string  $tag    The tag
     */
    private function __parseImg(string $str, string &$attr, string &$tag): ?string
    {
        $data = $this->__splitTagsAttr($str);

        $alt          = '';
        $current_attr = $attr;
        $align_attr   = '';
        $url          = $data[0];
        if (!empty($data[1])) {
            $alt = $data[1];
        }

        $attr .= ' src="' . $this->protectAttr($this->protectUrls($url)) . '"';
        $attr .= ' alt="' . $this->protectAttr($alt) . '"';

        if (!empty($data[2])) {
            $data[2] = strtoupper($data[2]);
            $style   = '';
            if ($data[2] === 'G' || $data[2] === 'L') {
                $style = $this->getOpt('img_style_left');
            } elseif ($data[2] === 'D' || $data[2] === 'R') {
                $style = $this->getOpt('img_style_right');
            } elseif ($data[2] === 'C') {
                $style = $this->getOpt('img_style_center');
            }
            if ($style != '') {
                $align_attr = ' ' . $style;
            }
        }

        if (empty($data[4])) {
            $attr .= $align_attr;
        }
        if (!empty($data[3])) {
            $attr .= ' title="' . $this->protectAttr($data[3]) . '"';
        }

        if (!empty($data[4])) {
            $tag = 'figure';
            $img = '<img' . $attr . '>';
            $img .= '<figcaption>' . $this->protectAttr($data[4]) . '</figcaption>';

            $attr = $current_attr . $align_attr;

            return $img;
        }

        return null;
    }

    /**
     * Parse a quote element
     *
     * @param      string  $str    The string
     * @param      string  $attr   The attribute
     */
    private function __parseQ(string $str, string &$attr): string
    {
        $str  = $this->__inlineWalk($str);
        $data = $this->__splitTagsAttr($str);

        $content = $data[0];
        $lang    = (empty($data[1])) ? '' : $this->protectAttr($data[1], true);

        $attr .= ($lang === '') ? '' : ' lang="' . $lang . '"';
        $attr .= (empty($data[2])) ? '' : ' cite="' . $this->protectAttr($this->protectUrls($data[2])) . '"';

        return $content;
    }

    /**
     * Parse an i element
     *
     * @param      string  $str    The string
     * @param      string  $attr   The attribute
     */
    private function __parseI(string $str, string &$attr): string
    {
        $str  = $this->__inlineWalk($str);
        $data = $this->__splitTagsAttr($str);

        $content = $data[0];
        $lang    = (empty($data[1])) ? '' : $this->protectAttr($data[1], true);

        $attr .= ($lang === '') ? '' : ' lang="' . $lang . '"';

        return $content;
    }

    /**
     * Parse an anchro
     *
     * @param      string  $str    The string
     * @param      string  $attr   The attribute
     */
    private function __parseAnchor(string $str, string &$attr): string
    {
        $name = $this->protectAttr($str, true);

        if ($name !== '') {
            $attr .= ' id="' . $name . '"';
        }

        return '';
    }

    /**
     * Parse a footnote
     *
     * @param      string  $str    The string
     */
    private function __parseNote(string $str): string
    {
        $i                     = count($this->foot_notes) + 1;
        $id                    = $this->getOpt('note_prefix') . '-' . $i;
        $this->foot_notes[$id] = $this->__inlineWalk($str);

        return '<sup>\[<a href="#' . $id . '" id="rev-' . $id . '">' . $i . '</a>\]</sup>';
    }

    /**
     * Parse inline HTML
     *
     * @param      string  $str    The string
     */
    private function __parseInlineHTML(string $str): string
    {
        return str_replace(['&gt;', '&lt;'], ['>', '<'], $str);
    }

    /**
     * Parse an acronym
     *
     * @param      string  $str    The string
     * @param      string  $attr   The attribute
     */
    private function __parseAcronym(string $str, string &$attr): string
    {
        $data = $this->__splitTagsAttr($str);

        $acronym = $data[0];
        $title   = $lang = '';

        if (count($data) > 1) {
            $title = $data[1];
            $lang  = (empty($data[2])) ? '' : $this->protectAttr($data[2], true);
        }

        if ($title == '' && !empty($this->acro_table[$acronym])) {
            $title = $this->acro_table[$acronym];
        }

        $attr .= ($title) ? ' title="' . $this->protectAttr($title) . '"' : '';
        $attr .= ($lang !== '') ? ' lang="' . $lang . '"' : '';

        return $acronym;
    }

    /**
     * Gets the acronyms.
     *
     * @return     array<string, string>  The acronyms.
     */
    private function __getAcronyms(): array
    {
        $file = $this->getOpt('acronyms_file');
        $res  = [];

        if (file_exists($file) && ($fc = @file($file)) !== false) {
            foreach ($fc as $v) {
                $v = trim($v);
                if ($v !== '') {
                    $p = strpos($v, ':');
                    if ($p !== false) {
                        $K = trim(substr($v, 0, $p));
                        $V = trim(substr($v, ($p + 1)));

                        if ($K !== '') {
                            $res[$K] = $V;
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * Parse wiki words (legacy)
     *
     * @param      string  $str    The string
     * @param      string  $tag    The tag
     */
    private function parseWikiWord(string $str, string &$tag): string
    {
        $tag = '';

        if (isset($this->functions['wikiword'])) {
            return call_user_func($this->functions['wikiword'], $str);
        }

        return $str;
    }

    /**
     * Protect attributes
     *
     * @param      string  $str    The string
     * @param      bool    $name   The name
     */
    private function protectAttr(string $str, bool $name = false): string
    {
        if ($name && !preg_match('/^[A-Za-z][A-Za-z0-9_:.-]*$/', $str)) {
            return '';
        }

        return str_replace(["'", '"'], ['&#039;', '&quot;'], $str);
    }

    /**
     * Protect URI
     *
     * @param      string  $str    The string
     */
    private function protectUrls(string $str): string
    {
        if (preg_match('/^javascript:/', $str)) {
            $str = '#';
        }

        return $str;
    }

    /**
     * Parse auto BR
     *
     * @param      array<string>         $matches      The matches
     */
    private function __autoBR(array $matches): string
    {
        return $matches[1] . str_replace("\n", "<br>\n", $matches[2]) . $matches[3];
    }

    /* Macro
    --------------------------------------------------- */

    /**
     * Prepare future macro treatment
     *
     * @param      array<string>   $matches  The matches
     */
    private function __getMacro(array $matches): string
    {
        // Stack the macro name
        $this->macros[] = str_replace('\"', '"', $matches[1]);

        return 'øøø' . self::MACRO_PREFIX . (count($this->macros) - 1) . self::MACRO_SUFFIX;
    }

    /**
     * Execute a macro.
     *
     * @param      array<string>  $matches     The matches
     */
    private function __putMacro(array $matches): string
    {
        $macro_id = (int) $matches[1];
        if (isset($this->macros[$macro_id])) {
            $content = str_replace("\r", '', $this->macros[$macro_id]);

            $lines = explode("\n", $content);

            # première ligne, premier mot
            $first_line = trim($lines[0]);
            $first_word = $first_line;

            if ($first_line !== '') {
                if (str_contains($first_line, ' ')) {
                    $first_word = substr($first_line, 0, (int) strpos($first_line, ' '));
                }
                $content = implode("\n", array_slice($lines, 1));
            }

            if ($lines[0] === "\n") {
                $content = implode("\n", array_slice($lines, 1));
            }

            if ($first_word && isset($this->functions[self::MACRO_FN_PREFIX . $first_word])) {
                return call_user_func($this->functions[self::MACRO_FN_PREFIX . $first_word], $content, $first_line);
            }

            # Si on n'a rien pu faire, on retourne le tout sous
            # forme de <pre>
            return '<pre>' . htmlspecialchars($this->macros[$macro_id]) . '</pre>';
        }

        return '';
    }

    /**
     * Macro ///html callback
     *
     * @param      string  $content      content
     */
    private function __macroHTML($content): string
    {
        return $content;
    }

    /* Aide et debug
       --------------------------------------------------- */

    /**
     * Return wiki syntax help
     */
    public function help(): string
    {
        $help      = [];
        $help['b'] = [];
        $help['i'] = [];

        $help['b'][] = 'Laisser une ligne vide entre chaque bloc <em>de même nature</em>.';
        $help['b'][] = '<strong>Paragraphe</strong> : du texte et une ligne vide';

        if ($this->getOpt('active_title')) {
            $help['b'][] = '<strong>Titre</strong> : <code>!!!</code>, <code>!!</code>, ' .
                '<code>!</code> pour des titres plus ou moins importants';
        }

        if ($this->getOpt('active_hr')) {
            $help['b'][] = '<strong>Trait horizontal</strong> : <code>----</code>';
        }

        if ($this->getOpt('active_lists')) {
            $help['b'][] = '<strong>Liste</strong> : ligne débutant par <code>*</code> ou ' .
                '<code>#</code>. Il est possible de mélanger les listes ' .
                '(<code>*#*</code>) pour faire des listes de plusieurs niveaux. ' .
                'Respecter le style de chaque niveau';
        }

        if ($this->getOpt('active_defl')) {
            $help['b'][] = '<strong>Liste de définitions</strong> : terme(s) débutant(s) par <code>=</code>, ' .
                'définition(s) débutant(s) par <code>:</code>.';
        }

        if ($this->getOpt('active_pre')) {
            $help['b'][] = '<strong>Texte préformaté</strong> : espace devant chaque ligne de texte';
        }

        if ($this->getOpt('active_quote')) {
            $help['b'][] = '<strong>Bloc de citation</strong> : <code>&gt;</code> ou ' .
                '<code>;:</code> devant chaque ligne de texte';
        }

        if ($this->getOpt('active_aside')) {
            $help['b'][] = '<aside>Note de côté</aside> : <code>)</code> devant chaque ligne de texte';
        }

        if ($this->getOpt('active_details')) {
            $help['b'][] = '<details><summary>Sommaire</summary> ... </details> : <code>|</code> en première ligne avec le texte du sommaire, <code>|</code> en derniere ligne du bloc';
        }

        if ($this->getOpt('active_fr_syntax')) {
            $help['i'][] = 'La correction de ponctuation est active. Un espace ' .
                'insécable remplacera automatiquement tout espace ' .
                'précédant les marques ";","?",":" et "!".';
        }

        if ($this->getOpt('active_em')) {
            $help['i'][] = '<strong>Emphase</strong> : deux apostrophes <code>\'\'texte\'\'</code>';
        }

        if ($this->getOpt('active_strong')) {
            $help['i'][] = '<strong>Forte emphase</strong> : deux soulignés <code>__texte__</code>';
        }

        if ($this->getOpt('active_br')) {
            $help['i'][] = '<strong>Retour forcé à la ligne</strong> : <code>%%%</code>';
        }

        if ($this->getOpt('active_ins')) {
            $help['i'][] = '<strong>Insertion</strong> : deux plus <code>++texte++</code>';
        }

        if ($this->getOpt('active_del')) {
            $help['i'][] = '<strong>Suppression</strong> : deux moins <code>--texte--</code>';
        }

        if ($this->getOpt('active_mark')) {
            $help['i'][] = '<mark>Texte marqué</mark> : deux guillemets <code>""texte""</code>';
        }

        if ($this->getOpt('active_sup')) {
            $help['i'][] = '<sup>Exposant</sup> : un accent circonflexe <code>^texte^</code>';
        }

        if ($this->getOpt('active_sub')) {
            $help['i'][] = '<sub>Indice</sub> : un souligné <code>,,texte,,</code>';
        }

        if ($this->getOpt('active_urls')) {
            $help['i'][] = '<strong>Lien</strong> : <code>[url]</code>, <code>[nom|url]</code>, ' .
                '<code>[nom|url|langue]</code> ou <code>[nom|url|langue|titre]</code>.';

            $help['i'][] = '<strong>Image</strong> : comme un lien mais avec une extension d\'image.' .
                '<br>Pour désactiver la reconnaissance d\'image mettez 0 dans un dernier ' .
                'argument. Par exemple <code>[image|image.gif||0]</code> fera un lien vers l\'image au ' .
                'lieu de l\'afficher.' .
                '<br>Il est conseillé d\'utiliser la nouvelle syntaxe.';
        }

        if ($this->getOpt('active_img')) {
            $help['i'][] = '<strong>Image</strong> (nouvelle syntaxe) : ' .
                '<code>((url|texte alternatif))</code>, ' .
                '<code>((url|texte alternatif|position))</code> ou ' .
                '<code>((url|texte alternatif|position|description longue))</code>. ' .
                '<br>La position peut prendre les valeur L ou G (gauche), R ou D (droite) ou C (centré).';
        }

        if ($this->getOpt('active_anchor')) {
            $help['i'][] = '<strong>Ancre</strong> : <code>~ancre~</code>';
        }

        if ($this->getOpt('active_acronym')) {
            $help['i'][] = '<strong>Acronyme</strong> : <code>??acronyme??</code> ou ' .
                '<code>??acronyme|titre??</code>';
        }

        if ($this->getOpt('active_q')) {
            $help['i'][] = '<strong>Citation</strong> : <code>{{citation}}</code>, ' .
                '<code>{{citation|langue}}</code> ou <code>{{citation|langue|url}}</code>';
        }

        if ($this->getOpt('active_i')) {
            $help['i'][] = '<strong>texte différencié</strong> : <code>££texte différencié££</code>, ' .
                '<code>££texte différencié|langue££</code>';
        }

        if ($this->getOpt('active_code')) {
            $help['i'][] = '<strong>Code</strong> : <code>@@code ici@@</code>';
        }

        if ($this->getOpt('active_footnotes')) {
            $help['i'][] = '<strong>Note de bas de page</strong> : <code>$$Corps de la note$$</code>';
        }

        $res = '<dl class="wikiHelp">';

        $res .= '<dt>Blocs</dt><dd>';
        $res .= '<ul><li>';
        $res .= implode('&nbsp;;</li><li>', $help['b']);
        $res .= '.</li></ul>';
        $res .= '</dd>';

        $res .= '<dt>Éléments en ligne</dt><dd>';
        if ($help['i'] !== []) {
            $res .= '<ul><li>';
            $res .= implode('&nbsp;;</li><li>', $help['i']);
            $res .= '.</li></ul>';
        }
        $res .= '</dd>';

        return $res . '</dl>';
    }
}
