<?php

namespace App\Models\AutoTest;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestMachineModel extends Model
{
    use ModelTrait;

    protected $connection = 'iot_test';
    protected $table = 'test_machine';

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

    public function updateByMsn($msn, $model, $update_value)
    {
        return $this->getModel()->where('model', $model)->where('msn', $msn)->update($update_value);
    }

    public function incrementTestTimes($msn, $model, $increment = 1)
    {
        return $this->getModel()->where('model', $model)->where('msn', $msn)->increment('turn_times', $increment);
    }

    public function getMachineTotalGroupByTestTurn($model)
    {
        return $this->getModel()->where('model', $model)->where('enabled', 0)->select(DB::raw('turn_times, count(turn_times) as times'))->groupBy('turn_times')->get();
    }

    public function getTotalMachines($model)
    {
        return $this->getModel()->where('model', $model)->where('enabled', 0)->count();
    }

    public function getAllMsn($model)
    {
        return self::query()->where('model', $model)->where('enabled', 0)->orderBy('id', 'desc')->select()->get()->toArray();
    }

    public function getNotTurn($turn_times, $model)
    {
        return self::query()->where('model', $model)->where('turn_time', '!=', $turn_times)->select()->get()->toArray();
    }
}
