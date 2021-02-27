<?php declare(strict_types=1);

namespace Hi\Http\Exceptions;

use Hi\Helpers\Exception;
use Hi\Helpers\StatusCode;

class RuntimeException extends Exception
{
    /**
     * constructor
     * @param mixed $runtime
     */
    public function __construct(string $message = '', int $code = StatusCode::E_500000, $runtime = null)
    {
        parent::__construct($message, $code, $runtime);
    }
}
