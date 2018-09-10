<?php

namespace App\Services;

use App\Models\AutoTest\TestFirmwareDetailModel;
use App\Models\AutoTest\TestMachineModel;

class MarioService extends IotService
{
    const MODEL = 'FR010';

    public static $SERVER_UNIX_TIME_COMMAND = [
        'c01' => [
            'timestamp' => ''
        ]
    ];

    public static $DOWNLOAD_FIRMWARE_COMMAND = [
        'c02' => [
            'url' => '',
            'size' => '',
            'md5' => '',
            'upgrade' => 1
        ]
    ];

    const RESTART_COMMAND = [
        'c03' => 'restart'
    ];

    public static $DOWNLOAD_TMS_COMMAND = [
        'c04' => [
            'url' => '',
            'size' => '',
            'md5' => '',
            'type' => ''
        ]
    ];

    const BIN5509 = [
        'hardware_version' => '1.55.09',
        'url' => 'https://test.cdn.sunmi.com/IOT-OTA/FR010-FR010-1.55.09.zip',
        'size' => 5971092,
        'md5' => '1c0e1ea9b88b3bf64d1866c1b5814d6e'
    ];

    const BIN5510 = [
        'hardware_version' => '1.55.10',
        'url' => 'https://test.cdn.sunmi.com/IOT-OTA/FR010-FR010-1.55.10.zip',
        'size' => 5971558,
        'md5' => 'a2312d5a3aa4a9569ad7f4198b7f0e96'
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
        $upgrading_total = $detail_model->getUpgradingTotal($machine_info['turn_times']);
        if ($upgrading_total > 0) {
            echo "{$machine_info['turn_times']} has other machine upgrade \n";
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
        self::$DOWNLOAD_FIRMWARE_COMMAND['c02'] = $will_grade_info['task'];
        $this->updateTestStat(self::MODEL);
        $emqtt_instance->publish($topic, json_encode(self::$DOWNLOAD_FIRMWARE_COMMAND));
    }

    public function getWillUpgradeInfo($machine_info)
    {
        if ($machine_info['firmware_version'] == '1.55.09') {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_FIRMWARE_COMMAND['c02'] = [
                'url' => self::BIN5510['url'],
                'size' => self::BIN5510['size'],
                'md5' => self::BIN5510['md5'],
                'upgrade' => 1
            ];
            $firmware = self::BIN5510['hardware_version'];
        } elseif ($machine_info['firmware_version'] == '1.55.10') {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_FIRMWARE_COMMAND['c02'] = [
                'url' => self::BIN5509['url'],
                'size' => self::BIN5509['size'],
                'md5' => self::BIN5509['md5'],
                'upgrade' => 1
            ];
            $firmware = self::BIN5509['hardware_version'];
        } else {
            echo "Firmware version is not test version ...\n";
            return [];
        }

        return [
            'firmware_version' => $firmware,
            'task' => $task
        ];
    }

    public function getDataUser()
    {
        $user = config('iot.mario.data_client');
        return [
            'host' => config('iot.mario.host'),
            'client_id' => $user['client_id'],
            'password' => $user['password'],
            "username" => $user['username']
        ];
    }

    public function getActiveUser()
    {
        $user = config('iot.mario.active_client');
        return [
            'host' => config('iot.mario.host'),
            'client_id' => $user['client_id'],
            'password' => $user['password'],
            "username" => $user['username']
        ];
    }
}