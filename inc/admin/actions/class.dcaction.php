<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

/**
 * dcActionsPage -- handler for action page on selected entries
 *
 */
abstract class dcActionsPage
{
    /** @var string form submit uri */
    protected $uri;
    /** @var dcCore dotclear core instance */
    protected $core;
    /** @var array action combo box */
    protected $combo;
    /** @var array list of defined actions (callbacks) */
    protected $actions;
    /** @var array selected entries (each key is the entry id, value contains the entry description) */
    protected $entries;
    /** @var record record that challenges ids against permissions */
    protected $rs;
    /** @var array redirection $_GET arguments, if any (does not contain ids by default, ids may be merged to it) */
    protected $redir_args;
    /** @var array list of $_POST fields used to build the redirection  */
    protected $redirect_fields;
    /** @var string redirection anchor if any  */
    protected $redir_anchor;

    /** @var string current action, if any */
    protected $action;
    /** @var array list of url parameters (usually $_POST) */
    protected $from;
    /** @var string form field name for "entries" (usually "entries") */
    protected $field_entries;

    /** @var string title for checkboxes list, if displayed */
    protected $cb_title;

    /** @var string title for caller page title */
    protected $caller_title;

    /** @var boolean true if we are acting inside a plugin (different handling of begin/endpage) */
    protected $in_plugin;

    /** @var boolean true if we enable to keep selection when redirecting */
    protected $enable_redir_selection;

    /**
     * Class constructor
     *
     * @param mixed  $core   dotclear core
     * @param mixed  $uri   form uri
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct($core, $uri, $redirect_args = array())
    {
        $this->core            = $core;
        $this->actions         = new ArrayObject();
        $this->combo           = array();
        $this->uri             = $uri;
        $this->redir_args      = $redirect_args;
        $this->redirect_fields = array();
        $this->action          = '';
        $this->cb_title        = __('Title');
        $this->entries         = array();
        $this->from            = new ArrayObject($_POST);
        $this->field_entries   = 'entries';
        $this->caller_title    = __('Entries');
        if (isset($this->redir_args['_ANCHOR'])) {
            $this->redir_anchor = '#' . $this->redir_args['_ANCHOR'];
            unset($this->redir_args['_ANCHOR']);
        } else {
            $this->redir_anchor = '';
        }
        $u                            = explode('?', $_SERVER['REQUEST_URI']);
        $this->in_plugin              = (strpos($u[0], 'plugin.php') !== false);
        $this->enable_redir_selection = true;
    }

    /**
     * setEnableRedirSelection - define whether to keep selection when redirecting
     *                            Can be usefull to be disabled to preserve some compatibility.
     *
     * @param boolean $enable true to enable, false otherwise
     *
     * @access public
     */
    public function setEnableRedirSelection($enable)
    {
        $this->enable_redir_selection = $enable;
    }

    /**
     * addAction - adds an action
     *
     * @param string $actions the actions names as if it was a standalone combo array.
     *                           It will be merged with other actions.
     *                           Can be bound to multiple values, if the same callback is to be called
     * @param callback $callback the callback for the action.
     *
     * @access public
     *
     * @return dcActionsPage the actions page itself, enabling to chain addAction().
     */
    public function addAction($actions, $callback)
    {
        foreach ($actions as $k => $a) {
            // Check each case of combo definition
            // Store form values in $values
            if (is_array($a)) {
                $values = array_values($a);
                if (!isset($this->combo[$k])) {
                    $this->combo[$k] = array();
                }
                $this->combo[$k] = array_merge($this->combo[$k], $a);
            } elseif ($a instanceof formSelectOption) {
                $values          = array($a->value);
                $this->combo[$k] = $a->value;
            } else {
                $values          = array($a);
                $this->combo[$k] = $a;
            }
            // Associate each potential value to the callback
            foreach ($values as $v) {
                $this->actions[$v] = $callback;
            }
        }
        return $this;
    }

    /**
     * getCombo - returns the actions combo, useable through form::combo
     *
     * @access public
     *
     * @return array the actions combo
     */
    public function getCombo()
    {
        return $this->combo;
    }

    /**
     * getIDS() - returns the list of selected entries
     *
     * @access public
     *
     * @return array the list
     */
    public function getIDs()
    {
        return array_keys($this->entries);
    }

    /**
     * getIDS() - returns the list of selected entries as HTML hidden fields string
     *
     * @access public
     *
     * @return string the HTML code for hidden fields
     */
    public function getIDsHidden()
    {
        $ret = '';
        foreach ($this->entries as $id => $v) {
            $ret .= form::hidden($this->field_entries . '[]', $id);
        }
        return $ret;
    }

    /**
     * getHiddenFields() - returns all redirection parameters as HTML hidden fields
     *
     * @param boolean $with_ids if true, also include ids in HTML code
     *
     * @access public
     *
     * @return string the HTML code for hidden fields
     */
    public function getHiddenFields($with_ids = false)
    {
        $ret = '';
        foreach ($this->redir_args as $k => $v) {
            $ret .= form::hidden(array($k), $v);
        }
        if ($with_ids) {
            $ret .= $this->getIDsHidden();
        }
        return $ret;
    }

    /**
     * getRS() - get record from DB Query containing requested IDs
     *
     * @param boolean $with_ids if true, also include ids in HTML code
     *
     * @access public
     *
     * @return string the HTML code for hidden fields
     */
    public function getRS()
    {
        return $this->rs;
    }

    /**
     * setupRedir - setup redirection arguments
     *  by default, $_POST fields as defined in redirect_fields attributes
     *  are set into redirect_args.
     *
     * @param array $from input to parse fields from (usually $_POST)
     *
     * @access protected
     */
    protected function setupRedir($from)
    {
        foreach ($this->redirect_fields as $p) {
            if (isset($from[$p])) {
                $this->redir_args[$p] = $from[$p];
            }
        }
    }

    /**
     * getRedirection - returns redirection URL
     *
     * @param array $params extra parameters to append to redirection
     *                        must be an array : each key is the name,
     *                        each value is the wanted value
     * @param boolean $with_selected_entries if true, add selected entries in url
     *
     * @access public
     *
     * @return string the redirection url
     */
    public function getRedirection($with_selected_entries = false, $params = array())
    {
        $redir_args = array_merge($params, $this->redir_args);
        if (isset($redir_args['redir'])) {
            unset($redir_args['redir']);
        }

        if ($with_selected_entries && $this->enable_redir_selection) {
            $redir_args[$this->field_entries] = array_keys($this->entries);
        }
        return $this->uri . '?' . http_build_query($redir_args) . $this->redir_anchor;
    }

    /**
     * redirect - redirects to redirection page
     *
     * @see getRedirection for arguments details
     *
     * @access public
     */
    public function redirect($with_selected_entries = false, $params = array())
    {
        http::redirect($this->getRedirection($with_selected_entries, $params));
        exit;
    }

    /**
     * getURI - returns current form URI, if any
     *
     * @access public
     *
     * @return string the form URI
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * getCallerTitle - returns current form URI, if any
     *
     * @access public
     *
     * @return string the form URI
     */
    public function getCallerTitle()
    {
        return $this->caller_title;
    }

    /**
     * getAction - returns current action, if any
     *
     * @access public
     *
     * @return string the action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * process - proceeds action handling, if any
     *             this method may issue an exit() if
     *            an action is being processed. If it
     *            returns, no action has been performed
     *
     * @access public
     */
    public function process()
    {

        $this->setupRedir($this->from);
        $this->fetchEntries($this->from);
        if (isset($this->from['action'])) {
            $this->action = $this->from['action'];
            try {
                $performed = false;
                foreach ($this->actions as $k => $v) {
                    if ($this->from['action'] == $k) {
                        $performed = true;
                        call_user_func($v, $this->core, $this, $this->from);
                    }
                }
                if ($performed) {
                    return true;
                }
            } catch (Exception $e) {
                $this->error($e);
                return true;
            }
        }
    }

    /**
     * getcheckboxes -returns html code for selected entries
     *             as a table containing entries checkboxes
     *
     * @access public
     *
     * @return string the html code for checkboxes
     */
    public function getCheckboxes()
    {
        $ret =
        '<table class="posts-list"><tr>' .
        '<th colspan="2">' . $this->cb_title . '</th>' .
            '</tr>';
        foreach ($this->entries as $id => $title) {
            $ret .=
            '<tr><td class="minimal">' .
            form::checkbox(array($this->field_entries . '[]'), $id, array(
                'checked' => true
            )) .
                '</td>' .
                '<td>' . $title . '</td></tr>';
        }
        $ret .= '</table>';
        return $ret;
    }

    /**
     * beginPage, endPage - displays the beginning/ending of a page, if action does not redirects dirtectly
     *
     * These methods are called from the actions themselves.
     *
     * @param string $breadcrumb breadcrumb to display
     * @param string $head    page header to include
     *
     * @access public
     */
    abstract public function beginPage($breadcrumb = '', $head = '');
    abstract public function endPage();

    /**
     * fetchEntries - fills-in information by requesting into db
     *     this method may setup the following attributes
     *   * entries : list of entries (checked against permissions)
     *      entries ids are array keys, values contain entry description (if relevant)
     *   * rs : record given by db request
     * @access protected
     */
    abstract protected function fetchEntries($from);

}
