<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html;

use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @class HtmlValidator
 *
 * HTML Validator
 *
 * This class will perform an HTML validation upon W3.ORG validator.
 */
class HtmlValidator extends HttpClient
{
    /**
     * Validator host
     *
     * @var        string
     */
    protected $host = 'validator.w3.org';

    /**
     * Validator path
     *
     * @var        string
     */
    protected $path = '/nu/';

    /**
     * Use SSL
     *
     * @var        bool
     */
    protected $use_ssl = true;

    /**
     * User agent
     *
     * @var        string
     */
    protected $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.3a) Gecko/20021207';

    /**
     * Timeout (in seconds)
     *
     * @var        int
     */
    protected $timeout = 2;

    /**
     * Validation errors list (HTML)
     *
     * @var        string
     */
    protected $html_errors = '';

    /**
     * Constructor, no parameters.
     */
    public function __construct()
    {
        parent::__construct($this->host, 443, $this->timeout);
    }

    /**
     * HTML Document
     *
     * Returns an HTML document from a <var>$fragment</var>.
     *
     * @param string    $fragment            HTML content
     */
    public function getDocument(string $fragment): string
    {
        return
            '<!DOCTYPE html>' . "\n" .
            '<html>' . "\n" .
            '<head>' . "\n" .
            '<title>validation</title>' . "\n" .
            '</head>' . "\n" .
            '<body>' . "\n" .
            $fragment . "\n" .
            '</body>' . "\n" .
            '</html>';
    }

    /**
     * HTML validation
     *
     * Performs HTML validation of <var>$html</var>.
     *
     * @param string    $html               HTML document
     * @param string    $charset            Document charset
     */
    public function perform(string $html, string $charset = 'UTF-8'): bool
    {
        $this->setMoreHeader('Content-Type: text/html; charset=' . strtolower($charset));
        $this->post($this->path, $html);

        if ($this->getStatus() !== 200) {
            throw new Exception('Status code line invalid.');
        }

        $result = $this->getContent();

        if (str_contains($result, '<p class="success">The document validates according to the specified schema(s).</p>')) {
            return true;
        }
        if (preg_match('#(<ol>.*</ol>)<p class="failure">There were errors.</p>#msU', $result, $matches)) {
            $this->html_errors = strip_tags($matches[1], '<ol><li><p><code><strong>');
        }

        return false;
    }

    /**
     * Validation Errors
     *
     * @return string    HTML validation errors list
     */
    public function getErrors(): string
    {
        return $this->html_errors;
    }

    /**
     * Static HTML validation
     *
     * Static validation method of an HTML fragment. Returns an array with the
     * following parameters:
     *
     * - valid (boolean)
     * - errors (string)
     *
     * @param string    $fragment           HTML content
     * @param string    $charset            Document charset
     *
     * @return array<string, mixed>
     */
    public static function validate(string $fragment, string $charset = 'UTF-8'): array
    {
        $instance = new self();
        $fragment = $instance->getDocument($fragment);

        if ($instance->perform($fragment, $charset)) {
            return [
                'valid'  => true,
                'errors' => null,
            ];
        }

        return [
            'valid'  => false,
            'errors' => $instance->getErrors(),
        ];
    }
}
