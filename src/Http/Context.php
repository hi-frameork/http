<?php

declare(strict_types=1);

namespace Hi\Http;

use Exception;
use Hi\Http\Message\Response;
use Hi\Http\Router\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Context
{
    /**
     * @var Route
     */
    public $route;

    /**
     * @var ServerRequestInterface
     */
    public $request;

    /**
     * @var ResponseInterface
     */
    public $response;

    // /**
    //  * @var Input
    //  */
    // public $input;

    /**
     * 上下文中间状态，用于在各个组件数据共享
     *
     * @var array
     */
    public $state = [];

    /**
     * Context construct.
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response = null)
    {
        if (!$response) {
            $response = new Response();
        }

        $this->request  = $request;
        $this->response = $response;

        // $this->input = $this->processInput();
    }

    /**
     * 断言并抛出异常（如果条件为 false）
     * 此方法用于在条件不满足时快速抛出异常，有助于简化代码
     *
     * @param mixed  $condition 条件
     * @param int    $status    HTTP statusCode
     * @param string $message   错误信息
     */
    public function assert($condition, $status, $message = ''): void
    {
        if (!$condition) {
            throw new Exception($message, $status);
        }
    }

    /**
     * 为输入参数提供快捷访问实例，在业务处理时，
     * 直接使用 $ctx->input 属性即可获取 queryString 参数与 form data 等参数
     *
     * 注：如果 query 参数与 form data 参数存在同名参数时
     *     form data 参数将会覆盖 query 参数，若想获取正确 query 参数
     *     请使用 $ctx->request->queryParams() 方法手动提取
     */
    // private function processInput(): Input
    // {
    //     $dataPayload = [];

    //     $queryParams = $this->request->getQueryParams();
    //     if ($queryParams) {
    //         $dataPayload += $queryParams;
    //     }

    //     // 此处 parsedBody 可能是 array, object, null 中的一种
    //     // 需要手动处理为 array 格式
    //     $parsedBody = $this->request->getParsedBody();
    //     if (is_object($parsedBody)) {
    //         $parsedBody = objectToArray($parsedBody);
    //     }

    //     if ($parsedBody) {
    //         $dataPayload += $parsedBody;
    //     }

    //     return new Input($dataPayload);
    // }
}
