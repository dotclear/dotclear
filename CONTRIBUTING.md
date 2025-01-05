# CONTRIBUTING

Dotclear is an open source project. If you'd like to contribute, you can send a pull request (on Github mirror, see below), or feel free to use any other way you'd prefer.

There are many way to contribute :

* Report bugs (<https://git.dotclear.org/dev/dotclear> and <https://git.dotclear.org/dev/dotclear/issues/new>)
* Add documentation:
  * <https://dotclear.org/documentation/2.0> in English
  * <https://fr.dotclear.org/documentation/2.0> in French
* Add/improve translations (<https://dotclear.crowdin.com/dotclear>)

## Repositories

<https://git.dotclear.org/dotclear/> (official)
<https://github.com/dotclear/dotclear> (Github mirror)

## CODE FORMATTING

See `.editorconfig` file

### PHP

See `.php-cs-fixer.dist.php` file

### JS

jsPrettier configuration:

```json
{
    "prettier_options": {
        "arrowParens": "always",
        "bracketSpacing": true,
        "editorconfig": true,
        "embeddedLanguageFormatting": "auto",
        "htmlWhitespaceSensitivity": "css",
        "insertPragma": false,
        "jsxBracketSameLine": false,
        "jsxSingleQuote": false,
        "printWidth": 128,
        "proseWrap": "preserve",
        "quoteProps": "as-needed",
        "requirePragma": false,
        "semi": true,
        "singleQuote": true,
        "tabWidth": 2,
        "trailingComma": "all",
        "useTabs": false,
        "vueIndentScriptAndStyle": false
    }
}
```

### HTML/CSS

`.jsbeautifyrc` file:

```json
{
  "html":
  {
      "allowed_file_extensions": ["htm", "html", "xhtml", "shtml", "xml", "svg"],
      "brace_style": "collapse",
      "end_with_newline": false,
      "indent_char": " ",
      "indent_handlebars": false,
      "indent_inner_html": false,
      "indent_scripts": "keep",
      "indent_size": 4,
      "indent_with_tabs": true,
      "max_preserve_newlines": 0,
      "preserve_newlines": true,
      "unformatted": ["a", "span", "img", "code", "pre", "sub", "sup", "em", "strong", "b", "i", "u", "strike", "big", "small", "pre", "h1", "h2", "h3", "h4", "h5", "h6"],
      "wrap_line_length": 0
  },
  "css":
  {
      "allowed_file_extensions": ["css", "scss", "sass", "less"],
      "end_with_newline": false,
      "indent_char": " ",
      "indent_size": 4,
      "indent_with_tabs": true,
      "newline_between_rules": true,
      "selector_separator": " ",
      "selector_separator_newline": true
  },
  "js":
  {
      "allowed_file_extensions": ["js", "json", "jshintrc", "jsbeautifyrc"],
      "brace_style": "collapse-preserve-inline",
      "break_chained_methods": false,
      "e4x": false,
      "end_with_newline": false,
      "indent_char": " ",
      "indent_level": 0,
      "indent_size": 4,
      "indent_with_tabs": true,
      "jslint_happy": false,
      "keep_array_indentation": false,
      "keep_function_indentation": false,
      "max_preserve_newlines": 0,
      "preserve_newlines": true,
      "space_after_anon_function": false,
      "space_before_conditional": true,
      "space_in_empty_paren": false,
      "space_in_paren": false,
      "unescape_strings": false,
      "wrap_line_length": 0
  }
}
```

## CODE ANALYSIS AND TESTING

Run `composer install` if necessary, from root directory

Then:

### PHP code analysis

For PHP static analysis, run:

```sh
bin/phpstan analyse --memory-limit=-1
```

And :

```sh
bin/rector process --dry-run --verbose --memory-limit=8G
```

And also:

```sh
bin/psalm
```

### PHP code unit testing

```sh
bin/atoum
```

To run unit tests

Or:

```sh
bin/atoum -c .atoum.coverage.php
```

To generate a [code coverage report](/coverage/html/index.html)

