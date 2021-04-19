<?php

namespace Sorry510\Annotations\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Sorry510\Annotations\annotation\Scaner;

/**
 * 注解中间件(解析控制器中的注解内容)
 * @Author sorry510 491559675@qq.com
 * @DateTime 2020-10-14
 */
class Annotation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * @var \Illuminate\Routing\Route;
         */
        $route = $request->route();
        $reflectionRouteMethod = new \ReflectionMethod($route, 'isControllerAction');
        $reflectionRouteMethod->setAccessible(true); // 开启私有方法的执行权
        if (!$reflectionRouteMethod->invoke($route)) { // 查看是否是 ControllerAction
            return $next($request);
        }
        // if (!is_string($route->action)) {
        //     return $next($request);
        // }
        $controller = $route->getController();
        $method = $route->getActionMethod();

        $scaner = Scaner::getInstance();
        $scaner->handlePropAnnotation($controller); // 属性注解,会有循环依赖的问题
        $scaner->handleMethodAnnotation($controller, $method); // 执行方法注解
        if ($scaner->autoTransaction()) {
            DB::beginTransaction();
            /**
             * @var \Illuminate\Http\Response
             */
            $response = $next($request); // 异常已经被捕获并处理了
            $transactionClass = config('annotation.transaction');
            $result = (new $transactionClass($response))->check();
            if ($result) {
                DB::commit();
                return $response;
            }
            DB::rollback();
            return $response;
        } else {
            return $next($request);
        }
    }
}
