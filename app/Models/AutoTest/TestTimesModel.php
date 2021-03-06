<?php

namespace App\Models\AutoTest;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestTimesModel extends Model
{
    use ModelTrait;

    protected $connection = 'iot_test';
    protected $table = 'test_times';

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

    public function updateByTimes($times, $update_value, $model)
    {
        return $this->getModel()
            ->where('turn_times', $times)
            ->where('model', $model)->update($update_value);
    }

    public function getByTimes($times, $model)
    {
        return $this->getModel()->where('model', $model)->where('turn_times', $times)->first();
    }
}
