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
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Exception;

/**
 * User workspace for preferences handler.
 */
class UserWorkspace implements UserWorkspaceInterface
{
    /**
     * Database connection handler.
     *
     * @var     ConnectionInterface     $con
     */
    protected ConnectionInterface $con;

    /**
     * Preferences table name.
     *
     * @var     string  $table
     */
    protected string $table;

    /**
     * User ID.
     *
     * @var     string  $user_id
     */
    protected ?string $user_id;

    /**
     * Global preferences.
     *
     * @var     array   Global preferences
     */
    protected array $global_prefs = [];

    /**
     * Local preferences.
     *
     * @var     array   $local_prefs
     */
    protected array $local_prefs = [];

    /**
     * User preferences.
     *
     * @var     array   $prefs
     */
    protected array $prefs = [];

    /**
     * Current workspace name.
     *
     * @var     string  $workspace
     */
    protected ?string $workspace;

    public function __construct(?string $user_id = null, ?string $workspace = null, ?MetaRecord $rs = null)
    {
        $this->con   = App::con();
        $this->table = $this->con->prefix() . self::WS_TABLE_NAME;

        if ($workspace !== null) {
            if (!preg_match(self::WS_NAME_SCHEMA, $workspace)) {
                throw new Exception(sprintf(__('Invalid dcWorkspace: %s'), $workspace));
            }

            $this->prefs     = $this->local_prefs = $this->global_prefs = [];
            $this->user_id   = $user_id;
            $this->workspace = $workspace;

            try {
                $this->getPrefs($rs);
            } catch (Exception $e) {
                trigger_error(__('Unable to retrieve prefs:') . ' ' . $this->con->error(), E_USER_ERROR);
            }
        }
    }

    public function openUserWorkspaceCursor(): Cursor
    {
        return $this->con->openCursor($this->table);
    }

    public function init(?string $user_id, string $workspace, ?MetaRecord $rs = null): UserWorkspaceInterface
    {
        return new self($user_id, $workspace, $rs);
    }

    /**
     * Gets the preferences.
     *
     * @param   MetaRecord  $rs     The recordset
     */
    private function getPrefs(?MetaRecord $rs = null): void
    {
        if ($rs === null) {
            $sql = new SelectStatement();
            $sql
                ->columns([
                    'user_id',
                    'pref_id',
                    'pref_value',
                    'pref_type',
                    'pref_label',
                    'pref_ws',
                ])
                ->from($this->table)
                ->where($sql->orGroup([
                    'user_id = ' . $sql->quote($this->user_id),
                    'user_id IS NULL',
                ]))
                ->and('pref_ws = ' . $sql->quote($this->workspace))
                ->order('pref_id ASC');

            try {
                $rs = $sql->select();
            } catch (Exception $e) {
                throw $e;
            }
        }
        while ($rs->fetch()) {
            if ($rs->f('pref_ws') !== $this->workspace) {
                break;
            }
            $name  = trim((string) $rs->f('pref_id'));
            $value = $rs->f('pref_value');
            $type  = $rs->f('pref_type');

            if ($type === self::WS_ARRAY) {
                $value = @json_decode($value, true);
            } else {
                if ($type === self::WS_FLOAT || $type === self::WS_DOUBLE) {
                    $type = self::WS_FLOAT;
                } elseif ($type !== self::WS_BOOL && $type !== self::WS_INT) {
                    $type = self::WS_STRING;
                }
            }

            settype($value, $type);

            $array = ($rs->user_id ? 'local' : 'global') . '_prefs';

            $this->{$array}[$name] = [
                'ws'     => $this->workspace,
                'value'  => $value,
                'type'   => $type,
                'label'  => (string) $rs->f('pref_label'),
                'global' => (!$rs->user_id),
            ];
        }

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    public function prefExists(string $name, bool $global = false): bool
    {
        $array = ($global ? 'global' : 'local') . '_prefs';

        return isset($this->{$array}[$name]);
    }

    public function get(string $name)
    {
        if (isset($this->prefs[$name]) && isset($this->prefs[$name]['value'])) {
            return $this->prefs[$name]['value'];
        }
    }

    public function getGlobal(string $name)
    {
        if (isset($this->global_prefs[$name]) && isset($this->global_prefs[$name]['value'])) {
            return $this->global_prefs[$name]['value'];
        }
    }

    public function getLocal(string $name)
    {
        if (isset($this->local_prefs[$name]) && isset($this->local_prefs[$name]['value'])) {
            return $this->local_prefs[$name]['value'];
        }
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function set(string $name, $value): void
    {
        if (isset($this->prefs[$name])) {
            $this->prefs[$name]['value'] = $value;
        }
    }

    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    public function put(string $name, $value, ?string $type = null, ?string $label = null, bool $ignore_value = true, bool $global = false): void
    {
        if (!preg_match(self::WS_ID_SCHEMA, $name)) {
            throw new Exception(sprintf(__('%s is not a valid pref id'), $name));
        }

        // We don't want to change pref value
        if (!$ignore_value) {
            if (!$global && $this->prefExists($name, false)) {
                $value = $this->local_prefs[$name]['value'];
            } elseif ($this->prefExists($name, true)) {
                $value = $this->global_prefs[$name]['value'];
            }
        }

        // Pref type
        if ($type === self::WS_DOUBLE) {
            $type = self::WS_FLOAT;
        } elseif ($type === null) {
            if (!$global && $this->prefExists($name, false)) {
                $type = $this->local_prefs[$name]['type'];
            } elseif ($this->prefExists($name, true)) {
                $type = $this->global_prefs[$name]['type'];
            } else {
                if (is_array($value)) {
                    $type = self::WS_ARRAY;
                } else {
                    $type = self::WS_STRING;
                }
            }
        } elseif ($type !== self::WS_BOOL && $type !== self::WS_INT && $type !== self::WS_FLOAT && $type !== self::WS_ARRAY) {
            $type = self::WS_STRING;
        }

        // We don't change label
        if (!$label) {
            if (!$global && $this->prefExists($name, false)) {
                $label = $this->local_prefs[$name]['label'];
            } elseif ($this->prefExists($name, true)) {
                $label = $this->global_prefs[$name]['label'];
            }
        }

        if ($type !== self::WS_ARRAY) {
            settype($value, $type);
        } else {
            $value = json_encode($value);
        }

        $cur = $this->con->openCursor($this->table);

        $cur->pref_value = ($type === self::WS_BOOL) ? (string) (int) $value : (string) $value;
        $cur->pref_type  = $type;
        $cur->pref_label = $label;

        #If we are local, compare to global value
        if (!$global && $this->prefExists($name, true)) {
            $g         = $this->global_prefs[$name];
            $same_pref = ($g['ws'] === $this->workspace && $g['value'] === $value && $g['type'] === $type && $g['label'] === $label);

            # Drop pref if same value as global
            if ($same_pref && $this->prefExists($name, false)) {
                $this->drop($name);
            } elseif ($same_pref) {
                return;
            }
        }

        if ($this->prefExists($name, $global) && $this->workspace === $this->prefs[$name]['ws']) {
            $sql = new UpdateStatement();

            if ($global) {
                $sql->where('user_id IS NULL');
            } else {
                $sql->where('user_id = ' . $sql->quote($this->user_id));
            }
            $sql
                ->and('pref_id = ' . $sql->quote($name))
                ->and('pref_ws = ' . $sql->quote($this->workspace));

            $sql->update($cur);
        } else {
            $cur->pref_id = $name;
            $cur->user_id = $global ? null : $this->user_id;
            $cur->pref_ws = $this->workspace;

            $cur->insert();
        }
    }

    public function rename(string $old_name, string $new_name): bool
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        if (!array_key_exists($old_name, $this->prefs) || array_key_exists($new_name, $this->prefs)) {
            return false;
        }

        if (!preg_match(self::WS_ID_SCHEMA, $new_name)) {
            throw new Exception(sprintf(__('%s is not a valid pref id'), $new_name));
        }

        // Rename the pref in the prefs array
        $this->prefs[$new_name] = $this->prefs[$old_name];
        unset($this->prefs[$old_name]);

        // Rename the pref in the database
        $sql = new UpdateStatement();
        $sql
            ->ref($this->table)
            ->set('pref_id = ' . $sql->quote($new_name))
            ->where('pref_ws = ' . $sql->quote($this->workspace))
            ->and('pref_id = ' . $sql->quote($old_name));

        $sql->update();

        // Reload preferences from database
        $this->global_prefs = $this->local_prefs = $this->prefs = [];
        $this->getPrefs();

        return true;
    }

    public function drop(string $name, bool $force_global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (($force_global) || ($this->user_id === null)) {
            $sql->where('user_id IS NULL');
            $global = true;
        } else {
            $sql->where('user_id = ' . $sql->quote($this->user_id));
            $global = false;
        }

        $sql
            ->and('pref_id = ' . $sql->quote($name))
            ->and('pref_ws = ' . $sql->quote($this->workspace));

        $sql->delete();

        if ($this->prefExists($name, $global)) {
            $array = ($global ? 'global' : 'local') . '_prefs';
            unset($this->{$array}[$name]);
        }

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    public function dropEvery(string $name, bool $global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (!$global) {
            $sql->where($sql->isNotNull('user_id'));
        }
        $sql
            ->and('pref_id = ' . $sql->quote($name))
            ->and('pref_ws = ' . $sql->quote($this->workspace));

        $sql->delete();

        if ($this->prefExists($name, false)) {
            unset($this->local_prefs[$name]);
        }
        if ($global && $this->prefExists($name, true)) {
            unset($this->global_prefs[$name]);
        }

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    public function dropAll(bool $force_global = false): void
    {
        if (!$this->workspace) {
            throw new Exception(__('No workspace specified'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if (($force_global) || ($this->user_id === null)) {
            $sql->where('user_id IS NULL');
            $global = true;
        } else {
            $sql->where('user_id = ' . $sql->quote($this->user_id));
            $global = false;
        }

        $sql->and('pref_ws = ' . $sql->quote($this->workspace));

        $sql->delete();

        // Reset global/local preferencess
        $array          = ($global ? 'global' : 'local') . '_prefs';
        $this->{$array} = [];

        // User preferences (local) overwrite global ones
        $this->prefs = array_merge($this->global_prefs, $this->local_prefs);
    }

    public function dumpWorkspace(): string
    {
        return $this->workspace;
    }

    public function dumpPrefs(): array
    {
        return $this->prefs;
    }

    public function dumpLocalPrefs(): array
    {
        return $this->local_prefs;
    }

    public function dumpGlobalPrefs(): array
    {
        return $this->global_prefs;
    }
}
