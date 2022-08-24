<?php declare(strict_types=1);

namespace Hi\Http;

use Closure;

class Pipeline
{
    /**
     * @var string
     */
    protected $method = 'handle';

    /**
     * @var mixed
     */
    protected $passable;

    /**
     * @var array
     */
    protected $stages = [];

    /**
     * 更改节点执行时将会调用的方法
     *
     * @return $this
     */
    public function via(string $method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 设置通过管道的对象
     *
     * @param mixed $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * 设置管道处理节点
     * @return $this
     */
    public function throgh(array $stages)
    {
        $this->stages = $stages;
        return $this;
    }

    /**
     * 执行管道并在最后执行回调
     *
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->stages),
            $this->carry(),
            $destination
        );

        return $pipeline($this->passable);
    }

    /**
     * 执行管道处理并返回处理结果
     *
     * @return Closure
     */
    public function thenReturn()
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    /**
     * 通过闭包将管道节点转换为单一调用回调
     *
     * @return Closure
     */
    protected function carry()
    {
        return function ($stack, $stage) {
            return function ($passable) use ($stack, $stage) {
                return call_user_func_array(
                    (is_callable($stage) ? $stage : [(new $stage), $this->method]),
                    [$passable, $stack]
                );
            };
        };
    }
}
