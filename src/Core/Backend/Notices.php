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
use Dotclear\Helper\Html\Form\Btn;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Timestamp;

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
     * @var array<string, string>   $notice_types
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
            $notice_error = App::behavior()->callBehavior(
                'adminPageNotificationError',
                App::config()->modern() ? null : dcCore::app(),
                App::error()
            );

            if ($notice_error !== '') {
                $res .= $notice_error;
            } else {
                $errors = [];
                foreach (App::error()->dump() as $msg) {
                    $errors[] = (new Text(null, self::error($msg, true, false, false)));
                }
                $res .= (new Div())
                    ->role('alert')
                    ->items([
                        (new Para())
                            ->items([
                                (new Strong(App::error()->count() > 1 ? __('Errors:') : __('Error:'))),
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
            if ($step === 2) {
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

            if (App::notice()->getNotices($params, true)->cardinal() > 0) {
                $lines = App::notice()->getNotices($params);

                while ($lines->fetch()) {
                    $notice_type    = $lines->strField('notice_type');
                    $notice_ts      = $lines->strField('notice_ts');
                    $notice_msg     = $lines->strField('notice_msg');
                    $notice_format  = $lines->strField('notice_format');
                    $notice_options = $lines->strField('notice_options');

                    $class = self::$notice_types[$notice_type] ?? $notice_type;

                    $ret = '';

                    /**
                     * @var array<string, mixed> $notification
                     */
                    $notification = [
                        'type'   => $notice_type,
                        'class'  => $class,
                        'ts'     => $notice_ts,
                        'text'   => $notice_msg,
                        'format' => $notice_format,
                    ];

                    /**
                     * @var array<string, mixed> $options
                     */
                    $options = [];
                    $with_ts = true;
                    $div     = false;
                    if ($notice_options !== '') {
                        $values = @json_decode($notice_options, true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($values)) {
                            foreach ($values as $key => $value) {
                                if (is_string($key)) {
                                    $options[$key] = $value;
                                }

                                if ($key === 'with_ts' && is_bool($value)) {
                                    $with_ts = $value;
                                }

                                if ($key === 'div_tag' && is_bool($value) && $value) {
                                    $notice_format = 'html';
                                    $div           = true;
                                }
                            }
                            // Legacy way
                            $notification = array_merge($notification, $options);
                        }
                    }

                    # --BEHAVIOR-- adminPageNotification -- dcCore, array<string,string>
                    $ret .= App::behavior()->callBehavior(
                        'adminPageNotification',
                        App::config()->modern() ? null : dcCore::app(),
                        $notification
                    );

                    $notice = new Notice(
                        $notice_type,
                        $notice_ts,
                        $notice_msg,
                        $notice_format,
                        $class,
                        $with_ts,
                        $div,
                        $options
                    );

                    # --BEHAVIOR-- adminPageNotificationV2 -- Notice
                    $ret .= App::behavior()->callBehavior(
                        'adminPageNotificationV2',
                        $notice
                    );

                    // @phpstan-ignore argument.type (should use only a Notice instance in the future)
                    $res .= ($ret !== '' ? $ret : self::getNotification($notice));
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
     *
     * @deprecated since 2.39 Use Notices::addNewNotice() instead
     */
    public static function addNotice(string $type, string $message, array $options = []): void
    {
        $cur = App::notice()->openNoticeCursor();

        $now = function (): string {
            $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

            Date::setTZ($user_tz);      // Set user TZ
            $dt = date('Y-m-d H:i:s');
            Date::setTZ('UTC');         // Back to default TZ

            return $dt;
        };

        $ts = isset($options['ts']) && $options['ts'] ? $options['ts'] : $now();

        $cur->notice_type    = $type;
        $cur->notice_ts      = $ts;
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
     * Adds a notice.
     *
     * @param      Notice                   $notice   Notice instance, will be used in priority if given
     */
    public static function addNewNotice(Notice $notice): void
    {
        $cur = App::notice()->openNoticeCursor();

        $now = function (): string {
            $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

            Date::setTZ($user_tz);      // Set user TZ
            $dt = date('Y-m-d H:i:s');
            Date::setTZ('UTC');         // Back to default TZ

            return $dt;
        };

        $cur->notice_type    = $notice->getType();
        $cur->notice_ts      = $notice->getTs() !== '' ? $notice->getTs() : $now();
        $cur->notice_msg     = $notice->getMsg();
        $cur->notice_options = json_encode($notice->getOptions(), JSON_THROW_ON_ERROR);

        if ($notice->useDiv()) {
            $cur->notice_format = 'html';
        }

        if ($notice->getFormat() !== '') {
            $cur->notice_format = $notice->getFormat();
        }

        App::notice()->addNotice($cur);
    }

    /**
     * Adds a typed notice.
     *
     * @param      string                   $message  The notice type
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    protected static function addTypedNotice(string $type, string $message, array $options = []): void
    {
        self::addNewNotice(
            new Notice(
                $type,
                isset($options['ts']) && is_string($ts = $options['ts']) ? $ts : '',
                $message,
                isset($options['format'])  && is_string($format = $options['format']) ? $format : '',
                isset($options['class'])   && is_string($class = $options['class']) ? $class : '',
                isset($options['with_ts']) && is_bool($with_ts = $options['with_ts']) ? $with_ts : true,
                isset($options['divtag'])  && is_bool($divtag = $options['divtag']) && $divtag,
                $options
            )
        );
    }

    /**
     * Adds a message (informational) notice.
     *
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addMessageNotice(string $message, array $options = []): void
    {
        self::addTypedNotice(self::NOTICE_MESSAGE, $message, $options);
    }

    /**
     * Adds a success notice.
     *
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addSuccessNotice(string $message, array $options = []): void
    {
        self::addTypedNotice(self::NOTICE_SUCCESS, $message, $options);
    }

    /**
     * Adds a warning notice.
     *
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addWarningNotice(string $message, array $options = []): void
    {
        self::addTypedNotice(self::NOTICE_WARNING, $message, $options);
    }

    /**
     * Adds an error notice.
     *
     * @param      string                   $message  The message
     * @param      array<string, mixed>     $options  The options
     */
    public static function addErrorNotice(string $message, array $options = []): void
    {
        self::addTypedNotice(self::NOTICE_ERROR, $message, $options);
    }

    /**
     * Gets the notification.
     *
     * @param      array<string, mixed>|Notice  $notice      The notification
     *
     * @return     string  The notification.
     */
    protected static function getNotification(array|Notice $notice): string
    {
        if ($notice instanceof Notice) {
            $ts      = $notice->getTs();
            $text    = $notice->getMsg();
            $format  = $notice->getFormat();
            $class   = $notice->getClass();
            $with_ts = $notice->useTs();
        } else {
            $ts      = isset($notice['ts'])      && is_string($ts = $notice['ts']) ? $ts : '';
            $text    = isset($notice['text'])    && is_string($text = $notice['text']) ? $text : '';
            $format  = isset($notice['format'])  && is_string($format = $notice['format']) ? $format : 'text';
            $class   = isset($notice['class'])   && is_string($class = $notice['class']) ? $class : '';
            $with_ts = isset($notice['with_ts']) && is_bool($with_ts = $notice['with_ts']) ? $with_ts : true;
        }

        $container = $format === 'html' ? new Div() : new Para();

        $container
            ->role('alert');

        if ($class !== '') {
            $container->class($class);
        }

        if ($with_ts) {
            $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

            $timestamp = (new Span())
                ->class('notice-ts')
                ->items([
                    (new Timestamp(Date::dt2str(__('%H:%M:%S'), $ts, $user_tz)))
                        ->datetime(Date::iso8601((int) strtotime($ts), $user_tz)),
                ]);
        } else {
            $timestamp = (new None());
        }

        return $container
            ->items([
                $timestamp,
                (new Text(null, $text)),
                (new Btn(null, __('Ok')))
                    ->class('close-notice'),
            ])
        ->render();
    }

    // Direct messages
    // ---------------

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
                $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

                $timestamp = (new Span())
                    ->class('notice-ts')
                    ->items([
                        (new Timestamp(Date::str(__('%H:%M:%S'), null, $user_tz)))
                            ->datetime(Date::iso8601(time(), $user_tz)),
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
                        (new Btn(null, __('Ok')))
                            ->class('close-notice'),
                    ]) :
                (new Set())
                    ->items([
                        (new Para())
                            ->class($class)
                            ->items([
                                $ts,
                                (new Text(null, $msg)),
                                (new Btn(null, __('Ok')))
                                    ->class('close-notice'),
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
