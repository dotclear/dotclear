<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Exception;

use Dotclear\Helper\L10n;

/**
 * @brief   Exception enumeration.
 *
 * Rules for ExceptionEnum:
 * * Exception code MUST be uniq for each exceptions
 * * Exception COULD extends another exception but MUST have its own code
 * * All 4xx exception MUST extends BadRequestException,
 * * All 5xx exception MUST extends InternalServerException
 *
 * Usage:
 * @code{php}
 * // Retreive an exception information
 * echo \Dotclear\Exception\DatabaseException::code();
 * echo \Dotclear\Exception\DatabaseException::label();
 * // Retreive label from code
 * echo \Dotclear\Exception\ExceptionEnum::tryFrom(503)?->label();
 * // List exceptions informations
 * foreach(\Dotclear\Exception\ExceptionEnum::cases() as $enum) {
 *   echo $enum->name . ' : ' . $enum->value . ' : ' . $enum->code() . ' : ' . $enum->label() . '<br />';
 * }
 * @endcode
 *
 * @return  string  The exception class name
 */
enum ExceptionEnum: string
{
    case AppException            = AppException::class;
    case BadRequestException     = BadRequestException::class;
    case BlogException           = BlogException::class;
    case ConfigException         = ConfigException::class;
    case ConflictException       = ConflictException::class;
    case ContextException        = ContextException::class;
    case DatabaseException       = DatabaseException::class;
    case NotFoundException       = NotFoundException::class;
    case PreconditionException   = PreconditionException::class;
    case ProcessException        = ProcessException::class;
    case SessionException        = SessionException::class;
    case TemplateException       = TemplateException::class;
    case UnauthorizedException   = UnauthorizedException::class;
    case InternalServerException = InternalServerException::class;

    /**
     * Get exception code.
     *
     * A 3 digits code starting from 4xx or 5xx
     *
     * @return  int     The exception code
     */
    public function code(): int
    {
        return match ($this) {
            self::AppException            => 503,
            self::BadRequestException     => 400,
            self::BlogException           => 570,
            self::ConfigException         => 551,
            self::ConflictException       => 409,
            self::ContextException        => 553,
            self::DatabaseException       => 560,
            self::NotFoundException       => 404,
            self::PreconditionException   => 412,
            self::ProcessException        => 552,
            self::SessionException        => 561,
            self::TemplateException       => 571,
            self::UnauthorizedException   => 401,
            self::InternalServerException => 500,
            //default => 503,
        };
    }

    /**
     * Get (translated) exception label.
     *
     * @return  string  The exception label
     */
    public function label(): string
    {
        // Be sure to have __() function
        L10n::bootstrap();

        return match ($this) {
            self::AppException            => __('Site temporarily unavailable'),
            self::BadRequestException     => __('Bad Request'),
            self::BlogException           => __('Blog handling error'),
            self::ConfigException         => __('Application configuration error'),
            self::ConflictException       => __('Conflict'),
            self::ContextException        => __('Application context error'),
            self::DatabaseException       => __('Database connection error'),
            self::NotFoundException       => __('Not Found'),
            self::PreconditionException   => __('Precondition Failed'),
            self::ProcessException        => __('Application process error'),
            self::SessionException        => __('Session handling error'),
            self::TemplateException       => __('Template handling error'),
            self::UnauthorizedException   => __('Unauthorized'),
            self::InternalServerException => __('Internal Server Error'),
            //default => __('Site temporarily unavailable'),
        };
    }
}
