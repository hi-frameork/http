<?php declare(strict_types=1);

namespace Hi\Http\Middleware;

use Hi\Http\Context;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    public function __invoke(Context $ctx)
    {
        $this->handle($ctx);
    }
}
