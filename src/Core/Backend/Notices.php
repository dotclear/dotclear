<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Backend notices handling facilities
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcCore;
use Dotclear\App;
use Dotclear\Helper\Date;

class Notices
{
    /* Constants */
    public const NOTICE_SUCCESS = 'success';
    public const NOTICE_WARNING = 'warning';
    public const NOTICE_ERROR   = 'error';
    public const NOTICE_MESSAGE = 'message';
    public const NOTICE_STATIC  = 'static';

    /**
     * List of supported types
     */
    private static array $notice_types = [
        // id → CSS class
        self::NOTICE_SUCCESS => 'success',
        self::NOTICE_WARNING => 'warning-msg',
        self::NOTICE_ERROR   => 'error',
        self::NOTICE_MESSAGE => 'message',
        self::NOTICE_STATIC  => 'static-msg',
    ];

    /**
     * Error has been displayed?
     */
    private static bool $error_displayed = false;

    /**
     * Gets the HTML code of notices.
     *
     * @return     string  The notices.
     */
    public static function getNotices(): string
    {
        $res = '';

        // return error messages if any
        if (App::error()->flag() && !self::$error_displayed) {
            //todo remove dcCore from method
            # --BEHAVIOR-- adminPageNotificationError -- dcCore, dcError
            $notice_error = App::behavior()->callBehavior('adminPageNotificationError', dcCore::app(), App::error());

            if (isset($notice_error) && !empty($notice_error)) {
                $res .= $notice_error;
            } else {
                $res .= '<div role="alert"><p><strong>' . (App::error()->count() > 1 ? __('Errors:') : __('Error:')) . '</strong></p>' .
                    App::error()->toHTML() .
                    '</div>';
            }
            self::$error_displayed = true;
        } else {
            self::$error_displayed = false;
        }

        // return notices if any

        // Should retrieve static notices first, then others
        $step = 2;
        do {
            if ($step == 2) {
                // Static notifications
                $params = [
                    'notice_type' => self::NOTICE_STATIC,
                ];
            } else {
                // Normal notifications
                $params = [
                    'sql' => "AND notice_type != '" . self::NOTICE_STATIC . "'",
                ];
            }
            if (App::notice()->getNotices($params, true)->f(0)) {
                $lines = App::notice()->getNotices($params);
                while ($lines->fetch()) {
                    if (isset(self::$notice_types[$lines->notice_type])) {
                        $class = self::$notice_types[$lines->notice_type];
                    } else {
                        $class = $lines->notice_type;
                    }
                    $notification = [
                        'type'   => $lines->notice_type,
                        'class'  => $class,
                        'ts'     => $lines->notice_ts,
                        'text'   => $lines->notice_msg,
                        'format' => $lines->notice_format,
                    ];
                    if ($lines->notice_options !== null) {
                        $notification = array_merge($notification, @json_decode($lines->notice_options, true, 512, JSON_THROW_ON_ERROR));
                    }
                    // todo remove dcCore from method
                    # --BEHAVIOR-- adminPageNotification -- dcCore, array<string,string>
                    $notice = App::behavior()->callBehavior('adminPageNotification', dcCore::app(), $notification);

                    $res .= (isset($notice) && !empty($notice) ? $notice : self::getNotification($notification));
                }
            }
        } while (--$step);

        // Delete returned notices
        App::notice()->delSessionNotices();

        return $res;
    }

    /**
     * Adds a notice.
     *
     * @param      string  $type     The type
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addNotice(string $type, string $message, array $options = [])
    {
        $cur = App::notice()->openCursor();

        $now = function () {
            Date::setTZ(App::auth()->getInfo('user_tz') ?? 'UTC');    // Set user TZ
            $dt = date('Y-m-d H:i:s');
            Date::setTZ('UTC');                                               // Back to default TZ

            return $dt;
        };

        $cur->notice_type    = $type;
        $cur->notice_ts      = isset($options['ts']) && $options['ts'] ? $options['ts'] : $now();
        $cur->notice_msg     = $message;
        $cur->notice_options = json_encode($options, JSON_THROW_ON_ERROR);

        if (isset($options['divtag']) && $options['divtag']) {
            $cur->notice_format = 'html';
        }
        if (isset($options['format']) && $options['format']) {
            $cur->notice_format = $options['format'];
        }

        App::notice()->addNotice($cur);
    }

    /**
     * Adds a message (informational) notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addMessageNotice(string $message, array $options = [])
    {
        self::addNotice(self::NOTICE_MESSAGE, $message, $options);
    }

    /**
     * Adds a success notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addSuccessNotice(string $message, array $options = [])
    {
        self::addNotice(self::NOTICE_SUCCESS, $message, $options);
    }

    /**
     * Adds a warning notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addWarningNotice(string $message, array $options = [])
    {
        self::addNotice(self::NOTICE_WARNING, $message, $options);
    }

    /**
     * Adds an error notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addErrorNotice(string $message, array $options = [])
    {
        self::addNotice(self::NOTICE_ERROR, $message, $options);
    }

    /**
     * Gets the notification.
     *
     * @param      array  $notice      The notification
     *
     * @return     string  The notification.
     */
    private static function getNotification(array $notice): string
    {
        $container = (isset($notice['format']) && $notice['format'] === 'html') ? 'div' : 'p';
        $timestamp = '';
        if (!isset($notice['with_ts']) || ($notice['with_ts'])) {
            $timestamp = '<span class="notice-ts">' .
                '<time datetime="' . Date::iso8601(strtotime($notice['ts']), App::auth()->getInfo('user_tz')) . '">' .
                Date::dt2str(__('%H:%M:%S'), $notice['ts'], App::auth()->getInfo('user_tz')) .
                '</time>' .
                '</span> ';
        }

        return
            '<' . $container . ' class="' . $notice['class'] . '" role="alert">' .
            $timestamp . $notice['text'] .
            '</' . $container . '>';
    }

    // Direct messages

    /**
     * Direct messages, usually immediately displayed
     *
     * @param      string       $msg        The message
     * @param      bool         $timestamp  With the timestamp
     * @param      bool         $div        Inside a div (else in a p)
     * @param      bool         $echo       Display the message?
     * @param      null|string  $class      The class of block (div/p)
     *
     * @return     string
     */
    public static function message(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true, ?string $class = null): string
    {
        $class ??= self::$notice_types[self::NOTICE_MESSAGE];
        $res = '';
        if ($msg != '') {
            $ts = '';
            if ($timestamp) {
                $ts = '<span class="notice-ts">' .
                    '<time datetime="' . Date::iso8601(time(), App::auth()->getInfo('user_tz')) . '">' .
                    Date::str(__('%H:%M:%S'), null, App::auth()->getInfo('user_tz')) .
                    '</time>' .
                    '</span> ';
            }
            $res = ($div ? '<div class="' . $class . '">' : '') . '<p' . ($div ? '' : ' class="' . $class . '"') . '>' .
                $ts . $msg .
                '</p>' . ($div ? '</div>' : '');
            if ($echo) {
                echo $res;
            }
        }

        return $res;
    }

    /**
     * Display a success message
     *
     * @param      string  $msg        The message
     * @param      bool    $timestamp  With the timestamp
     * @param      bool    $div        Inside a div (else in a p)
     * @param      bool    $echo       Display the message?
     *
     * @return     string
     */
    public static function success(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true): string
    {
        return self::message($msg, $timestamp, $div, $echo, self::$notice_types[self::NOTICE_SUCCESS]);
    }

    /**
     * Display a warning message
     *
     * @param      string  $msg        The message
     * @param      bool    $timestamp  With the timestamp
     * @param      bool    $div        Inside a div (else in a p)
     * @param      bool    $echo       Display the message?
     *
     * @return     string
     */
    public static function warning(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true): string
    {
        return self::message($msg, $timestamp, $div, $echo, self::$notice_types[self::NOTICE_WARNING]);
    }

    /**
     * Display an error message
     *
     * @param      string  $msg        The message
     * @param      bool    $timestamp  With the timestamp
     * @param      bool    $div        Inside a div (else in a p)
     * @param      bool    $echo       Display the message?
     *
     * @return     string
     */
    public static function error(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true): string
    {
        return self::message($msg, $timestamp, $div, $echo, self::$notice_types[self::NOTICE_ERROR]);
    }
}
