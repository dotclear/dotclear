<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcCore;
use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;

/**
 * Backend notices handling facilities
 */
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
     *
     * @var array<string, string>
     */
    protected static array $notice_types = [
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
    protected static bool $error_displayed = false;

    /**
     * Gets the HTML code of notices.
     *
     * @todo    Remove old dcCore from Notices::getNotices behaviors calls parameters
     *
     * @return  string  The notices.
     */
    public static function getNotices(): string
    {
        $res = '';

        // return error messages if any
        if (App::error()->flag() && !self::$error_displayed) {
            # --BEHAVIOR-- adminPageNotificationError -- dcCore, Error
            $notice_error = App::behavior()->callBehavior('adminPageNotificationError', dcCore::app(), App::error());

            if ($notice_error !== '') {
                $res .= $notice_error;
            } else {
                $errors = [];
                foreach (App::error()->dump() as $msg) {
                    $errors[] = (new Text(null, self::error($msg, true, false, false)));
                }
                $res .= (new Div())
                    ->extra('role="alert"')
                    ->items([
                        (new Para())
                            ->items([
                                (new Text('strong', App::error()->count() > 1 ? __('Errors:') : __('Error:'))),
                            ]),
                        ...$errors,
                    ])
                ->render();
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

                    # --BEHAVIOR-- adminPageNotification -- dcCore, array<string,string>
                    $notice = App::behavior()->callBehavior('adminPageNotification', dcCore::app(), $notification);

                    $res .= ($notice === '' ? $notice : self::getNotification($notification));
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
     * @param      string                   $type     The type
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addNotice(string $type, string $message, array $options = []): void
    {
        $cur = App::notice()->openNoticeCursor();

        $now = function (): string {
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
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addMessageNotice(string $message, array $options = []): void
    {
        self::addNotice(self::NOTICE_MESSAGE, $message, $options);
    }

    /**
     * Adds a success notice.
     *
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addSuccessNotice(string $message, array $options = []): void
    {
        self::addNotice(self::NOTICE_SUCCESS, $message, $options);
    }

    /**
     * Adds a warning notice.
     *
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addWarningNotice(string $message, array $options = []): void
    {
        self::addNotice(self::NOTICE_WARNING, $message, $options);
    }

    /**
     * Adds an error notice.
     *
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addErrorNotice(string $message, array $options = []): void
    {
        self::addNotice(self::NOTICE_ERROR, $message, $options);
    }

    /**
     * Gets the notification.
     *
     * @param      array<string, mixed>  $notice      The notification
     *
     * @return     string  The notification.
     */
    protected static function getNotification(array $notice): string
    {
        if (isset($notice['format']) && $notice['format'] === 'html') {
            $container = (new Div());
        } else {
            $container = (new Para());
        }
        $container
            ->class($notice['class'])
            ->extra('role="alert"');

        if (!isset($notice['with_ts']) || ($notice['with_ts'])) {
            $timestamp = (new Div(null, 'span'))
                ->class('notice-ts')
                ->items([
                    (new Text('time', Date::dt2str(__('%H:%M:%S'), $notice['ts'], App::auth()->getInfo('user_tz'))))
                        ->extra('datetime="' . Date::iso8601((int) strtotime((string) $notice['ts']), App::auth()->getInfo('user_tz')) . '"'),
                ]);
        } else {
            $timestamp = (new None());
        }

        return $container
            ->items([
                $timestamp,
                (new Text(null, $notice['text'])),
            ])
        ->render();
    }

    // Direct messages

    /**
     * Direct messages, usually immediately displayed
     *
     * @param      string       $msg        The message
     * @param      bool         $timestamp  With the timestamp
     * @param      bool         $div        Inside a div?
     * @param      bool         $echo       Display the message?
     * @param      null|string  $class      The class of block (div/p)
     */
    public static function message(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true, ?string $class = null): string
    {
        $class ??= self::$notice_types[self::NOTICE_MESSAGE];
        $res = '';
        if ($msg !== '') {
            $ts = (new None());
            if ($timestamp) {
                $timestamp = (new Div(null, 'span'))
                    ->class('notice-ts')
                    ->items([
                        (new Text('time', Date::str(__('%H:%M:%S'), null, App::auth()->getInfo('user_tz'))))
                            ->extra('datetime="' . Date::iso8601(time(), App::auth()->getInfo('user_tz')) . '"'),
                    ]);
            }
            $container = $div ?
                (new Div())
                    ->class($class)
                    ->items([
                        (new Para())
                            ->items([
                                $ts,
                                (new Text(null, $msg)),
                            ]),
                    ]) :
                (new Set())
                    ->items([
                        (new Para())
                            ->class($class)
                            ->items([
                                $ts,
                                (new Text(null, $msg)),
                            ]),
                    ]);

            $res = $container->render();
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
     */
    public static function error(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true): string
    {
        return self::message($msg, $timestamp, $div, $echo, self::$notice_types[self::NOTICE_ERROR]);
    }
}
