<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Core\Backend\Notices as BackendNotices;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Text;

/**
 * @brief   Upgrade notices handling facilities.
 *
 * @since   2.29
 */
class Notices extends BackendNotices
{
    /**
     * Gets the HTML code of notices.
     *
     * @return  string  The notices.
     */
    public static function getNotices(): string
    {
        $res = '';

        // return error messages if any
        if (App::error()->flag() && !self::$error_displayed) {
            $errors = [];
            foreach (App::error()->dump() as $msg) {
                $errors[] = (new Text(null, self::message($msg, true, false, false, self::NOTICE_ERROR)));
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
                    $notice_type = $lines->strField('notice_type');
                    if ($notice_type !== '') {
                        $notice_ts      = $lines->strField('notice_ts');
                        $notice_msg     = $lines->strField('notice_msg');
                        $notice_format  = $lines->strField('notice_format');
                        $notice_options = $lines->strField('notice_options');

                        $class = self::$notice_types[$notice_type] ?? $notice_type;

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
                        if ($notice_options !== '') {
                            /**
                             * @var array<string, mixed> $options
                             */
                            $options      = @json_decode($notice_options, true, 512, JSON_THROW_ON_ERROR);
                            $notification = array_merge($notification, $options);
                        }

                        $res .= self::getNotification($notification);
                    }
                }
            }
        } while (--$step);

        // Delete returned notices
        App::notice()->delSessionNotices();

        return $res;
    }
}
