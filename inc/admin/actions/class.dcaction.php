<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * dcActions -- handler for action page on selected entries
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Network\Http;

abstract class dcActions
{
    /**
     * @var string form submit uri
     */
    protected $uri;
    /**
     * @var array action combo box
     */
    protected $combo = [];
    /**
     * @var ArrayObject list of defined actions (callbacks)
     */
    protected $actions;
    /**
     * @var array selected entries (each key is the entry id, value contains the entry description)
     */
    protected $entries = [];
    /**
     * @var MetaRecord record that challenges ids against permissions
     */
    protected $rs;
    /**
     * @var array redirection $_GET arguments, if any (does not contain ids by default, ids may be merged to it)
     */
    protected $redir_args;
    /**
     * @var array list of $_POST fields used to build the redirection
     */
    protected $redirect_fields = [];
    /**
     * @var string redirection anchor if any
     */
    protected $redir_anchor = '';
    /**
     * @var string current action, if any
     */
    protected $action = '';
    /**
     * @var ArrayObject list of url parameters (usually $_POST)
     */
    protected $from;
    /**
     * @var string form field name for "entries" (usually "entries")
     */
    protected $field_entries;
    /**
     * @var string title for checkboxes list, if displayed
     */
    protected $cb_title;
    /**
     * @var string title for caller page title
     */
    protected $caller_title;
    /**
     * @var boolean true if we are acting inside a plugin (different handling of begin/endpage)
     */
    protected $in_plugin = false;
    /**
     * @var boolean true if we enable to keep selection when redirecting
     */
    protected $enable_redir_selection = true;
    /**
     * @var boolean true if class uses silent process method and uses render method instead
     */
    protected $use_render = false;
    /**
     * @var string Action process content
     */
    protected $render = '';

    /**
     * Constructs a new instance.
     *
     * @param      null|string  $uri            The form uri
     * @param      array        $redirect_args  The redirect arguments
     */
    public function __construct(?string $uri, array $redirect_args = [])
    {
        $this->uri           = $uri;
        $this->actions       = new ArrayObject();
        $this->redir_args    = $redirect_args;
        $this->from          = new ArrayObject($_POST);
        $this->field_entries = 'entries';
        $this->cb_title      = __('Title');
        $this->caller_title  = __('Posts');

        if (isset($this->redir_args['action_anchor'])) {
            $this->redir_anchor = '#' . $this->redir_args['action_anchor'];
            unset($this->redir_args['action_anchor']);
        }

        $uri_parts = explode('?', $_SERVER['REQUEST_URI']);
        if ($uri_parts !== false) {
            $this->in_plugin = (strpos($uri_parts[0], 'plugin.php') !== false);
        }
    }

    /**
     * Define whether to keep selection when redirecting
     * Can be usefull to be disabled to preserve some compatibility.
     *
     * @param boolean $enable true to enable, false otherwise
     */
    public function setEnableRedirSelection(bool $enable)
    {
        $this->enable_redir_selection = $enable;
    }

    /**
     * Adds an action
     *
     * @param array             $actions  the actions names as if it was a standalone combo array.
     *                                    It will be merged with other actions.
     *                                    Can be bound to multiple values, if the same callback is to be called
     * @param callable|array    $callback the callback for the action.
     *
     * @return dcActions the actions page itself, enabling to chain addAction().
     */
    public function addAction(array $actions, $callback): dcActions
    {
        foreach ($actions as $group => $options) {
            // Check each case of combo definition
            // Store form values in $values
            if (is_array($options)) {
                $values              = array_values($options);
                $this->combo[$group] = array_merge($this->combo[$group] ?? [], $options);
            } elseif (
                $options instanceof formSelectOption || // CB: common/lib.form.php (deprecated Since 2.26)
                $options instanceof Option
            ) {
                $values              = [$options->value];
                $this->combo[$group] = $options->value;
            } else {
                $values              = [$options];
                $this->combo[$group] = $options;
            }
            // Associate each potential value to the callback
            foreach ($values as $value) {
                $this->actions[$value] = $callback;
            }
        }

        return $this;
    }

    /**
     * Returns the actions combo
     * Useable through form::combo/formOption (see addAction() method)
     *
     * @return array the actions combo
     */
    public function getCombo(): ?array
    {
        return $this->combo;
    }

    /**
     * Returns the list of selected entries
     *
     * @return array the list
     */
    public function getIDs(): array
    {
        return array_keys($this->entries);
    }

    /**
     * Returns the list of selected entries as HTML hidden fields string
     *
     * @return string the HTML code for hidden fields
     */
    public function getIDsHidden(): string
    {
        $ret = '';
        foreach (array_keys($this->entries) as $id) {
            $ret .= (new Hidden($this->field_entries . '[]', $id))->render();
        }

        return $ret;
    }

    /**
     * Returns the list of selected entries as an array of formHidden object.
     *
     * @return array The hidden form fields.
     */
    public function IDsHidden(): array
    {
        $ret = [];
        foreach (array_keys($this->entries) as $id) {
            $ret[] = (new Hidden($this->field_entries . '[]', $id));
        }

        return $ret;
    }

    /**
     * Returns all redirection parameters as HTML hidden fields
     *
     * @param boolean $with_ids if true, also include ids in HTML code
     *
     * @return string the HTML code for hidden fields
     */
    public function getHiddenFields(bool $with_ids = false): string
    {
        $ret = '';
        foreach ($this->redir_args as $name => $value) {
            $ret .= (new Hidden([$name], $value))->render();
        }
        if ($with_ids) {
            $ret .= $this->getIDsHidden();
        }

        return $ret;
    }

    /**
     * Returns all redirection parameters as an array of formHidden object.
     *
     * @param boolean $with_ids if true, also include ids in array
     *
     * @return array The hidden form fields.
     */
    public function hiddenFields(bool $with_ids = false): array
    {
        $ret = [];
        foreach ($this->redir_args as $name => $value) {
            $ret[] = (new Hidden([$name], $value));
        }
        if ($with_ids) {
            $ret = array_merge($ret, $this->IDsHidden());
        }

        return $ret;
    }

    /**
     * Get record from DB Query containing requested IDs
     *
     * @return MetaRecord
     */
    public function getRS(): MetaRecord
    {
        return $this->rs;
    }

    /**
     * Setup redirection arguments
     *
     *  by default, $_POST fields as defined in redirect_fields attributes
     *  are set into redirect_args.
     *
     * @param array|ArrayObject     $from   input to parse fields from (usually $_POST)
     */
    protected function setupRedir($from)
    {
        foreach ($this->redirect_fields as $field) {
            if (isset($from[$field])) {
                $this->redir_args[$field] = $from[$field];
            }
        }
    }

    /**
     * Returns redirection URL
     *
     * @param boolean $with_selected_entries if true, add selected entries in url
     * @param array $params extra parameters to append to redirection
     *  must be an array : each key is the name, each value is the wanted value
     *
     * @return string the redirection url
     */
    public function getRedirection(bool $with_selected_entries = false, array $params = []): string
    {
        $redirect_args = array_merge($params, $this->redir_args);
        if (isset($redirect_args['redir'])) {
            unset($redirect_args['redir']);
        }

        if ($with_selected_entries && $this->enable_redir_selection) {
            $redirect_args[$this->field_entries] = array_keys($this->entries);
        }

        return $this->uri . '?' . http_build_query($redirect_args) . $this->redir_anchor;
    }

    /**
     * Redirects to redirection page
     *
     * @param boolean $with_selected_entries if true, add selected entries in url
     * @param array $params extra parameters to append to redirection
     *  must be an array : each key is the name, each value is the wanted value
     *
     * @see getRedirection for arguments details
     * @return never
     */
    public function redirect(bool $with_selected_entries = false, array $params = [])
    {
        Http::redirect($this->getRedirection($with_selected_entries, $params));
        exit;
    }

    /**
     * Returns current form URI, if any
     *
     * @return string the form URI
     */
    public function getURI(): ?string
    {
        return $this->uri;
    }

    /**
     * Returns current form URI, if any
     *
     * @return string the form URI
     */
    public function getCallerTitle(): ?string
    {
        return $this->caller_title;
    }

    /**
     * Returns current action, if any
     *
     * @return string the action
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Proceeds action handling, if any
     *
     * This method may issue an exit() if an action is being processed.
     *  If it returns, no action has been performed
     */
    public function process()
    {
        $this->setupRedir($this->from);
        $this->fetchEntries($this->from);
        if (isset($this->from['action'])) {
            $this->action = $this->from['action'];
            $performed    = false;
            if ($this->use_render) {
                ob_start();
            }

            try {
                foreach ($this->actions as $action => $callback) {
                    if ($this->from['action'] == $action) {
                        $performed = true;
                        call_user_func($callback, $this, $this->from);
                    }
                }
            } catch (Exception $e) {
                $performed = true;
            }

            if ($this->use_render) {
                $this->render = (string) ob_get_contents();
                ob_end_clean();
            }
            if ($performed) {
                return true;
            }
        }
    }

    /**
     * Output action process contents.
     *
     * Only when property $use_render is set to true.
     */
    public function render(): void
    {
        echo (string) $this->render;
    }

    /**
     * Returns HTML code for selected entries as a table containing entries checkboxes
     *
     * @return string the HTML code for checkboxes
     */
    public function getCheckboxes(): string
    {
        $ret = '<table class="posts-list"><tr>' .
        '<th colspan="2">' . $this->cb_title . '</th>' .
            '</tr>';
        foreach ($this->entries as $id => $title) {
            $ret .= '<tr><td class="minimal">' .
            form::checkbox([$this->field_entries . '[]'], $id, [
                'checked' => true,
            ]) .
                '</td>' .
                '<td>' . $title . '</td></tr>';
        }
        $ret .= '</table>';

        return $ret;
    }

    /**
     * Displays the beginning of a page, if action does not redirects dirtectly
     *
     * This method is called from the actions themselves.
     *
     * @param string $breadcrumb breadcrumb to display
     * @param string $head    page header to include
     */
    abstract public function beginPage(string $breadcrumb = '', string $head = '');

    /**
     * Displays the ending of a page, if action does not redirects dirtectly
     *
     * This method is called from the actions themselves.
     */
    abstract public function endPage();

    /**
     * Fills-in information by requesting into db
     * This method may setup the following attributes
     * - entries : list of entries (checked against permissions)
     *   entries ids are array keys, values contain entry description (if relevant)
     * - rs : MetaRecord given by db request
     *
     * @param ArrayObject $from
     */
    abstract protected function fetchEntries(ArrayObject $from);
}
