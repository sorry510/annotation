<?php

namespace Sorry510\Annotations\annotation;

use DirectoryIterator;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use PhpDocReader\PhpDocReader;
use ReflectionClass;
use ReflectionMethod;
use Sorry510\Annotations\Inject;
use Sorry510\Annotations\RequestParam;
use Sorry510\Annotations\Transaction;
use Sorry510\Annotations\Validate\Validate;
use Sorry510\Annotations\Validator;

class Scaner
{

    /**
     * 容器
     * @var [array]
     * @Author sorry510 491559675@qq.com
     * @DateTime 2020-09-29
     */
    protected $container = [];

    protected $transaction = false; // 是否自动开启事务

    /**@var AnnotationReader*/
    protected $reader;
    /**@var PhpDocReader*/
    protected $docReader;

    private static $instance;

    /**
     * 注解名称读取白名单
     * @var array
     */
    protected $whitelist = [
        "author", "var", "after", "afterClass", "backupGlobals", "backupStaticAttributes", "before", "beforeClass", "codeCoverageIgnore*",
        "covers", "coversDefaultClass", "coversNothing", "dataProvider", "depends", "doesNotPerformAssertions",
        "expectedException", "expectedExceptionCode", "expectedExceptionMessage", "expectedExceptionMessageRegExp", "group",
        "large", "medium", "preserveGlobalState", "requires", "runTestsInSeparateProcesses", "runInSeparateProcess", "small",
        "test", "testdox", "testWith", "ticket", "uses", "Author", "DateTime",
    ];

    /**
     * 注解命名空间读取白名单
     * @var array
     */
    protected $whiteNamespaceList = [
        "OpenApi", "OA", "OpenApi\Annotations",
    ];

    private function __construct()
    {
        $this->init();
    }

    private function __clone()
    {}

    public static function getInstance()
    {
        if (!static::$instance instanceof self) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function init()
    {
        // AnnotationRegistry::registerLoader('class_exists'); // 此种加载方式，会导致无法过滤 swagger
        foreach (new DirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }
            AnnotationRegistry::registerFile($fileInfo->getRealPath());
        }
        foreach (array_merge($this->whitelist, config('annotation.whiteWords')) as $name) {
            AnnotationReader::addGlobalIgnoredName($name);
        }
        foreach (array_merge($this->whiteNamespaceList, config('annotation.whiteNamespace')) as $namespace) {
            AnnotationReader::addGlobalIgnoredNamespace($namespace);
        }
        $this->reader = config('annotation.cache') ?
        new FileCacheReader(new AnnotationReader(), storage_path('annotation/cache')) :
        new AnnotationReader();
        $this->docReader = new PhpDocReader();
    }

    /**
     * 处理所有的方法注解
     *
     * @param $instance
     * @param $method
     * @return void
     */
    public function handleMethodAnnotation($instance, $method)
    {
        $reflectionMethod = new ReflectionMethod($instance, $method);
        $methodAnnotations = $this->reader->getMethodAnnotations($reflectionMethod);
        foreach ($methodAnnotations as $methodAnnotation) {
            if ($methodAnnotation instanceof Validator) { // 验证器
                if (!class_exists($methodAnnotation->class)) {
                    throw new \RunTimeException('class ' . $methodAnnotation->class . ' is not find');
                }
                $validate = (new ReflectionClass($methodAnnotation->class))->newInstanceWithoutConstructor();
                if (!$validate instanceof Validate) {
                    throw new \RunTimeException('class ' . $methodAnnotation->class . ' is not a validate class');
                }
                if ($methodAnnotation->scene) {
                    $validate->scene($methodAnnotation->scene);
                }
                $request = app(Request::class);
                if (!$validate->check($request->all())) {
                    // Bad Request
                    $exception = config('annotation.validateException', \Sorry510\Annotations\Exceptions\ValidateException::class);
                    throw new $exception(400, $validate->errorMsg);
                }
            } else if ($methodAnnotation instanceof RequestParam) {
                // 参数获取器
                $fields = $methodAnnotation->fields;
                $method = $methodAnnotation->method;
                $request = app(Request::class);
                $request->injectParams($fields, $method);
            } else if ($methodAnnotation instanceof Transaction) {
                // 开启自动事务
                $this->transaction = true;
            }
        }
    }

    /**
     * 读取类的所有属性的注解
     * @param $instance
     * @throws \ReflectionException
     */
    public function handlePropAnnotation($instance)
    {
        $this->deepInject($instance, 3);
    }

    /**
     *
     * 循环注入依赖
     * @Author sorry510 491559675@qq.com
     *
     * @param [type] $instance
     * @param integer $loop 深度默认3层
     * @return void
     */
    public function deepInject($instance, $loop = 3)
    {
        if ($loop <= 0) {
            return;
        }
        $reflClass = new ReflectionClass($instance);
        $reflectionProperties = $reflClass->getProperties(); // 获取反射类的所有属性，没有返回空数组
        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyAnnotations = $this->reader->getPropertyAnnotations($reflectionProperty); // 获取某个属性上的注解类，没有返回空数组
            foreach ($propertyAnnotations as $propertyAnnotation) {
                if ($propertyAnnotation instanceof Inject) {
                    $propertyClass = $this->docReader->getPropertyClass($reflectionProperty); // 获取 @var 的类名
                    if ($propertyClass) {
                        if (interface_exists($propertyClass)) {
                            // 从 laravel 容器中获取
                            $inject = app($propertyClass);
                        } elseif (isset($this->container[$propertyClass])) {
                            // 单例
                            $inject = $this->container[$propertyClass];
                        } else {
                            $injectReflect = new ReflectionClass($propertyClass);
                            if ($injectReflect->hasMethod('__construct')) {
                                // 有构造函数时，使用构造函数创建
                                $classArgs = [];
                                $refMethod = new ReflectionMethod($propertyClass, '__construct');
                                if ($refMethod->getNumberOfParameters() > 0) {
                                    // 注入实体类的参数
                                    foreach ($refMethod->getParameters() as $param) {
                                        $class = $param->getClass(); // 获取参数的实体类
                                        if ($class instanceof ReflectionClass) {
                                            $classArgs[$class->name] = $class->newInstanceWithoutConstructor(); // 依赖注入的 class 以非构造方式创建
                                        }
                                    }
                                }
                                $args = $propertyAnnotation->args ?: []; // 注解的参数
                                $args = array_values(array_merge($classArgs, $args)); // 非实体类参数必须放到后边
                                $inject = $injectReflect->newInstanceArgs($args);
                            } else {
                                $inject = $injectReflect->newInstanceWithoutConstructor();
                            }
                            if (!$inject instanceof Model) {
                                // model 不用单例模式，model 单例可能会造成数据混乱
                                $this->container[$propertyClass] = $inject;
                            }
                        }
                        $reflectionProperty->setAccessible(true); // 更改私有属性为公开
                        $reflectionProperty->setValue($instance, $inject); // 注入属性所需要的实体类
                        $this->deepInject($inject, $loop - 1);
                    }
                }
            }
        }
    }

    /**
     *
     * @Author sorry510 491559675@qq.com
     * @DateTime 2020-10-14
     *
     * @return void
     */
    public function autoTransaction()
    {
        return $this->transaction;
    }

}
