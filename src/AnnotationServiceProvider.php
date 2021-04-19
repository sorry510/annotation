<?php

namespace Sorry510;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AnnotationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addMiddlewareAlias('annotation', Sorry510\Annotations\Middleware\Annotation::class); // 在 $routeMiddleware 中添加别名
        $this->publishes([
            __DIR__ . '/Annotations/config/annotation.php' => config_path('annotation.php'),
        ], 'annotation');
    }

    protected function addMiddlewareAlias($name, $class)
    {
        $router = $this->app['router'];

        // 判断aliasMiddleware是否在类中存在
        if (method_exists($router, 'aliasMiddleware')) {
            return $router->aliasMiddleware($name, $class);
        }

        return $router->middleware($name, $class);
    }

}
