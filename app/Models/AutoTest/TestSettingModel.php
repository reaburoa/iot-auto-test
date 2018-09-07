<?php

namespace App\Models\AutoTest;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestSettingModel extends Model
{
    use ModelTrait;

    protected $connection = 'iot_test';
    protected $table = 'test_setting';

    // 复合主键
    public $timestamps = false; // 默认 Eloquent 会自动维护数据库表的 created_at 和 updated_at 字段, 置为false

    public function getModel()
    {
        return DB::connection($this->connection)->table($this->table);
    }

    public function insert($data)
    {
        return $this->getModel()->insert($data);
    }

    public function getByMsn($msn, $model)
    {
        $ret = self::query()->where('model', $model)->where('msn', $msn)->first();
        if ($ret === null) {
            return [];
        }
        return $ret ? json_decode(json_encode($ret), true) : $ret;
    }

    public function updateByMsn($msn, $model, $setting, $update_value)
    {
        return $this->getModel()
            ->where('model', $model)
            ->where('msn', $msn)
            ->where('setting', $setting)
            ->update($update_value);
    }
}
