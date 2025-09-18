<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Exception\AppException;
use Throwable;

/**
 * @brief   Exception handler interface.
 *
 * @since   2.36
 */
interface FaultInterface
{
    /**
     * Get an application exception instance.
     *
     * @param   string          $message    The exception message
     * @param   int             $code       The exception code
     * @param   null|Throwable  $previous   The previous Exception
     *
     * @return  AppException    The application exception instance
     */
    public function exception(string $message = '', int $code = 0, ?Throwable $previous = null): AppException;

    /**
     * Set exception handler debut mode.
     */
    public function setDebugMode(bool $debug_mode): void;

    /**
     * Set exception handler custom error file.
     */
    public function setErrorFile(string $error_file): void;

    /**
     * Set exception handler vendor name.
     */
    public function setVendorName(string $vendor_name): void;

    /**
     * Set Dotclear Exception handler.
     *
     * @return  bool    True if it is set from this call
     */
    public function setExceptionHandler(): bool;
}
