## 安装

```
composer require sorry510/annotation
```

## 使用说明

#### 功能
>添加了 4 中注解

- `@Validator` 参数验证(只能使用在 `Controller`中)
- `@RequestParam` 添加默认参数，传入同名参数会覆盖默认参数(只能使用在 `Controller`中)
- `@Transaction` 添加事务(只能使用在 `Controller`中)
- `@Inject` 注入属性(注意：循环注入可能会有循环依赖的问题a->b->c->a)

#### 使用前提

- 生成配置文件

```
php artisan vendor:publish --tag=annotation
```

- 在对应的 `Controller` 文件中添加对应的注解的`命名空间`

```
namespace App\Controller;

use Sorry510\Annotations\Validator;
use Sorry510\Annotations\RequestParam;
use Sorry510\Annotations\Transaction;
use Sorry510\Annotations\Inject;
```

- 在 `app\Http\Kernel.php` 添加 `annotation` 中间件

```
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    ...

    protected $middlewareGroups = [
        'api' => [
            'queryAuth',
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'annotation', // 注解中间件
        ],
    ];

    ...
}

```


#### `@Validator`

`Validator(class, scene)`有 2 个参数 `class` 和 `scene`,
- class 是选用的验证器的类的完整路径`（必填）`
- scene 是场景`（可选）`
  
>当验证没有通过时，会抛出`异常`被捕获，直接返回

```
{
	"code": 400,
	"message": "验证错误信息",
	"data": null,
    "timestamp": 1618801860765
}
```

##### Validate 类的写法

```
<?php 
namespace App\validate;

use Sorry510\Annotations\Validate\Validate;

class FormValidate extends Validate
{
    // 验证规则
    protected function rule(): array
    {
        return [
            'name' => 'required|max:100|unique:roles',
            'describe' => 'required|max:100|unique:roles',
        ];
    }

    // 提示信息 attribute是占位符，这里是custom方法的value
    protected $message = [
        'required' => ':attribute不能为空',
        'max' => ':attribute长度最多为max',
        'unique' => ':attribute必须唯一',
    ];

    // 自定义字段名称，提示的时候用到
    protected $custom = [
        'name' => '角色名称',
        'describe' => '角色描述',
    ];

    // 场景
    protected $scene = [
        "add" => ["name", "describe"],
        "edit" => ["name", "describe"],
    ];
}
```

示例

```

use Sorry510\Annotations\Validator;
use App\validate\FormValidate;
use Illuminate\Routing\Controller;

class Test extends Controller
{

   /**
     * @Validator(class=FormValidate::class, scene="add")
     */
    public function index()
    {
        return response()->json(['code' => 200, 'data' => request()->all()]);
    }
}
```

#### `@RequestParam`

`RequestParam(fields, method)`有 2 个参数 `fields` 和 `method`,
- fields 定义要获取的字段名，可批量设置默认值，如果前端传入了对应 key 可以覆盖默认值`（必填）`
- method 获取参数的方法,支持 get、post、put、delete，不填写默认是 param`（可选）`


示例

```

use Sorry510\Annotations\RequestParam;
use Illuminate\Routing\Controller;

class Test extends Controller
{

    /**
     * @RequestParam(fields={"title": "hello","num": 2})
     */
    public function index()
    {
		// ["title" => "hello", "num" => 2]
        return response()->json(['code' => 1, 'data' => request()->all()]);
    }
}

```

>ps: 注意默认是数组的写法是用 `{}` 而不是 `[]` , 字符串必须用 `"` , 不能用`'`

```

{"title": "hello", "num": {}} => ["title" => "hello", "num" => []]
{"title", "num"} => ['title', 'num']

```

#### `@Transaction`

示例

```

use Sorry510\Annotations\Transaction;
use Illuminate\Routing\Controller;

class Test extends Controller
{

    /**
     * @Transaction
     */
    public function test2()
    {
        $data = UnitModel::find();
        $data->stage = rand(1, 10);
        $data->save();
        RollerModel::create([
            'rid' => rand(1, 10),
        ]);
        return response()->json(['code' => 1]);
    }
}
```

>ps: 事务对http的返回code码为200~300之间的自动提交，其它的进行回滚操作


#### `@Inject`

 `Inject(args)`有 1 个参数 `args`,
  - args 定义类的初始化参数`（可选,可以在注入自定义属性内容）`
  
> 在注释中添加 `@var xxxclass` 注释想要被注入的类即可实现注入，注入为循环模式，最多 `2` 层深度,相当于可以在 controller 层注入任意的类, 同时在这个任意的类注入另一些类，当被注入的类有构造函数 `__contruct` 时,会依据构造函数实例化，构造函数中含有的实体类参数也会被自动注入，非实体类参数可以通过 `args` 传参传入(**非实体类参数必须放到实体类参数后面**)
  

完整示例

> controller 层注入

```
use App\logic\UnitLogic;
use App\logic\UnitLogic2;
use App\logic\UnitLogic3;
use Illuminate\Routing\Controller;

class Test extends Controller
{
    /**
     * @Inject
	 * @var UnitLogic
     */
    private $unitLogic;
	
	/**
     * @Inject
	 * @var UnitLogic2
     */
    private $unitLogic2;
	
	/**
     * @Inject({"other": "foo"})
	 * @var UnitLogic3
     */
    private $unitLogic3;
}
```

> 循环注入属性，有如下 2 中方式

- 非构造函数方式继续使用 `Inject` 注入

```
use App\Models\UnitModel;

class UnitLogic
{
	/**
     * @Inject
     * @var UnitModel
     */
    private $model;
}
```

- 构造函数方式注入

```
use App\Models\UnitModel;

class UnitLogic2
{
    private $model;

    public function __construct(UnitModel $model)
    {
        $this->model = $model;
    }
}
```

- 构造函数方式传参注入(**非实体类参数必须放到实体类参数后面**)

```
use App\Models\UnitModel;

class UnitLogic3
{
    private $model;
	
	private $other;

    public function __construct(UnitModel $model, $other = null)
    {
        $this->model = $model;
		$this->other = $other;
    }
}
```