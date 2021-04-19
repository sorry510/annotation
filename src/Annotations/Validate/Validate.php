<?php

namespace Sorry510\Annotations\Validate;

use Illuminate\Support\Facades\Validator;

abstract class Validate
{
    //验证规则
    abstract protected function rule(): array;

    //提示信息，attribute是占位符，这里是custom方法的value
    protected $message = [];

    //自定义字段名称，提示的时候用到
    protected $custom = [];

    // 验证场景，仿tp框架的写法
    protected $scene = [];

    //错误信息
    public $errorMsg = "";

    private $useScene = "";

    /**
     * @param $data 数据
     */
    public function check($data)
    {
        $rule = $this->rule();
        if ($this->useScene) {
            $rules_new = [];
            foreach ($this->scene[$this->useScene] as $k) {
                // 删除scene中没有的验证参数
                $rules_new[$k] = $rule[$k];
            }
            $validator = Validator::make($data, $rules_new, $this->message, $this->custom);
        } else {
            // 如果不存在数据验证分组，直接make
            $validator = Validator::make($data, $rule, $this->message, $this->custom);
        }
        if ($validator->fails()) {
            //验证数据不通过，抛出异常
            $this->errorMsg = $validator->errors()->first();
            return false;
        }
        return true;
    }

    /**
     * @param $scene 场景
     */
    public function scene($scene)
    {
        if (!empty($this->scene[$scene]) && is_array($this->scene[$scene])) {
            $this->useScene = $scene;
        }
        return $this;
    }
}
