<?php

namespace App\Models\AutoTest;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestAppDetailModel extends Model
{
    use ModelTrait;

    protected $connection = 'iot_test';
    protected $table = 'test_app_detail';

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
        $ret = self::query()->where('model', $model)->where('msn', $msn)->orderBy('id', 'desc')->first();
        if ($ret === null) {
            return [];
        }
        return $ret ? json_decode(json_encode($ret), true) : $ret;
    }

    public function hasUpgradeApp($msn, $model)
    {
        $ret = self::query()
            ->where('model', $model)
            ->where('msn', $msn)
            ->where('app_upgrade_state', -1)
            ->orderBy('id', 'desc')
            ->first();
        if ($ret === null) {
            return [];
        }
        return $ret ? json_decode(json_encode($ret), true) : $ret;
    }

    public function updateByMsn($msn, $model, $update_value)
    {
        return $this->getModel()->where('model', $model)->where('msn', $msn)->update($update_value);
    }

    public function updateAppUpgradeState($msn, $model, $to_app, $state = 1)
    {
        return $this->getModel()
            ->where('model', $model)
            ->where('msn', $msn)
            ->where('to_app_version', $to_app)
            ->update([
                'app_upgrade_end' => date('Y-m-d H:i:s'),
                'app_upgrade_state' => $state,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }

    public function updateAppDownloadState($msn, $model, $to_app, $state = 1)
    {
        return $this->getModel()
            ->where('model', $model)
            ->where('msn', $msn)
            ->where('to_app_version', $to_app)
            ->update([
                'app_download_state' => $state,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
    }
}
