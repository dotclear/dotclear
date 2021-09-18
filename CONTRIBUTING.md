# CONTRIBUTING

Dotclear is an open source project. If you'd like to contribute, you can send
a pull request (on Github mirror, see below), or feel free to use any other way you'd prefer.

There are many way to contribute :

* Report bugs (https://git.dotclear.org/dev/dotclear and https://git.dotclear.org/dev/dotclear/issues/new)
* Add documentation (https://dotclear.org/documentation/2.0 in English, https://fr.dotclear.org/documentation/2.0 in French)
* Add/improve translations (https://dotclear.crowdin.com/dotclear)

## Repositories

<https://git.dotclear.org/dotclear/> (official)
<https://github.com/dotclear/dotclear> (Github mirror)

## CODE FORMATTING

See `.editorconfig` file

### PHP

See `.php_cs.dist` file

### JS

jsPrettier configuration:

```json
{
    "prettier_options": {
        "arrowParens": "always",
        "bracketSpacing": true,
        "embeddedLanguageFormatting": "auto",
        "htmlWhitespaceSensitivity": "css",
        "insertPragma": false,
        "jsxBracketSameLine": false,
        "jsxSingleQuote": false,
        "proseWrap": "preserve",
        "quoteProps": "as-needed",
        "requirePragma": false,
        "semi": true,
        "singleQuote": true,
        "tabWidth": 2,
        "trailingComma": "es5",
        "useTabs": false,
        "vueIndentScriptAndStyle": false,
        "printWidth": 128
    }
}
```

### HTML/CSS

`.jsbeautifyrc` file:

```json
{
  "html": {
    "allowed_file_extensions": ["htm", "html", "xhtml", "shtml", "xml", "svg"],
    "brace_style": "collapse",
    "end_with_newline": false,
    "indent_char": " ",
    "indent_handlebars": false,
    "indent_inner_html": true,
    "indent_scripts": "keep",
    "indent_size": 2,
    "indent_with_tabs": false,
    "max_preserve_newlines": 2,
    "preserve_newlines": true,
    "unformatted": ["a", "span", "img", "code", "pre", "sub", "sup", "em", "strong", "b", "i", "u", "strike", "big", "small", "pre", "h1", "h2", "h3", "h4", "h5", "h6"],
    "wrap_line_length": 0
  },
  "css": {
    "allowed_file_extensions": ["css", "scss", "sass", "less"],
    "end_with_newline": false,
    "indent_char": " ",
    "indent_size": 2,
    "indent_with_tabs": false,
    "newline_between_rules": true,
    "selector_separator": " ",
    "selector_separator_newline": false
  }
}
```
