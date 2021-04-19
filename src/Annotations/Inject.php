<?php

namespace Sorry510\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * 自动注入对象
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Inject
{
    /** @var array */
    public $args;
}
