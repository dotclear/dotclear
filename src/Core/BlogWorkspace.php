<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Exception;

/**
 * Blog workspace for settings handler.
 *
 * Handle id,version pairs through database.
 */
class BlogWorkspace implements BlogWorkspaceInterface
{
    public const NS_TABLE_NAME = 'setting';

    public const NS_NAME_SCHEMA = '/^[a-zA-Z][a-zA-Z0-9]+$/';
    public const NS_ID_SCHEMA   = '/^[a-zA-Z][a-zA-Z0-9_]+$/';

    public const NS_STRING = 'string';
    public const NS_FLOAT  = 'float';
    public const NS_BOOL   = 'boolean';
    public const NS_INT    = 'integer';
    public const NS_ARRAY  = 'array';

    public const NS_DOUBLE = 'double';     // -> NS_FLOAT

    public const NS_TEXT    = self::NS_STRING;
    public const NS_BOOLEAN = self::NS_BOOL;
    public const NS_INTEGER = self::NS_INT;

    /** @var    ConnectionInterface     The connetion handler */
    protected ConnectionInterface $con;

    /** @var    string  Settings table name */
    protected string $table;

    /** @var    array   Global settings */
    protected array $global_settings = [];

    /** @var    array   Local settings */
    protected $local_settings = [];

    /** @var    array   Blog settings */
    protected $settings = [];

    /** @var    string  Settings table name */
    protected ?string $blog_id;

    /** @var    string  Settings table name */
    protected ?string $workspace;

    public function __construct(?string $blog_id = null, ?string $workspace = null, ?MetaRecord $rs = null)
    {
        $this->con   = App::con();
        $this->table = $this->con->prefix() . self::NS_TABLE_NAME;

        if ($workspace !== null) {
            if (!preg_match(self::NS_NAME_SCHEMA, $workspace)) {
                throw new Exception(sprintf(__('Invalid setting dcNamespace: %s'), $workspace));
            }

            $this->settings  = $this->local_settings = $this->global_settings = [];
            $this->blog_id   = $blog_id;
            $this->workspace = $workspace;

            $this->getSettings($rs);
        }
    }

    public function openBlogWorkspaceCursor(): Cursor
    {
        return $this->con->openCursor($this->table);
    }

    public function init(?string $blog_id, string $workspace, ?MetaRecord $rs = null): BlogWorkspaceInterface
    {
        return new self($blog_id, $workspace, $rs);
    }

    /**
     * Gets the settings.
     *
     * @param   MetaRecord  $rs     The recordset
     */
    private function getSettings(?MetaRecord $rs = null): void
    {
        if ($rs === null) {
            $sql = new SelectStatement();
            $sql
                ->columns([
                    'blog_id',
                    'setting_id',
                    'setting_value',
                    'setting_type',
                    'setting_label',
                    'setting_ns',
                ])
                ->from($this->table)
                ->where($sql->orGroup([
                    'blog_id = ' . $sql->quote((string) $this->blog_id),
                    'blog_id IS NULL',
                ]))
                ->and('setting_ns = ' . $sql->quote($this->workspace))
                ->order('setting_id DESC');

            try {
                $rs = $sql->select();
            } catch (Exception $e) {
                trigger_error(__('Unable to retrieve settings:') . ' ' . $this->con->error(), E_USER_ERROR);
            }
        }
        while ($rs->fetch()) {
            if ($rs->f('setting_ns') !== $this->workspace) {
                break;
            }
            $id    = trim((string) $rs->f('setting_id'));
            $value = $rs->f('setting_value');
            $type  = $rs->f('setting_type');

            if ($type === self::NS_ARRAY) {
                $value = @json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } else {
                if ($type === self::NS_FLOAT || $type === self::NS_DOUBLE) {
                    $type = self::NS_FLOAT;
                } elseif ($type !== self::NS_BOOL && $type !== self::NS_INT) {
                    $type = self::NS_STRING;
                }
            }

            settype($value, $type);

            $array = ($rs->blog_id ? 'local' : 'global') . '_settings';

            $this->{$array}[$id] = [
                'ns'     => $this->workspace,
                'value'  => $value,
                'type'   => $type,
                'label'  => (string) $rs->f('setting_label'),
                'global' => (!$rs->blog_id),
            ];
        }

        // Blog settings (local) overwrite global ones
        $this->settings = array_merge($this->global_settings, $this->local_settings);
    }

    public function settingExists(string $name, bool $global = false): bool
    {
        $array = ($global ? 'global' : 'local') . '_settings';

        return isset($this->{$array}[$name]);
    }

    public function get($name)
    {
        if (isset($this->settings[$name]) && isset($this->settings[$name]['value'])) {
            return $this->settings[$name]['value'];
        }
    }

    public function getGlobal($name)
    {
        if (isset($this->global_settings[$name]) && isset($this->global_settings[$name]['value'])) {
            return $this->global_settings[$name]['value'];
        }
    }

    public function getLocal($name)
    {
        if (isset($this->local_settings[$name]) && isset($this->local_settings[$name]['value'])) {
            return $this->local_settings[$name]['value'];
        }
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function set($name, $value): void
    {
        if (isset($this->settings[$name])) {
            $this->settings[$name]['value'] = $value;
        }
    }

    public function __set($name, $value): void
    {
        $this->set($name, $value);
    }

    public function put(string $name, $value, ?string $type = null, ?string $label = null, bool $ignore_value = true, bool $global = false): void
    {
        if (!preg_match(self::NS_ID_SCHEMA, $name)) {
            throw new Exception(sprintf(__('%s is not a valid setting id'), $name));
        }

        # We don't want to change setting value
        if (!$ignore_value) {
            if (!$global && $this->settingExists($name, false)) {
                $value = $this->local_settings[$name]['value'];
            } elseif ($this->settingExists($name, true)) {
                $value = $this->global_settings[$name]['value'];
            }
        }

        # Setting type
        if ($type === self::NS_DOUBLE) {
            $type = self::NS_FLOAT;
        } elseif ($type === null) {
            if (!$global && $this->settingExists($name, false)) {
                $type = $this->local_settings[$name]['type'];
            } elseif ($this->settingExists($name, true)) {
                $type = $this->global_settings[$name]['type'];
            } else {
                if (is_array($value)) {
                    $type = self::NS_ARRAY;
                } else {
                    $type = self::NS_STRING;
                }
            }
        } elseif ($type !== self::NS_BOOL && $type !== self::NS_INT && $type !== self::NS_FLOAT && $type !== self::NS_ARRAY) {
            $type = self::NS_STRING;
        }

        # We don't change label
        if (!$label) {
            if (!$global && $this->settingExists($name, false)) {
                $label = $this->local_settings[$name]['label'];
            } elseif ($this->settingExists($name, true)) {
                $label = $this->global_settings[$name]['label'];
            }
        }

        if ($type !== self::NS_ARRAY) {
            settype($value, $type);
        } else {
            $value = json_encode($value, JSON_THROW_ON_ERROR);
        }

        $cur = $this->con->openCursor($this->table);

        $cur->setting_value = ($type === self::NS_BOOL) ? (string) (int) $value : (string) $value;
        $cur->setting_type  = $type;
        $cur->setting_label = $label;

        #If we are local, compare to global value
        if (!$global && $this->settingExists($name, true)) {
            $g            = $this->global_settings[$name];
            $same_setting = ($g['ns'] === $this->workspace && $g['value'] === $value && $g['type'] === $type && $g['label'] === $label);

            # Drop setting if same value as global
            if ($same_setting && $this->settingExists($name, false)) {
                $this->drop($name);
            } elseif ($same_setting) {
                return;
            }
        }

        if ($this->settingExists($name, $global) && $this->workspace == $this->settings[$name]['ns']) {
            $sql = new UpdateStatement();

            if ($global) {
                $sql->where('blog_id IS NULL');
            } else {
                $sql->where('blog_id = ' . $sql->quote($this->blog_id));
            }
            $sql
                ->and('setting_id = ' . $sql->quote($name))
                ->and('setting_ns = ' . $sql->quote($this->workspace));

            $sql->update($cur);
        } else {
            $cur->setting_id = $name;
            $cur->blog_id    = $global ? null : $this->blog_id;
            $cur->setting_ns = $this->workspace;

            $cur->insert();
        }
    }

    public function rename(string $old_name, string $new_name): bool
    {
        if (!$this->workspace) {
            throw new Exception(__('No namespace specified'));
        }

        if (!array_key_exists($old_name, $this->settings) || array_key_exists($new_name, $this->settings)) {
            return false;
        }

        if (!preg_match(self::NS_ID_SCHEMA, $new_name)) {
            throw new Exception(sprintf(__('%s is not a valid setting id'), $new_name));
        }

        // Rename the setting in the settings array
        $this->settings[$new_name] = $this->settings[$old_name];
        unset($this->settings[$old_name]);

        if (isset($this->global_settings[$old_name])) {
            $this->global_settings[$new_name] = $this->global_settings[$old_name];
            unset($this->global_settings[$old_name]);
        }
        if (isset($this->local_settings[$old_name])) {
            $this->local_settings[$new_name] = $this->local_settings[$old_name];
            unset($this->local_settings[$old_name]);
        }

        // Rename the setting in the database
        $sql = new UpdateStatement();
        $sql
            ->ref($this->table)
            ->set('setting_id = ' . $sql->quote($new_name))
            ->where('setting_ns = ' . $sql->quote($this->workspace))
            ->and('setting_id = ' . $sql->quote($old_name));

        $sql->update();

        return true;
    }

    public function drop(string $name): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No namespace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if ($this->blog_id === null) {
            $sql->where('blog_id IS NULL');
        } else {
            $sql->where('blog_id = ' . $sql->quote($this->blog_id));
        }

        $sql
            ->and('setting_id = ' . $sql->quote($name))
            ->and('setting_ns = ' . $sql->quote($this->workspace));

        $sql->delete();
    }

    public function dropEvery(string $name, bool $global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No namespace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (!$global) {
            $sql->where('blog_id IS NOT NULL');
        }
        $sql
            ->and('setting_id = ' . $sql->quote($name))
            ->and('setting_ns = ' . $sql->quote($this->workspace));

        $sql->delete();
    }

    public function dropAll(bool $force_global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No namespace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (($force_global) || ($this->blog_id === null)) {
            $sql->where('blog_id IS NULL');
            $global = true;
        } else {
            $sql->where('blog_id = ' . $sql->quote($this->blog_id));
            $global = false;
        }

        $sql->and('setting_ns = ' . $sql->quote($this->workspace));

        $sql->delete();

        $array          = ($global ? 'global' : 'local') . '_settings';
        $this->{$array} = [];

        // Blog settings (local) overwrite global ones
        $this->settings = array_merge($this->global_settings, $this->local_settings);
    }

    public function dumpWorkspace(): string
    {
        return $this->workspace;
    }

    public function dumpSettings(): array
    {
        return $this->settings;
    }

    public function dumpLocalSettings(): array
    {
        return $this->local_settings;
    }

    public function dumpGlobalSettings(): array
    {
        return $this->global_settings;
    }

    public function dumpNamespace(): string
    {
        Deprecated::set(self::class . '::dumpWorkspace()', '2.28');

        return $this->workspace;
    }
}
