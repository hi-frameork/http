<?php declare(strict_types=1);

namespace Hi\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * 表示服务器返回的响应消息。
 *
 * 根据 HTTP 规范，此接口包含以下各项的属性：
 *
 * - 协议版本
 * - 状态码和原因短语
 * - 报头
 * - 消息体
 * 
 * HTTP 响应是被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套机制，在内部保
 * 持好原有的内容，然后把修改状态后的，新的 HTTP 响应实例返回。
 */
final class Response extends AbstractMessage implements ResponseInterface
{
    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be empty. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or
     * those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Response constructor.
     *
     * @param string $body
     * @param int    $code
     * @param array  $headers
     */
    public function __construct($body = 'php://memory', int $code = 200, array $headers = [])
    {
        $this->processCode($code);

        $this->headers = $this->processHeaders($headers);
        $this->body    = $this->processBody($body);
    }
    
    /**
     * 获取响应状态码。
     *
     * 状态码是一个三位整数，用于理解请求。
     *
     * @return int 状态码。
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 返回具有指定状态码和原因短语（可选）的实例。
     *
     * 如果未指定原因短语，实现代码 **可能** 选择 RFC7231 或 IANA 为状态码推荐的原因短语。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息实例，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @param int $code 三位整数的状态码。
     * @param string $reasonPhrase 为状态码提供的原因短语；如果未提供，实现代码可以使用 HTTP 规范建议的默认代码。
     * @return self
     * @throws \InvalidArgumentException 如果传入无效的状态码，则抛出。
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $newInstance = clone $this;
        $newInstance->processCode($code, $reasonPhrase);
        return $newInstance;
    }

    /**
     * 获取与响应状态码关联的响应原因短语。
     *
     * 因为原因短语不是响应状态行中的必需元素，所以原因短语 **可能** 是空。
     * 实现代码可以选择返回响应的状态代码的默认 RFC 7231 推荐原因短语（或 IANA HTTP 状态码注册表中列出的原因短语）。
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Set a valid status code and phrase
     *
     * @param mixed $code
     * @param mixed $phrase
     */
    protected function processCode($code, $phrase = ''): void
    {
        $phrases = $this->getPhrases();

        $this->checkCodeType($code);

        $code = (int) $code;
        $this->checkCodeValue($code);

        if (! is_string($phrase)) {
            throw new InvalidArgumentException('Invalid response reason');
        }

        if ('' === $phrase && isset($phrases[$code])) {
            $phrase = $phrases[$code];
        }

        $this->statusCode   = $code;
        $this->reasonPhrase = $phrase;
    }

    /**
     * Returns the list of status codes available
     */
    protected function getPhrases(): array
    {
        return [
            100 => 'Continue',                                         // Information - RFC 7231, 6.2.1
            101 => 'Switching Protocols',                              // Information - RFC 7231, 6.2.2
            102 => 'Processing',                                       // Information - RFC 2518, 10.1
            103 => 'Early Hints',
            200 => 'OK',                                               // Success - RFC 7231, 6.3.1
            201 => 'Created',                                          // Success - RFC 7231, 6.3.2
            202 => 'Accepted',                                         // Success - RFC 7231, 6.3.3
            203 => 'Non-Authoritative Information',                    // Success - RFC 7231, 6.3.4
            204 => 'No Content',                                       // Success - RFC 7231, 6.3.5
            205 => 'Reset Content',                                    // Success - RFC 7231, 6.3.6
            206 => 'Partial Content',                                  // Success - RFC 7233, 4.1
            207 => 'Multi-status',                                     // Success - RFC 4918, 11.1
            208 => 'Already Reported',                                 // Success - RFC 5842, 7.1
            218 => 'This is fine',                                     // Unofficial - Apache Web Server
            419 => 'Page Expired',                                     // Unofficial - Laravel Framework
            226 => 'IM Used',                                          // Success - RFC 3229, 10.4.1
            300 => 'Multiple Choices',                                 // Redirection - RFC 7231, 6.4.1
            301 => 'Moved Permanently',                                // Redirection - RFC 7231, 6.4.2
            302 => 'Found',                                            // Redirection - RFC 7231, 6.4.3
            303 => 'See Other',                                        // Redirection - RFC 7231, 6.4.4
            304 => 'Not Modified',                                     // Redirection - RFC 7232, 4.1
            305 => 'Use Proxy',                                        // Redirection - RFC 7231, 6.4.5
            306 => 'Switch Proxy',                                     // Redirection - RFC 7231, 6.4.6 (Deprecated)
            307 => 'Temporary Redirect',                               // Redirection - RFC 7231, 6.4.7
            308 => 'Permanent Redirect',                               // Redirection - RFC 7538, 3
            400 => 'Bad Request',                                      // Client Error - RFC 7231, 6.5.1
            401 => 'Unauthorized',                                     // Client Error - RFC 7235, 3.1
            402 => 'Payment Required',                                 // Client Error - RFC 7231, 6.5.2
            403 => 'Forbidden',                                        // Client Error - RFC 7231, 6.5.3
            404 => 'Not Found',                                        // Client Error - RFC 7231, 6.5.4
            405 => 'Method Not Allowed',                               // Client Error - RFC 7231, 6.5.5
            406 => 'Not Acceptable',                                   // Client Error - RFC 7231, 6.5.6
            407 => 'Proxy Authentication Required',                    // Client Error - RFC 7235, 3.2
            408 => 'Request Time-out',                                 // Client Error - RFC 7231, 6.5.7
            409 => 'Conflict',                                         // Client Error - RFC 7231, 6.5.8
            410 => 'Gone',                                             // Client Error - RFC 7231, 6.5.9
            411 => 'Length Required',                                  // Client Error - RFC 7231, 6.5.10
            412 => 'Precondition Failed',                              // Client Error - RFC 7232, 4.2
            413 => 'Request Entity Too Large',                         // Client Error - RFC 7231, 6.5.11
            414 => 'Request-URI Too Large',                            // Client Error - RFC 7231, 6.5.12
            415 => 'Unsupported Media Type',                           // Client Error - RFC 7231, 6.5.13
            416 => 'Requested range not satisfiable',                  // Client Error - RFC 7233, 4.4
            417 => 'Expectation Failed',                               // Client Error - RFC 7231, 6.5.14
            418 => 'I am a teapot',                                     // Client Error - RFC 7168, 2.3.3
            420 => 'Method Failure',                                   // Unofficial - Spring Framework
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',                             // Client Error - RFC 4918, 11.2
            423 => 'Locked',                                           // Client Error - RFC 4918, 11.3
            424 => 'Failed Dependency',                                // Client Error - RFC 4918, 11.4
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',                                 // Client Error - RFC 7231, 6.5.15
            428 => 'Precondition Required',                            // Client Error - RFC 6585, 3
            429 => 'Too Many Requests',                                // Client Error - RFC 6585, 4
            431 => 'Request Header Fields Too Large',                  // Client Error - RFC 6585, 5
            440 => 'Login Time-out',                                   // Unofficial - IIS
            444 => 'No Response',                                      // Unofficial - nginx
            449 => 'Retry With',                                       // Unofficial - IIS
            494 => 'Request header too large',                         // Unofficial - nginx
            495 => 'SSL Certificate Error',                            // Unofficial - nginx
            496 => 'SSL Certificate Required',                         // Unofficial - nginx
            497 => 'HTTP Request Sent to HTTPS Port',                  // Unofficial - nginx
            499 => 'Client Closed Request',                            // Unofficial - nginx
            450 => 'Blocked by Windows Parental Controls (Microsoft)', // Unofficial - nginx
            451 => 'Unavailable For Legal Reasons',                    // Client Error - RFC 7725, 3
            498 => 'Invalid Token (Esri)',                             // Unofficial - ESRI
            500 => 'Internal Server Error',                            // Server Error - RFC 7231, 6.6.1
            501 => 'Not Implemented',                                  // Server Error - RFC 7231, 6.6.2
            502 => 'Bad Gateway',                                      // Server Error - RFC 7231, 6.6.3
            503 => 'Service Unavailable',                              // Server Error - RFC 7231, 6.6.4
            504 => 'Gateway Time-out',                                 // Server Error - RFC 7231, 6.6.5
            505 => 'HTTP Version not supported',                       // Server Error - RFC 7231, 6.6.6
            506 => 'Variant Also Negotiates',                          // Server Error - RFC 2295, 8.1
            507 => 'Insufficient Storage',                             // Server Error - RFC 4918, 11.5
            508 => 'Loop Detected',                                    // Server Error - RFC 5842, 7.2
            509 => 'Bandwidth Limit Exceeded',                         // Unofficial - Apache/cPanel
            510 => 'Not Extended',                                     // Server Error - RFC 2774, 7
            511 => 'Network Authentication Required',                  // Server Error - RFC 6585, 6
            520 => 'Unknown Error',                                    // Unofficial - Cloudflare
            521 => 'Web Server Is Down',                               // Unofficial - Cloudflare
            522 => 'Connection Timed Out',                             // Unofficial - Cloudflare
            523 => 'Origin Is Unreachable',                            // Unofficial - Cloudflare
            524 => 'A Timeout Occurred',                               // Unofficial - Cloudflare
            525 => 'SSL Handshake Failed',                             // Unofficial - Cloudflare
            526 => 'Invalid SSL Certificate',                          // Unofficial - Cloudflare
            527 => 'Railgun Error',                                    // Unofficial - Cloudflare
            530 => 'Origin DNS Error',                                 // Unofficial - Cloudflare
            598 => 'Network read timeout error',                       // Unofficial
            599 => 'Network Connect Timeout Error'                     // Server Error - RFC 6585, 6
        ];
    }

    /**
     * Checks if a code is integer or string
     *
     * @param mixed $code
     */
    private function checkCodeType($code): void
    {
        if (! is_int($code) && ! is_string($code)) {
            throw new InvalidArgumentException(
                'status code 无效; 其值必须为 int 或 string 类型'
            );
        }
    }

    /**
     * Checks if a code is integer or string
     *
     * @param int $code
     */
    private function checkCodeValue(int $code): void
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException(
                'status code 无效 ' . $code . ', (值范围必须在 100-599 之间)'
            );
        }
    }
}
