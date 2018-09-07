<?php

namespace App\Services;

use App\Core\Service;
use App\Models\AutoTest\TestFirmwareDetailModel;
use App\Models\AutoTest\TestMachineModel;
use App\Models\AutoTest\TestTimesModel;

class IotService extends Service
{
    public function initTestMachine(array $msn_list, $model)
    {
        $m_model = new TestMachineModel();
        $now = date('Y-m-d H:i:s');
        $total = 0;
        foreach ($msn_list as $msn) {
            $machine_info = [
                'msn' => $msn,
                'model' => $model,
                'firmware_version' => '',
                'app_version' => '',
                'is_online' => 0,
                'now_state' => 0,
                'enabled' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $ret = $m_model->insert($machine_info);
            if ($ret) {
                $total ++;
            }
        }

        return $total;
    }

    public function updateTestStat($model)
    {
        $m_model = new TestMachineModel();
        $detail_model = new TestFirmwareDetailModel();
        $test_turn = $detail_model->getMachineTotalGroupByTestTurn($model);
        $test_turn = json_decode(json_encode($test_turn), true);
        $t_model = new TestTimesModel();
        $total_machines = $m_model->getTotalMachines($model);
        $now = date('Y-m-d H:i:s');
        foreach ($test_turn as $value) {
            $has = $t_model->getByTimes($value['turn_times']);
            if ($has) {
                $data = [
                    'total_machine' => $total_machines,
                    'succ_machine' => $value['times'],
                    'updated_at' => $now,
                ];
                $t_model->updateByTimes($value['turn_times'], $data, $model);
            } else {
                $data = [
                    'turn_times' => $value['turn_times'],
                    'total_machine' => $total_machines,
                    'succ_machine' => $value['times'],
                    'model' => $model,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $t_model->insert($data);
            }
        }
    }

    public function updateOnlineState($msn, $online_state, $model)
    {
        $m_model = new TestMachineModel();
        $m_model->updateByMsn($msn, $model, [
            'is_online' => $online_state,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 下载完成
     */
    public function dealS04($msn, $data, $model)
    {
        $download = $data['s04'];
        $detail_model = new TestFirmwareDetailModel();
        $has_upgrade = $detail_model->hasUpgradeFirmware($msn, $model);
        if (empty($has_upgrade)) {
            echo "{$msn} has no upgrade task ...\n";
            return false;
        }
        $firmware_download_state = strtolower($download) == 'success' ? 1 : 0;
        return $detail_model->updateFirmwareDownloadState($msn, $model, $has_upgrade['to_firmware_version'], $firmware_download_state);
    }

    public function getSubTopic($msn, $model)
    {
        return '/'.$model.'/'.$msn.'/sub';
    }

    public function getPubTopic($msn, $model)
    {
        return '/'.$model.'/'.$msn.'/pub';
    }
}