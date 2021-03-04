<?php declare(strict_types=1);

namespace Hi\Http\Middleware;

use Hi\Http\Context;

class HttpMidelware extends AbstractMiddleware
{
    public function handle(Context $ctx, callable $next): bool
    {
        return true;
    }
}
