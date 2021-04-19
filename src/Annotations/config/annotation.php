<?php

return [
    'cache' => env('ANNOTATION_CACHE', false), // 缓存
    'validateException' => \Sorry510\Annotations\Exceptions\ValidateException::class, // 自定义处理 validate 异常类
    'transaction' => \Sorry510\Annotations\annotation\HandleTransaction::class, // 处理事务类
    'whiteWords' => [], // 注解白名单名词，避免注释影响注解
    'whiteNamespace' => [], // 注解白名单命名空间，避免注释影响注解
];
