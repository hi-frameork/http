<?php

declare(strict_types=1);

namespace Hi\Http\Runtime\Swoole;

use Hi\Http\Runtime\Bridge;
use RuntimeException;
use Swoole\Http\Server as HttpServer;

class Server extends Bridge
{
    /**
     * @var HttpServer
     */
    protected $swoole;

    /**
     * Server Construct
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->eventHandler = new EventHandler();
    }

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        if ($this->isRunning()) {
            throw new RuntimeException(
                "操作失败，{$this->config->get('host')}:{$this->config->get('port')} 已被占用"
            );
        }

        $this->swoole = $this->create();

        $this->bindEventHanle();
        $this->bindSetting();

        $this->swoole->start();
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
            if (!is_callable($callback)) {
                continue;
            }

            $this->swoole->on(lcfirst(substr($method, 2)), $callback);
        }
    }

    /**
     * 处理服务设置
     */
    public function bindSetting(): void
    {
        $default = [
            'pid_file'          => $this->config->get('pid_file'),
            'log_file'          => $this->config->get('log_file'),
            'open_cpu_affinity' => true,
            'hook_flags'        => SWOOLE_HOOK_ALL,
        ];

        $this->swoole->set(array_merge($default, $this->config->get('swoole')));
    }

    /**
     * 创建并返回 Swoole Http Server 实例
     */
    protected function create(): HttpServer
    {
        return new HttpServer(
            $this->config->get('host'),
            $this->config->get('port')
        );
    }
}
