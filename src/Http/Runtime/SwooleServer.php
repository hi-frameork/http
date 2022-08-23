<?php

namespace Hi\Http\Runtime;

use Hi\Server\Server;
use Swoole\Http\Server as HttpServer;
use Hi\Http\Exception;

class SwooleServer extends Server
{
    /**
     * @var \Swoole\Server
     */
    protected $server;

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        if ($this->isRunning()) {
            throw new Exception(
                "操作失败，{$this->config->get('host')}:{$this->config->get('port')} 已被占用"
            );
        }

        $this->server = $this->createServer();
        $this->bindEventHanle();
        $this->bindSetting();
        $this->server->start();
    }

    /**
     * 绑定 Swoole 事件 handle
     */
    protected function bindEventHanle(): void
    {
        $class = new SwooleEventHandler;
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            if (substr($method, 0, 2) === 'on' && is_callable([$class, $method])) {
                /** @var callable $callback */
                $callback = [$class, $method];
                $this->server->on(lcfirst(substr($method, 2)), $callback);
            }
        }
    }

    /**
     * 处理服务设置
     */
    public function bindSetting(): void
    {
        $this->server->set(array_merge(
            [
                'pid_file' => $this->config->get('pid_file'),
                'log_file' => $this->config->get('log_file'),
                'open_cpu_affinity' => true,
            ],
            $this->config->get('swoole'))
        );
    }

    /**
     * 创建并返回 Swoole Http Server 实例
     */
    protected function createServer(): HttpServer
    {
        return new HttpServer($this->config->getHost(), $this->config->getPort());
    }
}
