<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Exceptions\InvalidArgumentException;
use Hi\Helpers\StatusCode;

abstract class AbstructCommon
{
    final protected function cloneInstance($element, string $property)
    {
        $newInstance = clone $this;
        $newInstance->{$property} = $element;
        return $newInstance;
    }

    final protected function checkStringParameter($element): void
    {
        if (! is_string($element)) {
            throw new InvalidArgumentException(
                '参数类型必须为 string',
                StatusCode::E_500000,
                [
                    'element' => $element,
                ]
            );
        }
    }

    final protected function processWith($element, string $property)
    {
        $this->checkStringParameter($element);
        return $this->cloneInstance($element, $property);
    }
}
