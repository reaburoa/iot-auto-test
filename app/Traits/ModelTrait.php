<?php
namespace App\Traits;

use App\Exceptions\BaseException;

/**
 * 定义Model Event
 */
trait ModelTrait
{
    /**
     * 模型验证之后返回异常
     * @param array $errors
     * @throws
     * @return true
     */
    public function afterValidate($errors = [])
    {
        if(!empty($errors)) {
            throw new BaseException($errors[0]);
        }

        return true;
    }
}
