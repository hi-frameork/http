<?php declare(strict_types=1);

namespace Hi\Http\Middleware;

use Closure;
use Hi\Http\Context;

interface MiddlewareInterface
{
    /**
     * 中间件业务处理器
     */
    public function handle(Context $ctx, Closure $next);
}
