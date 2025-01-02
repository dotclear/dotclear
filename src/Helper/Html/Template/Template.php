<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

use ArrayObject;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @class Template
 */
class Template
{
    // Constants

    public const CACHE_FOLDER = 'cbtpl';

    /**
     * Use cache for compiled template files
     *
     * @var        bool
     */
    public $use_cache = true;

    /**
     * Stack of node blocks callbacks
     *
     * @var        array<string, callable>
     */
    protected $blocks = [];

    /**
     * Stack of node values callbacks
     *
     * @var        array<string, callable>
     */
    protected $values = [];

    /**
     * Remove PHP from template file
     *
     * @var        bool
     */
    protected $remove_php = true;

    /**
     * Unknown node value callback
     *
     * @var        callable|null
     */
    protected $unknown_value_handler;

    /**
     * Unknown node block callback
     *
     * @var        callable|null
     */
    protected $unknown_block_handler;

    /**
     * Stack of template file paths
     *
     * @var        array<string>
     */
    protected $tpl_path = [];

    /**
     * Cache directory
     *
     * @var        string
     */
    protected $cache_dir;

    /**
     * Parent file
     *
     * May be a filename or "__parent__"
     */
    protected string $parent_file = '';

    /**
     * Stack of compiled template files
     *
     * @var        array<string>
     */
    protected $compile_stack = [];

    /**
     * Stack of parent template files
     *
     * @var        array<string>
     */
    protected $parent_stack = [];

    // Inclusion variables

    /**
     * Super globals
     *
     * @var        array<string>
     */
    protected static $superglobals = ['GLOBALS', '_SERVER', '_GET', '_POST', '_COOKIE', '_FILES', '_ENV', '_REQUEST', '_SESSION'];

    /**
     * Stacks of globals keys
     *
     * @var        array<string>
     */
    protected static $_k;

    /**
     * Working globals key name
     *
     * @var        string
     */
    protected static $_n;

    /**
     * Working output buffer
     *
     * @var        string|false
     */
    protected static $_r;

    /**
     * Constructs a new instance.
     *
     * @param      string  $cache_dir  The cache dir
     * @param      string  $self_name  The self name
     */
    public function __construct(
        string $cache_dir,
        /**
         * Instance self name
         *
         * Will be use in compiled template to call instance method or use instance properties
         */
        private readonly string $self_name
    ) {
        $this->setCacheDir($cache_dir);

        $this->addValue('include', $this->includeFile(...));
        $this->addBlock('Block', $this->blockSection(...));
    }

    /**
     * Node value "include" callback
     *
     * Syntax: {tpl:include src="filename"}
     *
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $attr   The attribute
     */
    public function includeFile($attr): string
    {
        if (!isset($attr['src'])) {
            return '';
        }

        $src = Path::clean($attr['src']);

        $tpl_file = $this->getFilePath($src);
        if (!$tpl_file) {
            return '';
        }
        if (in_array($tpl_file, $this->compile_stack)) {
            return '';
        }

        return
        '<?php try { ' .
        'echo ' . $this->self_name . "->getData('" . str_replace("'", "\'", $src) . "'); " .
            '} catch (Exception) {} ?>' . "\n";
    }

    /**
     * Node block "Block" callback
     *
     * Syntax: <tpl:Block name="name-of-block">[content]</tpl:Block>
     *
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $attr     The attribute
     * @param      string                                           $content  The content
     */
    public function blockSection($attr, string $content): string
    {
        // Ignore attributes and return block content only
        return $content;
    }

    /**
     * Sets the template path(s).
     *
     * Arguments may be a string or an array of string
     */
    public function setPath(): void
    {
        $path = [];

        foreach (func_get_args() as $v) {
            if (is_array($v)) {
                $path = [...$path, ...array_values($v)];
            } else {
                $path[] = $v;
            }
        }

        foreach ($path as $k => $v) {
            if (($v = Path::real($v)) === false) {
                unset($path[$k]);
            }
        }

        $this->tpl_path = array_unique($path);
    }

    /**
     * Prepend a template path.
     *
     * @param   string  $path   The path to prepend
     */
    public function prependPath(string $path): void
    {
        $this->setPath($path, $this->getPath());
    }

    /**
     * Append a template path.
     *
     * @param   string  $path   The path to append
     */
    public function appendPath(string $path): void
    {
        $this->setPath($this->getPath(), $path);
    }

    /**
     * Gets the template paths.
     *
     * @return     array<string>
     */
    public function getPath(): array
    {
        return $this->tpl_path;
    }

    /**
     * Sets the cache dir.
     *
     * @param      string     $dir    The dir
     *
     * @throws     Exception
     */
    public function setCacheDir(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new Exception($dir . ' is not a valid directory.');
        }

        if (!is_writable($dir)) {
            throw new Exception($dir . ' is not writable.');
        }

        $this->cache_dir = Path::real($dir) . '/';
    }

    /**
     * Adds a node block callback.
     *
     * The callback signature must be: callback(array $attr, string &$content)
     *
     * @param      string               $name      The name
     * @param      callable             $callback  The callback
     *
     * @throws     Exception
     */
    public function addBlock(string $name, $callback): void
    {
        if (!is_callable($callback)) {  // @phpstan-ignore-line waiting for add a callable type in method signature
            throw new Exception('No valid callback for ' . $name);
        }

        $this->blocks[$name] = $callback;
    }

    /**
     * Adds a node value callback.
     *
     * The callback signature must be: callback(array $attr [, string $str_attr])
     *
     * @param      string               $name      The name
     * @param      callable             $callback  The callback
     *
     * @throws     Exception
     */
    public function addValue(string $name, $callback): void
    {
        if (!is_callable($callback)) {  // @phpstan-ignore-line waiting for add a callable type in method signature
            throw new Exception('No valid callback for ' . $name);
        }

        $this->values[$name] = $callback;
    }

    /**
     * Determines if node block exists.
     *
     * @param      string  $name   The name
     *
     * @return     bool    True if block exists, False otherwise.
     */
    public function blockExists(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    /**
     * Determines if node value exists.
     *
     * @param      string  $name   The name
     *
     * @return     bool    True if value exists, False otherwise.
     */
    public function valueExists(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * Determines if ndoe tag (value or block) exists.
     *
     * @param      string  $name   The name
     *
     * @return     bool    True if tag exists, False otherwise.
     */
    public function tagExists(string $name): bool
    {
        return $this->blockExists($name) || $this->valueExists($name);
    }

    /**
     * Gets the node value callback.
     *
     * @param      string  $name   The value name
     *
     * @return     callable|false    The block callback.
     */
    public function getValueCallback(string $name)
    {
        if ($this->valueExists($name)) {
            return $this->values[$name];
        }

        return false;
    }

    /**
     * Gets the node block callback.
     *
     * @param      string  $name   The block name
     *
     * @return     callable|false    The block callback.
     */
    public function getBlockCallback(string $name)
    {
        if ($this->blockExists($name)) {
            return $this->blocks[$name];
        }

        return false;
    }

    /**
     * Gets the node blocks list.
     *
     * @return     array<string>  The blocks list.
     */
    public function getBlocksList(): array
    {
        return array_keys($this->blocks);
    }

    /**
     * Gets the node values list.
     *
     * @return     array<string>  The values list.
     */
    public function getValuesList(): array
    {
        return array_keys($this->values);
    }

    /**
     * Add on the fly custom header to all files.
     *
     * @return     string  The custom content
     */
    protected function addFileHeader(): string
    {
        return '';
    }

    /**
     * Gets the template file fullpath, creating it if not exist or not in cache and recent enough.
     *
     * @param      string     $file   The file
     *
     * @throws     Exception
     */
    public function getFile(string $file): string
    {
        $tpl_file = $this->getFilePath($file);

        if (!$tpl_file) {
            throw new Exception('No template found for ' . $file);
        }

        $dest_file = $this->getFileCachePath($tpl_file);

        clearstatcache();
        $stat_f = $stat_d = false;
        if (file_exists($dest_file)) {
            $stat_f = stat($tpl_file);
            $stat_d = stat($dest_file);
        }

        # We create template if:
        # - dest_file doest not exists
        # - we don't want cache
        # - dest_file size == 0
        # - tpl_file is more recent thant dest_file
        if (!$stat_d || !$this->use_cache || $stat_d['size'] == 0 || ($stat_f !== false && $stat_f['mtime'] > $stat_d['mtime'])) {
            Files::makeDir(dirname($dest_file), true);

            if (($fp = @fopen($dest_file, 'wb')) === false) {
                throw new Exception('Unable to create cache file');
            }

            $fc = $this->addFileHeader() . $this->compileFile($tpl_file);
            fwrite($fp, $fc);
            fclose($fp);
            Files::inheritChmod($dest_file);
        }

        return $dest_file;
    }

    /**
     * Gets the file cache path.
     *
     * @param      string  $tpl_file  The tpl file (full path)
     *
     * @return     string  The file cache path.
     */
    public function getFileCachePath(string $tpl_file): string
    {
        $file_md5 = md5($tpl_file);

        return sprintf(
            '%s/%s/%s/%s/%s.php',
            $this->cache_dir,
            self::CACHE_FOLDER,
            substr($file_md5, 0, 2),
            substr($file_md5, 2, 2),
            $file_md5
        );
    }

    /**
     * Gets the file path.
     *
     * @param      string       $file   The file
     */
    public function getFilePath(string $file): false|string
    {
        foreach ($this->tpl_path as $p) {
            if (file_exists($p . '/' . $file)) {
                return $p . '/' . $file;
            }
        }

        return false;
    }

    /**
     * Gets the parent file path.
     *
     * @param      string       $previous_path  The previous path
     * @param      string       $file           The file
     */
    public function getParentFilePath(string $previous_path, string $file): false|string
    {
        $check_file = false;
        foreach ($this->tpl_path as $p) {
            if ($check_file && file_exists($p . '/' . $file)) {
                return $p . '/' . $file;
            }
            if ($p == $previous_path) {
                $check_file = true;
            }
        }

        return false;
    }

    /**
     * Gets the template file content.
     *
     * @param      string  $________    The template filename
     *
     * @return     string  The data.
     */
    public function getData(string $________): string
    {
        self::$_k = array_keys($GLOBALS);   // @phpstan-ignore-line

        foreach (self::$_k as self::$_n) {
            if (!in_array(self::$_n, self::$superglobals)) {
                global ${self::$_n};
            }
        }
        $dest_file = $this->getFile($________);
        ob_start();
        if (ini_get('display_errors')) {
            include $dest_file;
        } else {
            @include $dest_file;
        }
        self::$_r = (string) ob_get_contents();
        ob_end_clean();

        return self::$_r;
    }

    /**
     * Gets the compiled tree.
     *
     * @param      string   $file   The file
     * @param      string   $err    The error
     *
     * @return     TplNode  The compiled tree.
     */
    protected function getCompiledTree(string $file, string &$err): TplNode
    {
        $fc = (string) file_get_contents($file);

        $this->compile_stack[] = $file;

        // Remove every PHP tags
        if ($this->remove_php) {
            $fc = (string) preg_replace('/<\?(?=php|=|\s).*?\?>/ms', '', $fc);
        }

        // Transform what could be considered as PHP short tags
        $fc = (string) preg_replace(
            '/(<\?(?!php|=|\s))(.*?)(\?>)/ms',
            '<?php echo "$1"; ?>$2<?php echo "$3"; ?>',
            $fc
        );

        // Remove template comments <!-- #... -->
        $fc = (string) preg_replace('/(^\s*)?<!-- #(.*?)-->/ms', '', $fc);

        # = tpl: blocks whitespace control syntax =
        # <tpl:...> or {{tpl:...}}
        #   No whitespace processing (original lexer behaviour)
        #
        # <~tpl:...> or {{~tpl:...}}
        #   Remove whitespaces before tag, only on the current line
        #   (stop processing at the beginning of line if reached)
        #
        # <tpl:...~> or {{tpl:...~}}
        #   Remove whitespaces after tag, up to and including one newline
        #   (stop processing at the end of line if reached)
        #

        # whitespace range definition - \s is not used on purpose here,
        # because the matching is done using the multiline option. Using
        # \s would consider newlines as valid whitespace.
        $ws = '[ \t]*';

        # wsnl adds an optional newline at the end of the previous
        # whitespace definition.
        $wsnl = $ws . "\n?";

        $blocks_re = '#'
                   # opening <tpl> blocks
                   . '(<tpl:\w+[^~>]*>)|'                    # no processing, plain block
                   . "(<tpl:\w+[^~>]*~>)$wsnl|"              # <tpl:...~>
                   . "$ws(<~tpl:\w+[^~>]*>)|"                # <~tpl:...>
                   . "$ws(<~tpl:\w+[^~>]*~>)$wsnl|"          # <~tpl:...~>
                   # closing </tpl> blocks
                   . '(</tpl:\w+[^~]>)|'                     # no processing, plain block
                   . "(</tpl:\w+~>)$wsnl|"                   # </tpl:...~>
                   . "$ws(</~tpl:\w+[^~]>)|"                 # </~tpl:...>
                   . "$ws(</~tpl:\w+~>)$wsnl|"               # </~tpl:...~>
                   # brace {{tpl}} blocks
                   . '({{tpl:\w+[^~}]*}})|'                  # no processing, plain block
                   . "({{tpl:\w+[^~}]*~}})$wsnl|"            # {{tpl:...~}}
                   . "$ws({{~tpl:\w+[^~}]*}})|"              # {{~tpl:...}}
                   . "$ws({{~tpl:\w+[^~}]*~}})$wsnl"         # {{~tpl:...~}}
                   . '#msu';

        // Lexer part : split file into small pieces
        // each array entry will be either a tag or plain text
        $blocks = preg_split(
            $blocks_re,
            $fc,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        // Next : build semantic tree from tokens.
        $rootNode          = new TplNode();
        $node              = $rootNode;
        $errors            = [];
        $this->parent_file = '';
        if ($blocks !== false) {
            foreach ($blocks as $block) {
                $isblock = preg_match('#<~?tpl:(\w+)(?:(\s+.*?)~?>|~?>)|</~?tpl:(\w+)~?>|{{~?tpl:(\w+)(\s(.*?))?~?}}#ms', $block, $match);
                if ($isblock == 1) {
                    if (substr($match[0] ?? '', 1, 1) === '/') {
                        // Closing tag, check if it matches current opened node
                        $tag = $match[3] ?? '';
                        if (($node instanceof TplNodeBlock) && $node->getTag() === $tag) {
                            $node->setClosing();
                            $node = $node->getParent();
                        } else {
                            // Closing tag does not match opening tag
                            // Search if it closes a parent tag
                            $search = $node;
                            while ($search?->getTag() != 'ROOT' && $search?->getTag() != $tag) {
                                $search = $search?->getParent();
                            }
                            if ($search?->getTag() == $tag) {
                                $errors[] = sprintf(
                                    __('Did not find closing tag for block <tpl:%s>. Content has been ignored.'),
                                    Html::escapeHTML($node?->getTag())
                                );
                                $search?->setClosing();
                                $node = $search?->getParent();
                            } else {
                                $errors[] = sprintf(
                                    __('Unexpected closing tag </tpl:%s> found.'),
                                    $tag
                                );
                            }
                        }
                    } elseif (str_starts_with($match[0] ?? '', '{')) {
                        // Value tag
                        $tag      = $match[4] ?? '';
                        $str_attr = '';
                        $attr     = [];
                        if (isset($match[6])) {
                            $str_attr = $match[6];
                            $attr     = $this->getAttrs($match[6]);
                        }
                        if (strtolower($tag) === 'extends') {
                            if (isset($attr['parent']) && is_string($attr['parent']) && $this->parent_file === '') {
                                $this->parent_file = $attr['parent'];
                            }
                        } elseif (strtolower($tag) === 'parent') {
                            $node?->addChild(new TplNodeValueParent($tag, $attr, $str_attr));
                        } else {
                            $node?->addChild(new TplNodeValue($tag, $attr, $str_attr));
                        }
                    } else {
                        // Opening tag, create new node and dive into it
                        $tag = $match[1] ?? '';
                        if ($tag === 'Block') {
                            $newnode = new TplNodeBlockDefinition($tag, isset($match[2]) ? $this->getAttrs($match[2]) : []);
                        } else {
                            $newnode = new TplNodeBlock($tag, isset($match[2]) ? $this->getAttrs($match[2]) : []);
                        }
                        $node?->addChild($newnode);
                        $node = $newnode;
                    }
                } else {
                    // Simple text
                    $node?->addChild(new TplNodeText($block));
                }
            }
        }

        if (($node instanceof TplNodeBlock) && !$node->isClosed()) {
            $errors[] = sprintf(
                __('Did not find closing tag for block <tpl:%s>. Content has been ignored.'),
                Html::escapeHTML($node->getTag())
            );
        }

        $err = '';
        if ($errors !== []) {
            $err = "\n\n<!-- \n" . __('WARNING: the following errors have been found while parsing template file :') . "\n * " . implode("\n * ", $errors) . "\n -->\n";
        }

        return $rootNode;
    }

    /**
     * Compile a template file
     *
     * @param      string     $file   The file
     *
     * @throws     Exception
     */
    protected function compileFile(string $file): string
    {
        $tree = null;
        $err  = '';
        while (true) {
            if ($file && !in_array($file, $this->parent_stack)) {
                $tree = $this->getCompiledTree($file, $err);

                if ($this->parent_file === '__parent__') {
                    $this->parent_stack[] = $file;
                    $newfile              = $this->getParentFilePath(dirname($file), basename($file));
                    if (!$newfile) {
                        throw new Exception('No template found for ' . basename($file));
                    }
                    $file = $newfile;
                } elseif ($this->parent_file !== '') {
                    $this->parent_stack[] = $file;
                    $file                 = $this->getFilePath($this->parent_file);
                    if ($file === false) {
                        throw new Exception('No template found for ' . $this->parent_file);
                    }
                } else {
                    return $tree->compile($this) . $err;
                }
            } else {
                if ($tree != null) {
                    return $tree->compile($this) . $err;
                }

                return '';
            }
        }
    }

    /**
     * Compile block node
     *
     * @param      string                                           $tag      The tag
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $attr     The attribute
     * @param      string                                           $content  The content
     */
    public function compileBlockNode(string $tag, $attr, string $content): string
    {
        $res = '';
        if (isset($this->blocks[$tag])) {
            $res .= call_user_func($this->blocks[$tag], $attr, $content);
        } elseif (is_callable($this->unknown_block_handler)) {
            $res .= call_user_func($this->unknown_block_handler, $tag, $attr, $content);
        }

        return $res;
    }

    /**
     * Compile value node
     *
     * @param      string                                           $tag       The tag
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $attr      The attribute
     * @param      string                                           $str_attr  The string attribute
     */
    public function compileValueNode(string $tag, $attr, string $str_attr): string
    {
        $res = '';
        if (isset($this->values[$tag])) {
            $res .= call_user_func($this->values[$tag], $attr, ltrim($str_attr));
        } elseif (is_callable($this->unknown_value_handler)) {
            $res .= call_user_func($this->unknown_value_handler, $tag, $attr, $str_attr);
        }

        return $res;
    }

    /**
     * Compile value
     *
     * @param      array<mixed>   $match  The match
     */
    protected function compileValue(array $match): string
    {
        $v        = $match[1];
        $attr     = isset($match[2]) && is_string($match[2]) ? $this->getAttrs($match[2]) : [];
        $str_attr = $match[2] ?? null;

        return call_user_func($this->values[$v], $attr, ltrim((string) $str_attr));
    }

    /**
     * Sets the unknown value handler.
     *
     * @param      callable|null  $callback  The callback
     */
    public function setUnknownValueHandler($callback): void
    {
        $this->unknown_value_handler = $callback;
    }

    /**
     * Sets the unknown block handler.
     *
     * @param      callable|null  $callback  The callback
     */
    public function setUnknownBlockHandler($callback): void
    {
        $this->unknown_block_handler = $callback;
    }

    /**
     * Gets the attributes.
     *
     * @param      string  $str    The string
     *
     * @return     array<string, mixed>   The attributes.
     */
    protected function getAttrs(string $str): array
    {
        $res = [];
        if (preg_match_all('|([a-zA-Z0-9_:-]+)="([^"]*)"|ms', $str, $m) > 0) {
            foreach ($m[1] as $i => $v) {
                $res[$v] = $m[2][$i];
            }
        }

        return $res;    // @phpstan-ignore-line
    }
}
