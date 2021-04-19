<?php
namespace Sorry510\Annotations\annotation;

class HandleTransaction
{
    /**
     * @var \Illuminate\Http\Response
     */
    protected $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * 检查事务是提交还是回滚
     * @Author sorry510 491559675@qq.com
     * @DateTime 2021-04-19
     *
     * @return boolean
     */
    public function check(): bool
    {
        $httpCode = $this->response->getStatusCode();
        if ($httpCode >= 200 && $httpCode <= 300) {
            return true;
        }
        return false;
    }
}
