<?php

namespace Sorry510\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * 自动注入事务
 * @Annotation
 * @Target("METHOD")
 */
class Transaction
{
    /** @var boolean */
    public $auto = true;
}
