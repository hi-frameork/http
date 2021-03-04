<?php declare(strict_types=1);

namespace Hi\Http;

use Hi\Helpers\Input;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Message\Response;

class Context
{
    /**
     */
    public $route;

    /**
     * @var ServerRequest
     */
    public $request;

    /**
     * @var Response
     */
    public $response;

    /**
     * @var Input
     */
    public $input;

    /**
     * 上下文中间状态，用于在各个组件数据共享
     *
     * @var array
     */
    public $state = [];

    /**
     * Context construct.
     */
    public function __construct(ServerRequest $request, Response $response = null)
    {
        if (! $response) {
            $response = new Response();
        }

        $this->request  = $request;
        $this->response = $response;

        $this->input = $this->processInput();
    }

    /**
     * 断言并抛出异常（如果条件为 false）
     * 此方法用于在条件不满足时快速抛出异常，有助于简化代码
     *
     * @param mixed                 $condition
     * @param int                   $status
     * @param string                $message
     * @param int|string|array|null $addition
     */
    public function assert($condition, $status, $message = '', $addition = null): void
    {
        if (! $condition) {
            $this->response->withStatus($status);
            throw new Exception($message, -1, $addition);
        }
    }

    /**
     * 为输入参数提供快捷访问实例，在业务处理时，
     * 直接使用 $ctx->input 属性即可获取 queryString 参数与 form data 等参数
     *
     * 注：如果 query 参数与 form data 参数存在同名参数时
     * form data 参数将会覆盖 query 参数，若想获取正确 query 参数
     * 请使用 $ctx->request->queryParams() 方法手动提取
     */
    private function processInput(): Input
    {
        $dataPayload = [];

        $queryParams = $this->request->getQueryParams();
        if ($queryParams) {
            $dataPayload += $queryParams;
        }

        // 此处 parsedBody 可能是 array, object, null 中的一种
        // 需要手动处理为 array 格式
        $parsedBody = $this->request->getParsedBody();
        if (is_object($parsedBody)) {
            $parsedBody = objectToArray($parsedBody);
        }

        if ($parsedBody) {
            $dataPayload += $parsedBody;
        }

        return new Input($dataPayload);
    }
}
