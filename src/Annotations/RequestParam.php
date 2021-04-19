<?php

namespace Sorry510\Annotations;

use Doctrine\Common\Annotations\Annotation\Enum;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 */
class RequestParam
{
    /**
     * 初始化字段
     * @var array
     */
    public $fields;

    /**
     * 获取参数的方法 不填默认是param形式获取
     * @Enum({"get","post","put","delete"})
     * @var string
     */
    public $method;
}
