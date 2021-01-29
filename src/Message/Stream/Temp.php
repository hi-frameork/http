<?php

declare(strict_types=1);

namespace Hi\Http\Message\Stream;

use Hi\Http\Message\Stream;

class Temp extends Stream
{
    public function __construct($mode = 'rb')
    {
        parent::__construct('php://temp', $mode);
    }
}
