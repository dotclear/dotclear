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
                ->extra('role="alert"')
                ->items([
                    (new Para())
                        ->items([
                            (new Text('strong', App::error()->count() > 1 ? __('Errors:') : __('Error:'))),
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

                    $res .= self::getNotification($notification);
                }
            }
        } while (--$step);

        // Delete returned notices
        App::notice()->delSessionNotices();

        return $res;
    }
}
