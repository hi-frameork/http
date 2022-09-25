<?php

declare(strict_types=1);

namespace Hi\Http\Middleware;

use Closure;
use Hi\Http\Context;

class DispatchMiddleware implements MiddlewareInterface
{
    public function handle(Context $ctx, Closure $next)
    {
        $ctx->response->getBody()->write($ctx->route->call($ctx));

        return $next($ctx);
    }
}
