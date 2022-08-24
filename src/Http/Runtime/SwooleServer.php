<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Exception;
use Swoole\Http\Server as HttpServer;
use Hi\Http\Runtime;
use Hi\Http\Runtime\EventHandler\SwooleEventHandler;

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

        $this->eventHandler = new SwooleEventHandler;
        $this->server       = $this->createServer();
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
        foreach (get_class_methods($this->eventHandler) as $method) {
            if (substr($method, 0, 2) !== 'on') {
                continue;
            }

            /** @var callable $callback */
            $callback = [$this->eventHandler, $method];
            if (! is_callable($callback)) {
                continue;
            }

            $this->server->on(lcfirst(substr($method, 2)), $callback);
        }
    }

    /**
     * 处理服务设置
     */
    public function bindSetting(): void
    {
        $default = [
            'pid_file' => $this->config->get('pid_file'),
            'log_file' => $this->config->get('log_file'),
            'open_cpu_affinity' => true,
        ];

        $this->server->set(array_merge($default, $this->config->get('swoole')));
    }

    /**
     * 创建并返回 Swoole Http Server 实例
     */
    protected function createServer(): HttpServer
    {
        return new HttpServer($this->config->get('host'), $this->config->get('port'));
    }
}
