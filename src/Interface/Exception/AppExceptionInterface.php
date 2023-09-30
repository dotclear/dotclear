<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Exception;

/**
 * @brief   Exception interface.
 *
 * @since   2.28
 */
interface AppExceptionInterface
{
    /**
     * The exception code.
     *
     * A 3 digits code starting from 4xx or 5xx
     *
     * @var 	int 	CODE
     */
    public const CODE = 503;

    /**
     * The exception label.
     *
     * @var 	string 	LABEL
     */
    public const LABEL = 'Site temporarily unavailable';
}
