<?php
namespace App\Core;

/**
 * App\Core\Model
 *
 * @see \Illuminate\Database\Eloquent\Model
 * @mixin \Eloquent
 */
use Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
    /**
     * 重载boot函数
     */
    protected static function boot()
    {
        parent::boot();

        //注册自定义模型观察者
        self::observe(new Observer());
    }

    /**
     * 模型层数据验证
     * @return boolean
     *
     */
    public function validate()
    {
        if (property_exists($this, 'rules')) {
            $validator = \Validator::make($this->toArray(), $this->rules);
            if (property_exists($this, 'customMessages')) {
                $validator->setCustomMessages($this->customMessages);
            }

            $errors = $validator->messages()->all();
            if ($validator->failed()) {
                if (method_exists($this, 'afterValidate')) {
                    return $this->afterValidate($errors);
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 检测字段改变
     * @param array $param
     * @return array
     *
     */
    public function change($param)
    {
        $data = [];
        foreach ($param as $key => $value) {
            if(is_null($value)) continue;
            if($this->$key == $value) continue;
            $data[$key] = $value;
        }

        return $data;
    }

}