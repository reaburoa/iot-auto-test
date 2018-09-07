<?php

namespace App\Core;

/**
 * 重写模型观察者类, 绑定自定义模型观察者
 * @function beforeSave
 * @function afterSave
 * @function afterValidate
 * @function afterDelete
 */
class Observer
{
    /**
     * 注册模型保存前事件
     */
    public function saving($model) {
        /**
         * Validate
         */
        $model->validate();

        if(method_exists($model, 'beforeSave')) {
            $model->beforeSave();
        }

    }

    /**
     * 注册模型保存之后事件
     */
    public function saved($model) {
        if(method_exists($model, 'afterSave')) {
            $model->afterSaved();
        }
    }

    /**
     * 注册模型删除之后事件
     */
    public function deleted($model) {
        if(method_exists($model, 'afterDelete')) {
            $model->afterDeleted();
        }
    }
}