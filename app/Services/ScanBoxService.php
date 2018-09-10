<?php

namespace App\Services;

use App\Models\AutoTest\TestFirmwareDetailModel;
use App\Models\AutoTest\TestMachineModel;
use App\Models\AutoTest\TestSettingModel;

class ScanBoxService extends IotService
{
    const MODEL = 'NS010';

    const SETTING_COMMAND = [
        'c01' => [
            'pv' => 2,
            'vs' => 1,
            'kb' => 0,
            'wxp' => 0,
            'ap' => 1,
            'up' => 1,
            'qp' => 1,
            'bdp' => 1,
            'jp' => 1,
            'bp' => 1,
            'wp' => 1,
            'ii' => 5
        ]
    ];

    public static $DOWNLOAD_COMMAND = [
        'c02' => [
            'url' => '',
            'size' => '',
            'md5' => ''
        ]
    ];

    const RESTART_COMMAND = [
        'c03' => 'restart'
    ];

    const BIN176 = [
        'hardware_version' => '1.7.17',
        'url' => 'test.cdn.sunmi.com/IOT-OTA/15347437755651.bin',
        'size' => 2212928,
        'md5' => '85d256813ce8624ac33d6336f9d20a91'
    ];

    const BIN276 = [
        'hardware_version' => '2.7.17',
        'url' => 'test.cdn.sunmi.com/IOT-OTA/15347437859834.bin',
        'size' => 2212928,
        'md5' => 'f34070fe2326e6a4508707c55f742145'
    ];

    const BIN376 = [
        'hardware_version' => '3.7.17',
        'url' => 'test.cdn.sunmi.com/IOT-OTA/15347437976178.bin',
        'size' => 2212928,
        'md5' => '6c538e9f1cd50c3a5c7b5573052c0cd2'
    ];

    /**
     * 上报终端信息，版本、经纬度等
     */
    public function dealS01($msn, $data)
    {
        $m_model = new TestMachineModel();
        $machine_info = $m_model->getByMsn($msn, self::MODEL);
        if ($machine_info === false || !is_array($machine_info)) {
            echo "Something Error Happened\n";
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if (empty($machine_info)) {
            echo "{$msn} is not in test list ...\n";
            return false;
        }
        if ($data['bv'] == $machine_info['firmware_version']) {
            return $machine_info;
        }
        $machine = [
            'firmware_version' => $data['bv'],
            'updated_at' => $now,
        ];
        $ret = $m_model->updateByMsn($msn, self::MODEL, $machine);
        $machine_info['firmware_version'] = $data['bv'];
        $machine_info['updated_at'] = $now;
        $detail_model = new TestFirmwareDetailModel();
        $last_upgrade_info = $detail_model->getByMsn($msn, self::MODEL);
        $upgrade_state = 0;
        if ($last_upgrade_info && $last_upgrade_info['to_firmware_version'] == $data['bv']) {
            $upgrade_state = 1;
        }
        $test_ret = $detail_model->updateFirmwareUpgradeState($msn, self::MODEL, $data['bv'], $upgrade_state);
        return $ret === false ? false : $machine_info;
    }

    public function upgrade($msn, $machine_info, $emqtt_instance)
    {
        $detail_model = new TestFirmwareDetailModel();
        $last_test = $detail_model->hasUpgradeFirmware($msn, self::MODEL);
        $topic = $this->getSubTopic($msn, self::MODEL);
        if ($last_test) {
            echo "{$msn} is upgrading ...\n";
            if ((time() - strtotime($last_test['created_at'])) >= 80 * 60) {
                $detail_model->updateFirmwareUpgradeState($msn, self::MODEL, $last_test['to_firmware_version'], 2);
                $emqtt_instance->publish($topic, json_encode(self::RESTART_COMMAND));
            }
            return '';
        }
        $upgrading_total = $detail_model->getUpgradingTotal($machine_info['turn_times'], self::MODEL);
        if ($upgrading_total > 0) {
            echo "{$machine_info['turn_times']} has other machine upgrade \n";
            return '';
        } elseif ($upgrading_total == 0) {
            echo "{$machine_info['turn_times']} has no machine upgrade and wait for restart all machine \n";
            return '';
        }
        $m_model = new TestMachineModel();
        $will_grade_info = $this->getWillUpgradeInfo($machine_info);
        if (empty($will_grade_info)) {
            return false;
        }
        $ret = $m_model->incrementTestTimes($msn, self::MODEL);
        $now = date('Y-m-d H:i:s');
        $data = [
            'msn' => $msn,
            'model' => self::MODEL,
            'old_firmware_version' => $machine_info['firmware_version'],
            'to_firmware_version' => $will_grade_info['firmware_version'],
            'firmware_upgrade_start' => $now,
            'firmware_download_state' => -1,
            'firmware_upgrade_state' => -1,
            'turn_times' => $machine_info['turn_times'] + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $detail_model->insert($data);
        self::$DOWNLOAD_COMMAND['c02'] = $will_grade_info['task'];
        $this->updateTestStat(self::MODEL);
        $emqtt_instance->publish($topic, json_encode(self::$DOWNLOAD_COMMAND));
    }

    public function getWillUpgradeInfo($machine_info)
    {
        if (preg_match('/^1\./', $machine_info['firmware_version'])) {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_COMMAND['c02'] = [
                'url' => self::BIN276['url'],
                'size' => self::BIN276['size'],
                'md5' => self::BIN276['md5']
            ];
            $firmware = self::BIN276['hardware_version'];
        } elseif (preg_match('/^2\./', $machine_info['firmware_version'])) {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_COMMAND['c02'] = [
                'url' => self::BIN376['url'],
                'size' => self::BIN376['size'],
                'md5' => self::BIN376['md5']
            ];
            $firmware = self::BIN376['hardware_version'];
        } elseif (preg_match('/^3\./', $machine_info['firmware_version'])) {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_COMMAND['c02'] = [
                'url' => self::BIN176['url'],
                'size' => self::BIN176['size'],
                'md5' => self::BIN176['md5']
            ];
            $firmware = self::BIN176['hardware_version'];
        } else {
            echo "Firmware version is not test version ...\n";
            return [];
        }

        return [
            'firmware_version' => $firmware,
            'task' => $task
        ];
    }

    public function publishSetting(array $msns, $setting)
    {
        $model = new TestSettingModel();
        $now = date('Y-m-d H:i:s');
        foreach ($msns as $msn) {
            $data = [
                'msn' => $msn,
                'model' => self::MODEL,
                'setting' => json_encode($setting),
                'setting_state' => -1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $model->insert($data);
        }
    }

    /**
     * 设置
     */
    public function dealS03($msn, $data)
    {
        $setting_ret = $data['s03'];
        $setting_up = md5(json_encode(self::SETTING_COMMAND)) == $setting_ret ? 1 : 0;
        $setting_model = new TestSettingModel();
        return $setting_model->updateByMsn($msn, self::MODEL, json_encode(self::SETTING_COMMAND), [
            'setting_state' => $setting_up,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getDataUser()
    {
        $user = config('iot.scan_box.data_client');
        return [
            'host' => config('iot.scan_box.host'),
            'client_id' => $user['client_id'],
            'password' => $user['password'],
            "username" => $user['username']
        ];
    }

    public function getActiveUser()
    {
        $user = config('iot.scan_box.active_client');
        return [
            'host' => config('iot.scan_box.host'),
            'client_id' => $user['client_id'],
            'password' => $user['password'],
            "username" => $user['username']
        ];
    }

    public function getExportData()
    {
        $data = TestFirmwareDetailModel::query()
            ->where('model', self::MODEL)
            ->orderBy('turn_times')->orderBy('msn')->get()->toArray();
        $list = [];
        $setting_data = TestSettingModel::query()->where('model', self::MODEL)->groupBy('msn')->get()->toArray();
        foreach ($data as $value) {
            $list[$value['turn_times']][] = $value;
        }
        unset($data);

        return $list;
    }
}
