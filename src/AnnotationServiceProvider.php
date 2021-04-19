<?php

namespace Sorry510;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AnnotationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addMiddlewareAlias('annotation', \Sorry510\Annotations\Middleware\Annotation::class); // 在 $routeMiddleware 中添加别名
        $this->inject();
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

    /**
     * 注入参数
     * @Author sorry510 491559675@qq.com
     * @DateTime 2021-04-19
     *
     * @return void
     */
    protected function inject()
    {
        Request::macro('injectParams', function ($params, $method = '') {
            if (is_array($params)) {
                $post = null;
                $get = null;
                switch (strtoupper($method)) {
                    case 'POST':
                        $post = $this->request;
                        break;
                    case 'GET':
                        $get = $this->query;
                        break;
                    default:
                        $post = $this->request;
                        $get = $this->query;
                }
                foreach ($params as $key => $row) {
                    if ($post) {
                        if (!$post->has($key)) {
                            $post->set($key, $row);
                        }
                    }
                    if ($get) {
                        if (!$get->has($key)) {
                            $get->set($key, $row);
                        }
                    }
                }
            }
        });
    }

}
