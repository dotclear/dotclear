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
 * dcNotices -- Backend notices handling facilities
 *
 */
class dcNotices
{
    /** @var dcCore dotclear core instance */
    protected $core;

    private $N_TYPES = [
        // id → CSS class
        "success" => "success",
        "warning" => "warning-msg",
        "error"   => "error",
        "message" => "message",
        "static"  => "static-msg"];

    private $error_displayed = false;

    /**
     * Class constructor
     *
     * @param mixed  $core   dotclear core
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct($core)
    {
        $this->core = $core;
    }

    /* Session based notices */

    public function getNotices()
    {
        $res = '';

        // return error messages if any
        if ($this->core->error->flag() && !$this->error_displayed) {

            # --BEHAVIOR-- adminPageNotificationError
            $notice_error = $this->core->callBehavior('adminPageNotificationError', $this->core, $this->core->error);

            if (isset($notice_error) && !empty($notice_error)) {
                $res .= $notice_error;
            } else {
                $res .= '<div class="error" role="alert"><p>' .
                '<strong>' . (count($this->core->error->getErrors()) > 1 ? __('Errors:') : __('Error:')) . '</strong>' .
                '</p>' . $this->core->error->toHTML() . '</div>';
            }
            $this->error_displayed = true;
        }

        // return notices if any
        if (isset($_SESSION['notifications'])) {
            foreach ($_SESSION['notifications'] as $notification) {
                # --BEHAVIOR-- adminPageNotification
                $notice = $this->core->callBehavior('adminPageNotification', $this->core, $notification);

                $res .= (isset($notice) && !empty($notice) ? $notice : $this->getNotification($notification));
            }
            unset($_SESSION['notifications']);
            // unset seems not to be sufficient, so initialize to an empty array
            $_SESSION['notifications'] = [];
        }
        return $res;
    }

    public function addNotice($type, $message, $options = [])
    {
        if (isset($this->N_TYPES[$type])) {
            $class = $this->N_TYPES[$type];
        } else {
            $class = $type;
        }
        if (isset($_SESSION['notifications']) && is_array($_SESSION['notifications'])) {
            $notifications = $_SESSION['notifications'];
        } else {
            $notifications = [];
        }

        $n = array_merge($options, ['class' => $class, 'ts' => time(), 'text' => $message]);
        if ($type != "static") {
            $notifications[] = $n;
        } else {
            array_unshift($notifications, $n);
        }
        $_SESSION['notifications'] = $notifications;
    }

    public function addSuccessNotice($message, $options = [])
    {
        $this->addNotice("success", $message, $options);
    }

    public function addWarningNotice($message, $options = [])
    {
        $this->addNotice("warning", $message, $options);
    }

    public function addErrorNotice($message, $options = [])
    {
        $this->addNotice("error", $message, $options);
    }

    protected function getNotification($n)
    {
        $tag = (isset($n['divtag']) && $n['divtag']) ? 'div' : 'p';
        $ts  = '';
        if (!isset($n['with_ts']) || ($n['with_ts'] == true)) {
            $ts = dt::str(__('[%H:%M:%S]'), $n['ts'], $this->core->auth->getInfo('user_tz')) . ' ';
        }
        $res = '<' . $tag . ' class="' . $n['class'] . '" role="alert">' . $ts . $n['text'] . '</' . $tag . '>';
        return $res;
    }

    /* Direct messages, usually immediately displayed */

    public function message($msg, $timestamp = true, $div = false, $echo = true, $class = 'message')
    {
        $res = '';
        if ($msg != '') {
            $res = ($div ? '<div class="' . $class . '">' : '') . '<p' . ($div ? '' : ' class="' . $class . '"') . '>' .
                ($timestamp ? dt::str(__('[%H:%M:%S]'), null, $this->core->auth->getInfo('user_tz')) . ' ' : '') . $msg .
                '</p>' . ($div ? '</div>' : '');
            if ($echo) {
                echo $res;
            }
        }
        return $res;
    }

    public function success($msg, $timestamp = true, $div = false, $echo = true)
    {
        return $this->message($msg, $timestamp, $div, $echo, "success");
    }

    public function warning($msg, $timestamp = true, $div = false, $echo = true)
    {
        return $this->message($msg, $timestamp, $div, $echo, "warning-msg");
    }
}
