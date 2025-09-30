<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper;

/**
 * @brief   L10n helper interface.
 *
 * @since   2.36
 */
interface L10nInterface
{
    /// @name Utils methods
    ///@{
    /**
     * Set L10n root function.
     */
    public static function bootstrap(): void;

    /**
     * L10N initialization
     *
     * Create global arrays for L10N stuff. Should be called before any work
     * with other methods. For plural-forms, __l10n values can now be array.
     *
     * @param string|null $code Language code to work with
     */
    public static function init(?string $code = 'en'): void;

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
    public static function lang(?string $code = null): string;

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
    public static function trans(string $singular, ?string $plural = null, ?int $count = null): string;

    /**
     * Retrieve plural index from input number
     *
     * @param integer $count Number to take account
     *
     * @return integer Index of plural form
     */
    public static function index(int $count): int;

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
    public static function set(string $file): bool;

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
    public static function getFilePath(string $dir, string $file, string $lang);
    ///@}

    /// @name Gettext PO methods
    ///@{
    /**
     * Load gettext file
     *
     * Returns an array of strings found in a given gettext (.po) file
     *
     * @param string    $file        Filename
     *
     * @return array<mixed>|false
     */
    public static function getPoFile(string $file): false|array;

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
    public static function generatePhpFileFromPo(string $file, string $license_block = ''): bool;

    /**
     * Parse Po File
     *
     * Return an array of po headers and translations from a po file
     *
     * @param string $file File path
     *
     * @return array<mixed>|false Parsed file
     */
    public static function parsePoFile(string $file): false|array;

    /**
     * Extract nplurals and plural from po expression
     *
     * @param string $expression Plural form as of gettext Plural-form param
     *
     * @return array<int|string> Number of plurals and cleaned plural expression
     */
    public static function parsePluralExpression(string $expression): array;

    /**
     * Create function to find plural msgstr index from gettext expression
     *
     * @param integer   $nplurals   Plurals number
     * @param string    $expression Plural expression
     *
     * @return callable Function to extract right plural index
     */
    public static function createPluralFunction(int $nplurals, string $expression);
    ///@}

    /// @name Languages definitions methods
    ///@{
    /**
     * Check if a language code exists
     *
     * @param string $code Language code

     * @return bool True if code exists
     */
    public static function isCode(string $code): bool;

    /**
     * Get a language code according to a language name
     *
     * @param string $code Language name
     *
     * @return string Language code
     */
    public static function getCode(string $code): string;

    /**
     * ISO Codes
     *
     * @param bool    $flip              Flip resulting array
     * @param bool    $name_with_code    Prefix (code) to names
     *
     * @return array<string, string>
     */
    public static function getISOcodes(bool $flip = false, bool $name_with_code = false): array;

    /**
     * Get a language name according to a lang code
     *
     * @param string $code Language code
     *
     * @return string Language name
     */
    public static function getLanguageName(string $code): string;

    /**
     * Get languages names
     *
     * @return array<string, string> List of languages names by languages codes
     */
    public static function getLanguagesName(): array;

    /**
     * Get a text direction according to a language code
     *
     * @param string $code Language code
     *
     * @return string Text direction (rtl or ltr)
     */
    public static function getLanguageTextDirection(string $code): string;

    /**
     * Get languages text directions
     *
     * @return array<string, string> List of text directions by languages codes
     */
    public static function getLanguagesTextDirection(): array;

    /**
     * Get a number of plurals according to a language code
     *
     * @param string $code Language code
     *
     * @return int  Number of plurals
     */
    public static function getLanguagePluralsNumber(string $code): int;

    /**
     * Get languages numbers of plurals
     *
     * @return array<string, int|null> List of numbers of plurals by languages codes
     */
    public static function getLanguagesPluralsNumber(): array;

    /**
     * Get a plural expression according to a language code
     *
     * @param string $code Language code
     *
     * @return string Plural expression
     */
    public static function getLanguagePluralExpression(string $code): string;

    /**
     * Get languages plural expressions
     *
     * @return array<string, string|null> List of plural expressions by languages codes
     */
    public static function getLanguagesPluralExpression(): array;
    ///@}
}
