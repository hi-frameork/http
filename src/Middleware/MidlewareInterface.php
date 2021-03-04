<?php declare(strict_types=1);

namespace Hi\Http\Middleware;

use Hi\Http\Context;

interface MiddlewareInterface
{
    public function __invoke(Context $ctx, callable $next);

    /**
     * 中间件业务处理器
     */
    public function handle(Context $ctx callable $next): bool;
}
