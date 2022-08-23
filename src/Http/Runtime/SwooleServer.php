<?php

namespace Hi\Http\Runtime;

use Hi\Http\Exception;
use Swoole\Http\Server as HttpServer;
use Hi\Http\Runtime;

class SwooleServer extends Runtime
{
    /**
     * @var \Swoole\Server
     */
    protected $server;

    /**
     * Construct
     *
     * @param array{} $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->handler = new SwooleEventHandler;
        $this->server = $this->createServer();
    }

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

        $this->bindEventHanle();
        $this->bindSetting();

        $this->server->start();
    }

    /**
     * 绑定 Swoole 事件 handle
     */
    protected function bindEventHanle(): void
    {
        $methods = get_class_methods($this->eventHandler);
        foreach ($methods as $method) {
            if (substr($method, 0, 2) === 'on' && is_callable([$this->eventHandler, $method])) {
                /** @var callable $callback */
                $callback = [$this->eventHandler, $method];
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
        return new HttpServer($this->config->get('host'), $this->config->get('port'));
    }
}
