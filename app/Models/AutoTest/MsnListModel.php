<?php

namespace App\Models\AutoTest;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MsnListModel extends Model
{
    use ModelTrait;

    protected $connection = 'iot_test';
    protected $table = 'snlist';
    // 复合主键
    public $timestamps = false; // 默认 Eloquent 会自动维护数据库表的 created_at 和 updated_at 字段, 置为false

    public function getModel()
    {
        return DB::connection($this->connection)->table($this->table);
    }

    public function getAllMsn()
    {
        return self::query()->orderBy('id', 'desc')->select()->get()->toArray();
    }

}