<?php declare(strict_types=1);

namespace Hi\Http\Message;

use InvalidArgumentException;

/**
 * 消息基类
 */
abstract class AbstractCommon
{
    /**
     * 返回已设置参数的新实例
     *
     * @param mixed $element
     * @return static
     */
    final protected function cloneInstance($element, string $property)
    {
        $newInstance              = clone $this;
        $newInstance->{$property} = $element;
        return $newInstance;
    }

    /**
     * 检查元素是否为 string
     *
     * @param string $element
     */
    final protected function checkStringParameter($element): void
    {
        if (! is_string($element)) {
            throw new InvalidArgumentException('方法参数必须为 string');
        }
    }

    /**
     * 检查传入元素类型，并返回克隆对象
     *
     * @param string $element
     * @return static
     */
    final protected function processWith($element, string $property)
    {
        $this->checkStringParameter($element);
        return $this->cloneInstance($element, $property);
    }
}
