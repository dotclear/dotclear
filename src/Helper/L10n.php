<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper {
    /**
     * @class L10n
     *
     * Localization utilities
     */
    class L10n
    {
        /**
         * Translations files loaded
         *
         * @var        array<string>
         */
        public static array $files = [];

        /**
         * Locales loaded
         *
         * @var        array<string, string|array<string>>
         */
        public static array $locales = [];

        /// @name Languages properties
        //@{

        /**
         * @var        array<array<mixed>>
         */
        protected static array $languages_definitions = [];

        /**
         * @var        array<string, string>
         */
        protected static array $languages_name = [];

        /**
         * @var        array<string, string>
         */
        protected static array $languages_textdirection = [];

        /**
         * @var        array<string, null|int>
         */
        protected static array $languages_pluralsnumber = [];

        /**
         * @var        array<string, null|string>
         */
        protected static array $languages_pluralexpression = [];
        //@}

        /// @name Current language properties
        //@{

        /**
         * Language code
         */
        protected static ?string $language_code = null;

        /**
         * Language name
         */
        protected static string $language_name;

        /**
         * Text direction according to a language code
         */
        protected static string $language_textdirection;

        /**
         * Number of plurals according to a language code
         */
        protected static int $language_pluralsnumber = 1;

        /**
         * Plural expression according to a language code
         */
        protected static string $language_pluralexpression = '';

        /**
         * Find plural msgstr index from gettext expression
         *
         * @var        callable
         */
        protected static $language_pluralfunction;
        //@}

        public static function bootstrap(): void
        {
            // May be used to have __() function defined if necessary via autoload system.
        }

        /**
         * L10N initialization
         *
         * Create global arrays for L10N stuff. Should be called before any work
         * with other methods. For plural-forms, __l10n values can now be array.
         *
         * @param string|null $code Language code to work with
         */
        public static function init(?string $code = 'en'): void
        {
            self::$locales = self::$files = [];

            /*
             * @deprecated Since 1.3
             *
             * Used by generated *.lang.php in old 3rd party modules
             */
            $GLOBALS['__l10n'] = &self::$locales;

            /*
             * @deprecated Since 1.3
             *
             * Used by generated *.lang.php in old 3rd party modules
             */
            $GLOBALS['__l10n_files'] = &self::$files;

            self::lang($code);
        }

        /**
         * Set a language to work on or return current working language code
         *
         * This set up language properties to manage plurals form.
         * Change of language code not reset global array of L10N stuff.
         *
         * @param string $code Language code
         *
         * @return string Current language code
         */
        public static function lang(?string $code = null): string
        {
            if ($code !== null && self::$language_code != $code && self::isCode($code)) {
                self::$language_code             = $code;
                self::$language_name             = self::getLanguageName($code);
                self::$language_textdirection    = self::getLanguageTextDirection($code);
                self::$language_pluralsnumber    = self::getLanguagePluralsNumber($code);
                self::$language_pluralexpression = self::getLanguagePluralExpression($code);

                self::$language_pluralfunction = self::createPluralFunction(
                    self::$language_pluralsnumber,
                    self::$language_pluralexpression
                );
            }

            return (string) self::$language_code;
        }

        /**
         * Translate a string
         *
         * Returns a translated string of $singular
         * or $plural according to a number if it is set.
         * If translation is not found, returns the string.
         *
         * @param string    $singular   Singular form of the string
         * @param string    $plural     Plural form of the string (optionnal)
         * @param integer   $count      Context number for plural form (optionnal)
         *
         * @return string Translated string
         */
        public static function trans(string $singular, ?string $plural = null, ?int $count = null): string
        {
            if ($singular === '') {
                // If no string to translate, return no string
                return '';
            } elseif ((self::$locales === [] || !array_key_exists($singular, self::$locales)) && is_null($count)) {
                // If no l10n translation loaded or exists
                return $singular;
            } elseif ($plural === null || $count === null || self::$language_pluralsnumber == 1) {
                // If no $plural form or if current language has no plural form return $singular translation
                $t = empty(self::$locales[$singular]) ? $singular : self::$locales[$singular];

                return is_array($t) ? $t[0] : $t;
            }

            // Else return translation according to $count
            $i = self::index($count);

            if ($i > 0 && !empty(self::$locales[$plural])) {
                // If it is a plural and translation exists in "singular" form
                $t = self::$locales[$plural];

                return is_array($t) ? $t[0] : $t;
            } elseif (!empty(self::$locales[$singular])
                    && is_array(self::$locales[$singular])
                    && array_key_exists($i, self::$locales[$singular])
                    && (isset(self::$locales[$singular][$i]) && self::$locales[$singular][$i] !== '')) {
                // If it is plural and index exists in plurals translations
                return self::$locales[$singular][$i];
            }

            // Else return input string according to "en" plural form
            return $i > 0 ? $plural : $singular;
        }

        /**
         * Retrieve plural index from input number
         *
         * @param integer $count Number to take account
         *
         * @return integer Index of plural form
         */
        public static function index(int $count): int
        {
            return call_user_func(self::$language_pluralfunction, $count);
        }

        /**
         * Add a file
         *
         * Adds a l10n file in translation strings. $file should be given without
         * extension. This method will look for $file.lang.php and $file.po (in this
         * order) and retrieve the first one found.
         * We don't care about language (and plurals forms) of the file.
         *
         * @param string    $file        Filename (without extension)
         *
         * @return boolean True on success
         */
        public static function set(string $file): bool
        {
            $po_file  = $file . '.po';
            $php_file = $file . '.lang.php';

            if (file_exists($php_file)) {
                require $php_file;
            } elseif (($tmp = self::getPoFile($po_file)) !== false) {
                self::$files[] = $po_file;
                self::$locales = $tmp + self::$locales; // "+" erase numeric keys unlike array_merge
            } else {
                return false;
            }

            return true;
        }

        /**
         * L10N file
         *
         * Returns a file path for a file, a directory and a language.
         * If $dir/$lang/$file is not found, it will check if $dir/en/$file
         * exists and returns the result. Returns false if no file were found.
         *
         * @param string    $dir        Directory
         * @param string    $file       File
         * @param string    $lang       Language
         *
         * @return string|false         File path or false
         */
        public static function getFilePath(string $dir, string $file, string $lang)
        {
            $f = $dir . '/' . $lang . '/' . $file;
            if (!file_exists($f)) {
                $f = $dir . '/en/' . $file;
            }

            return file_exists($f) ? $f : false;
        }

        /// @name Gettext PO methods
        //@{
        /**
         * Load gettext file
         *
         * Returns an array of strings found in a given gettext (.po) file
         *
         * @param string    $file        Filename

         * @return array<mixed>|false
         */
        public static function getPoFile(string $file): false|array
        {
            if (($m = self::parsePoFile($file)) === false) {
                return false;
            }

            if (empty($m[1])) {
                return [];
            }

            // Keep singular id and translations, remove headers and comments
            $r = [];
            foreach ($m[1] as $v) {
                if (isset($v['msgid']) && isset($v['msgstr'])) {
                    $r[$v['msgid']] = $v['msgstr'];
                }
            }

            return $r;
        }

        /**
         * Generates a PHP file from a po file
         *
         * Return a boolean depending on success or failure
         *
         * @param      string $file             File
         * @param      string $license_block    Optional license block to add at the beginning
         *
         * @return     bool     true on success
         */
        public static function generatePhpFileFromPo(string $file, string $license_block = ''): bool
        {
            $po_file  = $file . '.po';
            $php_file = $file . '.lang.php';

            $strings  = self::getPoFile($po_file);
            $fcontent = "<?php\n" .
                $license_block . "\n" .
                "#\n" .
                "# DOT NOT MODIFY THIS FILE !\n" .
                "#\n" .
                "\n" .
                'use ' . self::class . ";\n" .
                "\n";

            if ($strings !== false) {
                foreach ($strings as $vo => $tr) {
                    $vo = str_replace("'", "\\'", (string) $vo);
                    if (is_array($tr)) {
                        $items = [];
                        foreach ($tr as $t) {
                            $t       = str_replace("'", "\\'", $t);
                            $items[] = '\'' . $t . '\'';    // @phpstan-ignore-line str_replace() may return array, but not in this case
                        }
                        if ($items !== []) {
                            $fcontent .= 'L10n::$locales[\'' . $vo . '\'] = [' . "\n\t" . implode(',' . "\n\t", $items) . ",\n" . '];' . "\n";
                        }
                    } else {
                        $tr = str_replace("'", "\\'", $tr);
                        $fcontent .= 'L10n::$locales[\'' . $vo . '\'] = \'' . $tr . '\';' . "\n";   // @phpstan-ignore-line see above
                    }
                }
            }

            if (($fp = fopen($php_file, 'w')) !== false) {
                fwrite($fp, $fcontent, strlen($fcontent));
                fclose($fp);

                return true;
            }

            return false;
        }

        /**
         * Parse Po File
         *
         * Return an array of po headers and translations from a po file
         *
         * @param string $file File path
         *
         * @return array<mixed>|false Parsed file
         */
        public static function parsePoFile(string $file): false|array
        {
            // stop if file not exists
            if (!file_exists($file)) {
                return false;
            }

            // read file per line in array (without ending new line)
            if (false === ($lines = file($file, FILE_IGNORE_NEW_LINES))) {
                return false;
            }

            // prepare variables
            $headers = [
                'Project-Id-Version'        => '',
                'Report-Msgid-Bugs-To'      => '',
                'POT-Creation-Date'         => '',
                'PO-Revision-Date'          => '',
                'Last-Translator'           => '',
                'Language-Team'             => '',
                'Content-Type'              => '',
                'Content-Transfer-Encoding' => '',
                'Plural-Forms'              => '',
                // there are more headers but these ones are default
            ];

            $headers_searched = $headers_found = false;
            $h_line           = $h_val = $h_key = '';
            $entries          = $entry = [];
            $i                = 0;

            /**
             * @var array<string, mixed>
             */
            $desc = [];

            // read through lines
            $counter = count($lines);
            for ($i = 0; $i < $counter; $i++) {
                // some people like mirovinben add white space at the end of line
                $line = trim($lines[$i]);

                // jump to next line on blank one or empty comment (#)
                if (strlen($line) < 2) {
                    continue;
                }

                // headers
                if (!$headers_searched && preg_match('/^msgid\s+""$/', trim($line))) {
                    // headers start wih empty msgid and msgstr follow be multine
                    if (!preg_match('/^msgstr\s+""$/', trim($lines[$i + 1]))
                        || !preg_match('/^"(.*)"$/', trim($lines[$i + 2]))) {
                        $headers_searched = true;
                    } else {
                        $l = $i + 2;
                        while (($def = self::cleanPoLine('multi', $lines[$l++])) !== false) {
                            $h_line = self::cleanPoString($def[1]);

                            // an header has key:val
                            if (false === ($h_index = strpos($h_line, ':'))) {
                                // multiline value
                                if ($h_key !== '' && (isset($headers[$h_key]) && $headers[$h_key] !== '')) {
                                    $headers[$h_key] = trim($headers[$h_key] . $h_line);

                                    continue;

                                    // your .po file is so bad
                                }
                                $headers_searched = true;

                                break;
                            }

                            // extract key and value
                            $h_key = substr($h_line, 0, $h_index);
                            $h_val = substr($h_line, $h_index + 1);

                            // ok it's an header, add it
                            $headers[$h_key] = trim($h_val);
                            $headers_found   = true;
                        }

                        // headers found so stop search and clean previous comments
                        if ($headers_found) {
                            $headers_searched = true;
                            $entry            = $desc = [];
                            $i                = $l - 1;

                            continue;
                        }
                    }
                }

                // comments
                if (false !== ($def = self::cleanPoLine('comment', $line))) {
                    $str = self::cleanPoString($def[2]);

                    switch ($def[1]) {
                        // translator comments
                        case ' ':
                            if (!isset($desc['translator-comments'])) {
                                $desc['translator-comments'] = $str;
                            } else {
                                $desc['translator-comments'] .= "\n" . $str;
                            }

                            break;

                            // extracted comments
                        case '.':
                            if (!isset($desc['extracted-comments'])) {
                                $desc['extracted-comments'] = $str;
                            } else {
                                $desc['extracted-comments'] .= "\n" . $str;
                            }

                            break;

                            // reference
                        case ':':
                            if (!isset($desc['references'])) {
                                $desc['references'] = [];
                            }
                            $desc['references'][] = $str;

                            break;

                            // flag
                        case ',':
                            if (!isset($desc['flags'])) {
                                $desc['flags'] = [];
                            }
                            $desc['flags'][] = $str;

                            break;

                            // previous msgid, msgctxt
                        case '|':
                            // msgid
                            if (str_starts_with($def[2], 'msgid')) {
                                $desc['previous-msgid'] = $str;
                                // msgcxt
                            } else {
                                $desc['previous-msgctxt'] = $str;
                            }

                            break;
                    }
                }

                // msgid
                elseif (false !== ($def = self::cleanPoLine('msgid', $line))) {
                    // add last translation and start new one
                    if ((isset($entry['msgid']) || isset($entry['msgid_plural'])) && isset($entry['msgstr'])) {
                        // save last translation and start new one
                        $entries[] = $entry;
                        $entry     = [];

                        // add comments to new translation
                        if (!empty($desc)) {
                            $entry = array_merge($entry, $desc);
                            $desc  = [];
                        }

                        // stop searching headers
                        $headers_searched = true;
                    }

                    $str = self::cleanPoString($def[2]);

                    // msgid_plural
                    if (!empty($def[1])) {
                        $entry['msgid_plural'] = $str;
                    } else {
                        $entry['msgid'] = $str;
                    }
                }

                // msgstr
                elseif (false !== ($def = self::cleanPoLine('msgstr', $line))) {
                    $str = self::cleanPoString($def[2]);

                    // plural forms
                    if (!empty($def[1])) {
                        if (!isset($entry['msgstr'])) {
                            $entry['msgstr'] = [];
                        }
                        $entry['msgstr'][] = $str;
                    } else {
                        $entry['msgstr'] = $str;
                    }
                }

                // multiline
                elseif (false !== ($def = self::cleanPoLine('multi', $line))) {
                    $str = self::cleanPoString($def[1]);

                    if (!isset($entry['msgstr'])) {
                        // msgid
                        if (isset($entry['msgid_plural'])) {
                            //msgid plural
                            if (!is_array($entry['msgid_plural'])) {
                                $entry['msgid_plural'] .= $str;
                            } else {
                                $entry['msgid_plural'][count($entry['msgid_plural']) - 1] .= $str;
                            }
                        } elseif (!is_array($entry['msgid'])) {
                            $entry['msgid'] .= $str;
                        } else {
                            $entry['msgid'][count($entry['msgid']) - 1] .= $str;
                        }
                    } elseif (!is_array($entry['msgstr'])) {
                        // msgstr
                        $entry['msgstr'] .= $str;
                    } else {
                        $entry['msgstr'][count($entry['msgstr']) - 1] .= $str;
                    }
                }
            }

            // Add last translation
            if ($entry !== []) {
                if (!empty($desc)) {
                    $entry = array_merge($entry, $desc);
                }
                $entries[] = $entry;
            }

            return [$headers, $entries];
        }

        /**
         * Clean line from .po
         *
         * @param      string  $type   The type
         * @param      mixed   $_      the line
         *
         * @return     false|array<string>
         */
        protected static function cleanPoLine(string $type, $_): array|false
        {
            $patterns = [
                'msgid'   => 'msgid(_plural|)\s+"(.*)"',
                'msgstr'  => 'msgstr(\[.*?\]|)\s+"(.*)"',
                'multi'   => '"(.*)"',
                'comment' => '#\s*(\s|\.|:|\,|\|)\s*(.*)',
            ];

            if (array_key_exists($type, $patterns)
                && preg_match('/^' . $patterns[$type] . '$/i', trim((string) $_), $m)) {
                return $m;
            }

            return false;
        }

        /**
         * Clean string from .po
         *
         * @param      mixed   $_      The string
         */
        protected static function cleanPoString($_): string
        {
            return stripslashes((string) str_replace(['\n', '\r\n'], "\n", $_));    // @phpstan-ignore-line
        }

        /**
         * Extract nplurals and plural from po expression
         *
         * @param string $expression Plural form as of gettext Plural-form param
         *
         * @return array<int, int|string> Number of plurals and cleaned plural expression
         */
        public static function parsePluralExpression(string $expression): array
        {
            return preg_match('/^\s*nplurals\s*=\s*(\d+)\s*;\s+plural\s*=\s*(.+)$/', $expression, $m) ?
            [(int) $m[1], trim(self::cleanPluralExpression($m[2]))] :
            [self::$language_pluralsnumber, self::$language_pluralexpression];
        }

        /**
         * Create function to find plural msgstr index from gettext expression
         *
         * @param integer   $nplurals   Plurals number
         * @param string    $expression Plural expression
         *
         * @return callable Function to extract right plural index
         */
        public static function createPluralFunction(int $nplurals, string $expression)
        {
            return function ($n) use ($nplurals, $expression) {
                $i = eval('return (integer) (' . str_replace('n', (string) $n, $expression) . ');');

                return ($i < $nplurals) ? $i : $nplurals - 1;
            };
        }

        /* @ignore */
        protected static function cleanPluralExpression(string $_): string
        {
            $_ .= ';';
            $r = '';
            $l = 0;

            for ($i = 0; $i < strlen($_); ++$i) {
                switch ($_[$i]) {
                    case '?':
                        $r .= ' ? (';
                        $l++;

                        break;

                    case ':':
                        $r .= ') : (';

                        break;

                    case ';':
                        $r .= str_repeat(')', $l) . ';';
                        $l = 0;

                        break;

                    default:
                        $r .= $_[$i];
                }
            }

            return rtrim($r, ';');
        }
        //@}

        /// @name Languages definitions methods
        //@{
        /**
         * Check if a language code exists
         *
         * @param string $code Language code

         * @return bool True if code exists
         */
        public static function isCode(string $code): bool
        {
            return array_key_exists($code, self::getLanguagesName());
        }

        /**
         * Get a language code according to a language name
         *
         * @param string $code Language name
         *
         * @return string Language code
         */
        public static function getCode(string $code): string
        {
            $_ = self::getLanguagesName();

            return (($index = array_search($code, $_)) !== false) ? $index : (string) self::$language_code;
        }

        /**
         * ISO Codes
         *
         * @param bool    $flip              Flip resulting array
         * @param bool    $name_with_code    Prefix (code) to names
         *
         * @return array<string, string>
         */
        public static function getISOcodes(bool $flip = false, bool $name_with_code = false): array
        {
            $langs = self::getLanguagesName();
            if ($name_with_code) {
                foreach ($langs as $k => &$v) {
                    $v = $k . ' - ' . $v;
                }
            }

            if ($flip) {
                return array_flip($langs);
            }

            return $langs;
        }

        /**
         * Get a language name according to a lang code
         *
         * @param string $code Language code
         *
         * @return string Language name
         */
        public static function getLanguageName(string $code): string
        {
            $_ = self::getLanguagesName();

            return array_key_exists($code, $_) ? $_[$code] : self::$language_name;
        }

        /**
         * Get languages names
         *
         * @return array<string, string> List of languages names by languages codes
         */
        public static function getLanguagesName(): array
        {
            if (self::$languages_name === []) {
                self::$languages_name = self::getLanguagesDefinitions(3);
            }

            return self::$languages_name;
        }

        /**
         * Get a text direction according to a language code
         *
         * @param string $code Language code
         *
         * @return string Text direction (rtl or ltr)
         */
        public static function getLanguageTextDirection(string $code): string
        {
            $_ = self::getLanguagesTextDirection();

            return array_key_exists($code, $_) ? $_[$code] : self::$language_textdirection;
        }

        /**
         * Get languages text directions
         *
         * @return array<string, string> List of text directions by languages codes
         */
        public static function getLanguagesTextDirection(): array
        {
            if (self::$languages_textdirection === []) {
                self::$languages_textdirection = self::getLanguagesDefinitions(4);
            }

            return self::$languages_textdirection;
        }

        /**
         * Get a number of plurals according to a language code
         *
         * @param string $code Language code
         *
         * @return int  Number of plurals
         */
        public static function getLanguagePluralsNumber(string $code): int
        {
            $_ = self::getLanguagesPluralsNumber();

            return empty($_[$code]) ? self::$language_pluralsnumber : $_[$code];
        }

        /**
         * Get languages numbers of plurals
         *
         * @return array<string, int|null> List of numbers of plurals by languages codes
         */
        public static function getLanguagesPluralsNumber(): array
        {
            if (self::$languages_pluralsnumber === []) {
                self::$languages_pluralsnumber = self::getLanguagesDefinitions(5);
            }

            return self::$languages_pluralsnumber;
        }

        /**
         * Get a plural expression according to a language code
         *
         * @param string $code Language code
         *
         * @return string Plural expression
         */
        public static function getLanguagePluralExpression(string $code): string
        {
            $_ = self::getLanguagesPluralExpression();

            return empty($_[$code]) ? self::$language_pluralexpression : $_[$code];
        }

        /**
         * Get languages plural expressions
         *
         * @return array<string, string|null> List of plural expressions by languages codes
         */
        public static function getLanguagesPluralExpression(): array
        {
            if (self::$languages_pluralexpression === []) {
                self::$languages_pluralexpression = self::getLanguagesDefinitions(6);
            }

            return self::$languages_pluralexpression;
        }

        /**
         * Get languages definitions of a given type
         *
         * The list follows ISO 639.1 norm with additionnal IETF codes as pt-br
         *
         * Countries codes and names from:
         * - http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
         * - http://www.gnu.org/software/gettext/manual/gettext.html#Language-Codes
         * - http://www.loc.gov/standards/iso639-2/php/English_list.php
         *
         * Text direction from:
         * - http://translate.sourceforge.net/wiki/l10n/displaysettings
         * - http://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
         *
         * Plural-forms taken from:
         * - http://translate.sourceforge.net/wiki/l10n/pluralforms
         *
         * $languages_definitions types look like this:
         * 0 = code ISO 639.1 (2 digit) + IETF add
         * 1 = code ISO 639.2 (english 3 digit)
         * 2 = English name
         * 3 = natal name
         * 4 = text direction (ltr or rtl)
         * 5 = number of plurals (1 means no plural form)
         * 6 = plural expression (as of gettext .po plural form)
         *
         * null values represent missing values
         *
         * @param integer   $type Type of definition
         * @param string    $default Default value if definition is empty
         *
         * @return array<string, mixed>    List of requested definition by languages codes
         */
        protected static function getLanguagesDefinitions(int $type, string $default = ''): array
        {
            if ($type < 0 || $type > 6) {
                return [];
            }

            if (self::$languages_definitions === []) {
                self::$languages_definitions = [
                    ['aa', 'aar', 'Afar', 'Afaraf', 'ltr', null, null],
                    ['ab', 'abk', 'Abkhazian', 'Аҧсуа', 'ltr', null, null],
                    ['ae', 'ave', 'Avestan', 'Avesta', 'ltr', null, null],
                    ['af', 'afr', 'Afrikaans', 'Afrikaans', 'ltr', 2, 'n != 1'],
                    ['ak', 'aka', 'Akan', 'Akan', 'ltr', 2, 'n > 1)'],
                    ['am', 'amh', 'Amharic', 'አማርኛ', 'ltr', 2, 'n > 1'],
                    ['an', 'arg', 'Aragonese', 'Aragonés', 'ltr', 2, 'n != 1'],
                    ['ar', 'ara', 'Arabic', '‫العربية', 'rtl', 6, 'n==0 ? 0 : (n==1 ? 1 : (n==2 ? 2 : (n%100>=3 && n%100<=10 ? 3 : (n%100>=11 ? 4 : 5))))'],
                    ['as', 'asm', 'Assamese', 'অসমীয়া', 'ltr', null, null],
                    ['av', 'ava', 'Avaric', 'авар мацӀ', 'ltr', null, null],
                    ['ay', 'aym', 'Aymara', 'Aymar aru', 'ltr', 1, '0'],
                    ['az', 'aze', 'Azerbaijani', 'Azərbaycan dili', 'ltr', 2, 'n != 1'],

                    ['ba', 'bak', 'Bashkir', 'башҡорт теле', 'ltr', null, null],
                    ['be', 'bel', 'Belarusian', 'Беларуская', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
                    ['bg', 'bul', 'Bulgarian', 'български език', 'ltr', 2, 'n != 1'],
                    ['bh', 'bih', 'Bihari languages', 'भोजपुरी', 'ltr', null, null],
                    ['bi', 'bis', 'Bislama', 'Bislama', 'ltr', null, null],
                    ['bm', 'bam', 'Bambara', 'Bamanankan', 'ltr', null, null],
                    ['bn', 'ben', 'Bengali', 'বাংলা', 'ltr', 2, 'n != 1'],
                    ['bo', 'tib', 'Tibetan', 'བོད་ཡིག', 'ltr', 1, '0'],
                    ['br', 'bre', 'Breton', 'Brezhoneg', 'ltr', 2, 'n > 1'],
                    ['bs', 'bos', 'Bosnian', 'Bosanski jezik', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],

                    ['ca', 'cat', 'Catalan', 'Català', 'ltr', 2, 'n != 1'],
                    ['ce', 'che', 'Chechen', 'нохчийн мотт', 'ltr', null, null],
                    ['ch', 'cha', 'Chamorro', 'Chamoru', 'ltr', 3, 'n==1 ? 0 : ((n>=2 && n<=4) ? 1 : 2)'],
                    ['co', 'cos', 'Corsican', 'Corsu', 'ltr', null, null],
                    ['cr', 'cre', 'Cree', 'ᓀᐦᐃᔭᐍᐏᐣ', 'ltr', null, null],
                    ['cs', 'cze', 'Czech', 'Česky', 'ltr', null, null],
                    ['cu', 'chu', 'Church Slavonic', 'ѩзыкъ Словѣньскъ', 'ltr', null, null],
                    ['cv', 'chv', 'Chuvash', 'чӑваш чӗлхи', 'ltr', null, null],
                    ['cy', 'wel', 'Welsh', 'Cymraeg', 'ltr', 4, 'n==1 ? 0 : ((n==2) ? 1 : ((n != 8 && n != 11) ? 2 : 3))'],

                    ['da', 'dan', 'Danish', 'Dansk', 'ltr', 2, 'n != 1'],
                    ['de', 'ger', 'German', 'Deutsch', 'ltr', 2, 'n != 1'],
                    ['dv', 'div', 'Maldivian', 'ދިވެހި', 'rtl', null, null],
                    ['dz', 'dzo', 'Dzongkha', 'རྫོང་ཁ', 'ltr', 1, '0'],

                    ['ee', 'ewe', 'Ewe', 'Ɛʋɛgbɛ', 'ltr', null, null],
                    ['el', 'gre', 'Greek', 'Ελληνικά', 'ltr', 2, 'n != 1'],
                    ['en', 'eng', 'English', 'English', 'ltr', 2, 'n != 1'],
                    ['eo', 'epo', 'Esperanto', 'Esperanto', 'ltr', 2, 'n != 1'],
                    ['es', 'spa', 'Spanish', 'Español', 'ltr', 2, 'n != 1'],
                    ['es-ar', null, 'Argentinean Spanish', 'Argentinean Spanish', 'ltr', 2, 'n != 1'],
                    ['et', 'est', 'Estonian', 'Eesti keel', 'ltr', 2, 'n != 1'],
                    ['eu', 'baq', 'Basque', 'Euskara', 'ltr', 2, 'n != 1'],

                    ['fa', 'per', 'Persian', '‫فارسی', 'rtl', 1, '0'],
                    ['ff', 'ful', 'Fulah', 'Fulfulde', 'ltr', 2, 'n != 1'],
                    ['fi', 'fin', 'Finnish', 'Suomen kieli', 'ltr', 2, 'n != 1'],
                    ['fj', 'fij', 'Fijian', 'Vosa Vakaviti', 'ltr', null, null],
                    ['fo', 'fao', 'Faroese', 'Føroyskt', 'ltr', 2, 'n != 1'],
                    ['fr', 'fre', 'French', 'Français', 'ltr', 2, 'n > 1'],
                    ['fy', 'fry', 'Western Frisian', 'Frysk', 'ltr', 2, 'n != 1'],

                    ['ga', 'gle', 'Irish', 'Gaeilge', 'ltr', 5, 'n==1 ? 0 : (n==2 ? 1 : (n<7 ? 2 : (n<11 ? 3 : 4)))'],
                    ['gd', 'gla', 'Gaelic', 'Gàidhlig', 'ltr', 4, '(n==1 || n==11) ? 0 : ((n==2 || n==12) ? 1 : ((n > 2 && n < 20) ? 2 : 3))'],
                    ['gl', 'glg', 'Galician', 'Galego', 'ltr', 2, 'n != 1'],
                    ['gn', 'grn', 'Guarani', "Avañe'ẽ", 'ltr', null, null],
                    ['gu', 'guj', 'Gujarati', 'ગુજરાતી', 'ltr', 2, 'n != 1'],
                    ['gv', 'glv', 'Manx', 'Ghaelg', 'ltr', null, null],

                    ['ha', 'hau', 'Hausa', '‫هَوُسَ', 'rtl', 2, 'n != 1'],
                    ['he', 'heb', 'Hebrew', '‫עברית', 'rtl', 2, 'n != 1'],
                    ['hi', 'hin', 'Hindi', 'हिन्दी', 'ltr', 2, 'n != 1'],
                    ['ho', 'hmo', 'Hiri Motu', 'Hiri Motu', 'ltr', null, null],
                    ['hr', 'hrv', 'Croatian', 'Hrvatski', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
                    ['ht', 'hat', 'Haitian', 'Kreyòl ayisyen', 'ltr', null, null],
                    ['hu', 'hun', 'Hungarian', 'Magyar', 'ltr', 2, 'n != 1'],
                    ['hy', 'arm', 'Armenian', 'Հայերեն', 'ltr', 2, 'n != 1'],
                    ['hz', 'her', 'Herero', 'Otjiherero', 'ltr', null, null],

                    ['ia', 'ina', 'Interlingua', 'Interlingua', 'ltr', 2, 'n != 1'],
                    ['id', 'ind', 'Indonesian', 'Bahasa Indonesia', 'ltr', 1, '0'],
                    ['ie', 'ile', 'Interlingue', 'Interlingue', 'ltr', null, null],
                    ['ig', 'ibo', 'Igbo', 'Igbo', 'ltr', null, null],
                    ['ii', 'iii', 'Sichuan Yi', 'ꆇꉙ', 'ltr', null, null],
                    ['ik', 'ipk', 'Inupiaq', 'Iñupiaq', 'ltr', null, null],
                    ['io', 'ido', 'Ido', 'Ido', 'ltr', null, null],
                    ['is', 'ice', 'Icelandic', 'Íslenska', 'ltr', 2, '(n%10!=1 || n%100==11) ? 1 : 0'],
                    ['it', 'ita', 'Italian', 'Italiano', 'ltr', 2, 'n != 1'],
                    ['iu', 'iku', 'Inuktitut', 'ᐃᓄᒃᑎᑐᑦ', 'ltr', null, null],

                    ['ja', 'jpn', 'Japanese', '日本語', 'ltr', 1, '0'],
                    ['jv', 'jav', 'Javanese', 'Basa Jawa', 'ltr', 2, 'n != 0'],

                    ['ka', 'geo', 'Georgian', 'ქართული', 'ltr', 1, '0'],
                    ['kg', 'kon', 'Kongo', 'KiKongo', 'ltr', null, null],
                    ['ki', 'kik', 'Kikuyu', 'Gĩkũyũ', 'ltr', null, null],
                    ['kj', 'kua', 'Kuanyama', 'Kuanyama', 'ltr', null, null],
                    ['kk', 'kaz', 'Kazakh', 'Қазақ тілі', 'ltr', 1, '0'],
                    ['kl', 'kal', 'Greenlandic', 'Kalaallisut', 'ltr', null, null],
                    ['km', 'khm', 'Central Khmer', 'ភាសាខ្មែរ', 'ltr', 1, '0'],
                    ['kn', 'kan', 'Kannada', 'ಕನ್ನಡ', 'ltr', 2, 'n != 1'],
                    ['ko', 'kor', 'Korean', '한국어', 'ltr', 1, '0'],
                    ['kr', 'kau', 'Kanuri', 'Kanuri', 'ltr', null, null],
                    ['ks', 'kas', 'Kashmiri', 'कश्मीरी', 'rtl', null, null],
                    ['ku', 'kur', 'Kurdish', 'Kurdî', 'ltr', 2, 'n!= 1'],
                    ['kv', 'kom', 'Komi', 'коми кыв', 'ltr', null, null],
                    ['kw', 'cor', 'Cornish', 'Kernewek', 'ltr', 4, 'n==1 ? 0 : ((n==2) ? 1 : ((n == 3) ? 2 : 3))'],
                    ['ky', 'kir', 'Kirghiz', 'кыргыз тили', 'ltr', 1, '0'],

                    ['la', 'lat', 'Latin', 'Latine', 'ltr', null, null],
                    ['lb', 'ltz', 'Luxembourgish', 'Lëtzebuergesch', 'ltr', 2, 'n != 1'],
                    ['lg', 'lug', 'Ganda', 'Luganda', 'ltr', null, null],
                    ['li', 'lim', 'Limburgan', 'Limburgs', 'ltr', null, null],
                    ['ln', 'lin', 'Lingala', 'Lingála', 'ltr', 2, 'n>1'],
                    ['lo', 'lao', 'Lao', 'ພາສາລາວ', 'ltr', 1, '0'],
                    ['lt', 'lit', 'Lithuanian', 'Lietuvių kalba', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n%10>=2 && (n%100<10 or n%100>=20) ? 1 : 2)'],
                    ['lu', 'lub', 'Luba-Katanga', 'Luba-Katanga', 'ltr', null, null],
                    ['lv', 'lav', 'Latvian', 'Latviešu valoda', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n != 0 ? 1 : 2)'],

                    ['mg', 'mlg', 'Malagasy', 'Malagasy fiteny', 'ltr', 2, 'n > 1'],
                    ['mh', 'mah', 'Marshallese', 'Kajin M̧ajeļ', 'ltr', null, null],
                    ['mi', 'mao', 'Maori', 'Te reo Māori', 'ltr', 2, 'n > 1'],
                    ['mk', 'mac', 'Macedonian', 'македонски јазик', 'ltr', 2, 'n==1 || n%10==1 ? 0 : 1'],
                    ['ml', 'mal', 'Malayalam', 'മലയാളം', 'ltr', 2, 'n != 1'],
                    ['mn', 'mon', 'Mongolian', 'Монгол', 'ltr', 2, 'n != 1'],
                    ['mo', null, 'Moldavian', 'Limba moldovenească', 'ltr', 3, 'n==1 ? 0 : ((n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2)'], //cf: ro
                    ['mr', 'mar', 'Marathi', 'मराठी', 'ltr', 2, 'n != 1'],
                    ['ms', 'may', 'Malay', 'Bahasa Melayu', 'ltr', 1, '0'],
                    ['mt', 'mlt', 'Maltese', 'Malti', 'ltr', 4, 'n==1 ? 0 : (n==0 || ( n%100>1 && n%100<11) ? 1 : ((n%100>10 && n%100<20 ) ? 2 : 3))'],
                    ['my', 'bur', 'Burmese', 'ဗမာစာ', 'ltr', 1, '0'],

                    ['na', 'nau', 'Nauru', 'Ekakairũ Naoero', 'ltr', null, null],
                    ['nb', 'nob', 'Norwegian Bokmål', 'Norsk bokmål', 'ltr', 2, 'n != 1'],
                    ['nd', 'nde', 'North Ndebele', 'isiNdebele', 'ltr', null, null],
                    ['ne', 'nep', 'Nepali', 'नेपाली', 'ltr', 2, 'n != 1'],
                    ['ng', 'ndo', 'Ndonga', 'Owambo', 'ltr', null, null],
                    ['nl', 'dut', 'Flemish', 'Nederlands', 'ltr', 2, 'n != 1'],
                    ['nl-be', null, 'Flemish', 'Nederlands (Belgium)', 'ltr', 2, 'n != 1'],
                    ['nn', 'nno', 'Norwegian Nynorsk', 'Norsk nynorsk', 'ltr', 2, 'n != 1'],
                    ['no', 'nor', 'Norwegian', 'Norsk', 'ltr', 2, 'n != 1'],
                    ['nr', 'nbl', 'South Ndebele', 'Ndébélé', 'ltr', null, null],
                    ['nv', 'nav', 'Navajo', 'Diné bizaad', 'ltr', null, null],
                    ['ny', 'nya', 'Chichewa', 'ChiCheŵa', 'ltr', null, null],

                    ['oc', 'oci', 'Occitan', 'Occitan', 'ltr', 2, 'n > 1'],
                    ['oj', 'oji', 'Ojibwa', 'ᐊᓂᔑᓈᐯᒧᐎᓐ', 'ltr', null, null],
                    ['om', 'orm', 'Oromo', 'Afaan Oromoo', 'ltr', null, null],
                    ['or', 'ori', 'Oriya', 'ଓଡ଼ିଆ', 'ltr', 2, 'n != 1'],
                    ['os', 'oss', 'Ossetian', 'Ирон æвзаг', 'ltr', null, null],

                    ['pa', 'pan', 'Panjabi', 'ਪੰਜਾਬੀ', 'ltr', 2, 'n != 1'],
                    ['pi', 'pli', 'Pali', 'पाऴि', 'ltr', null, null],
                    ['pl', 'pol', 'Polish', 'Polski', 'ltr', 3, 'n==1 ? 0 : (n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
                    ['ps', 'pus', 'Pushto', '‫پښتو', 'rtl', 2, 'n != 1'],
                    ['pt', 'por', 'Portuguese', 'Português', 'ltr', 2, 'n != 1'],
                    ['pt-br', null, 'Brazilian Portuguese', 'Português do Brasil', 'ltr', 2, 'n > 1'],

                    ['qu', 'que', 'Quechua', 'Runa Simi', 'ltr', null, null],

                    ['rm', 'roh', 'Romansh', 'Rumantsch grischun', 'ltr', 2, 'n != 1'],
                    ['rn', 'run', 'Rundi', 'kiRundi', 'ltr', null, null],
                    ['ro', 'rum', 'Romanian', 'Română', 'ltr', 3, 'n==1 ? 0 : ((n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2)'],
                    ['ru', 'rus', 'Russian', 'Русский', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
                    ['rw', 'kin', 'Kinyarwanda', 'IKinyarwanda', 'ltr', 2, 'n != 1'],

                    ['sa', 'san', 'Sanskrit', 'संस्कृतम्', 'ltr', null, null],
                    ['sc', 'srd', 'Sardinian', 'sardu', 'ltr', null, null],
                    ['sd', 'snd', 'Sindhi', 'सिन्धी', 'ltr', 2, 'n != 1'],
                    ['se', 'sme', 'Northern Sami', 'Davvisámegiella', 'ltr', null, null],
                    ['sg', 'sag', 'Sango', 'Yângâ tî sängö', 'ltr', null, null],
                    ['sh', null, null, 'SrpskoHrvatski', 'ltr', null, null], //!
                    ['si', 'sin', 'Sinhalese', 'සිංහල', 'ltr', 2, 'n != 1'],
                    ['sk', 'slo', 'Slovak', 'Slovenčina', 'ltr', 3, '(n==1) ? 0 : ((n>=2 && n<=4) ? 1 : 2)'],
                    ['sl', 'slv', 'Slovenian', 'Slovenščina', 'ltr', 4, 'n%100==1 ? 1 : (n%100==2 ? 2 : (n%100==3 || n%100==4 ? 3 : 0))'],
                    ['sm', 'smo', 'Samoan', "Gagana fa'a Samoa", 'ltr', null, null],
                    ['sn', 'sna', 'Shona', 'chiShona', 'ltr', null, null],
                    ['so', 'som', 'Somali', 'Soomaaliga', 'ltr', 2, 'n != 1'],
                    ['sq', 'alb', 'Albanian', 'Shqip', 'ltr', 2, 'n != 1'],
                    ['sr', 'srp', 'Serbian', 'српски језик', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
                    ['ss', 'ssw', 'Swati', 'SiSwati', 'ltr', null, null],
                    ['st', 'sot', 'Southern Sotho', 'seSotho', 'ltr', null, null],
                    ['su', 'sun', 'Sundanese', 'Basa Sunda', 'ltr', 1, '0'],
                    ['sv', 'swe', 'Swedish', 'Svenska', 'ltr', 2, 'n != 1'],
                    ['sw', 'swa', 'Swahili', 'Kiswahili', 'ltr', 2, 'n != 1'],

                    ['ta', 'tam', 'Tamil', 'தமிழ்', 'ltr', 2, 'n != 1'],
                    ['te', 'tel', 'Telugu', 'తెలుగు', 'ltr', 2, 'n != 1'],
                    ['tg', 'tgk', 'Tajik', 'тоҷикӣ', 'ltr', 2, 'n > 1'],
                    ['th', 'tha', 'Thai', 'ไทย', 'ltr', 1, '0'],
                    ['ti', 'tir', 'Tigrinya', 'ትግርኛ', 'ltr', 2, 'n > 1'],
                    ['tk', 'tuk', 'Turkmen', 'Türkmen', 'ltr', 2, 'n != 1'],
                    ['tl', 'tlg', 'Tagalog', 'Tagalog', 'ltr', null, null],
                    ['tn', 'tsn', 'Tswana', 'seTswana', 'ltr', null, null],
                    ['to', 'ton', 'Tonga', 'faka Tonga', 'ltr', null, null],
                    ['tr', 'tur', 'Turkish', 'Türkçe', 'ltr', 2, 'n > 1'],
                    ['ts', 'tso', 'Tsonga', 'xiTsonga', 'ltr', null, null],
                    ['tt', 'tat', 'Tatar', 'татарча', 'ltr', 1, '0'],
                    ['tw', 'twi', 'Twi', 'Twi', 'ltr', null, null],
                    ['ty', 'tah', 'Tahitian', 'Reo Mā`ohi', 'ltr', null, null],

                    ['ug', 'uig', 'Uighur', 'Uyƣurqə', 'ltr', 1, '0'],
                    ['uk', 'ukr', 'Ukrainian', 'Українська', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : (n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'],
                    ['ur', 'urd', 'Urdu', '‫اردو', 'rtl', 2, 'n != 1'],
                    ['uz', 'uzb', 'Uzbek', "O'zbek", 'ltr', 2, 'n > 1'],

                    ['ve', 'ven', 'Venda', 'tshiVenḓa', 'ltr', null, null],
                    ['vi', 'vie', 'Vietnamese', 'Tiếng Việt', 'ltr', 1, '0'],
                    ['vo', 'vol', 'Volapük', 'Volapük', 'ltr', null, null],

                    ['wa', 'wln', 'Walloon', 'Walon', 'ltr', 2, 'n > 1'],
                    ['wo', 'wol', 'Wolof', 'Wollof', 'ltr', 1, '0'],

                    ['xh', 'xho', 'Xhosa', 'isiXhosa', 'ltr', null, null],

                    ['yi', 'yid', 'Yiddish', '‫ייִדיש', 'rtl', null, null],
                    ['yo', 'yor', 'Yoruba', 'Yorùbá', 'ltr', 2, 'n != 1'],

                    ['za', 'zha', 'Chuang', 'Saɯ cueŋƅ', 'ltr', null, null],
                    ['zh-cn', 'zhi', 'Chinese', '中文', 'ltr', 1, '0'],
                    ['zh-hk', null, 'Honk Kong Chinese', '中文 (香港)', 'ltr', 1, '0'],
                    ['zh-tw', null, 'Taiwan Chinese', '中文 (臺灣)', 'ltr', 1, '0'],
                    ['zu', 'zul', 'Zulu', 'isiZulu', 'ltr', null, null],
                ];
            }

            /**
             * @var        array<string, mixed>
             */
            $r = [];
            foreach (self::$languages_definitions as $_) {
                $r[$_[0]] = empty($_[$type]) ? $default : $_[$type];
            }

            return $r;  // @phpstan-ignore-line
        }
        //@}
    }
}

namespace {
    use Dotclear\Helper\L10n;

    if (!function_exists('__')) {
        /**
         * Translated string
         *
         * @see L10n::trans()
         *
         * @param      string   $singular Singular form of the string
         * @param      string   $plural Plural form of the string (optionnal)
         * @param      integer  $count Context number for plural form (optionnal)
         *
         * @return     string   translated string
         */
        function __(string $singular, ?string $plural = null, ?int $count = null): string
        {
            return L10n::trans($singular, $plural, $count);
        }
    }
}
