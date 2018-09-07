<?php

namespace App\Services;

use App\Models\AutoTest\TestFirmwareDetailModel;
use App\Models\AutoTest\TestAppDetailModel;
use App\Models\AutoTest\TestMachineModel;

class MarioService extends IotService
{
    const MODEL = 'FR010';

    public static $GET_SERVER_UNIX_TIME_COMMAND = [
        'c01' => ''
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

    const BIN176 = [
        'hardware_version' => '1.7.6',
        'url' => 'test.cdn.sunmi.com/IOT-OTA/15329474538827.bin',
        'size' => 2211904,
        'md5' => '30b6127c54cc85dcc78fa31f8855fb3b'
    ];

    const BIN276 = [
        'hardware_version' => '2.7.6',
        'url' => 'test.cdn.sunmi.com/IOT-OTA/15329474645101.bin',
        'size' => 2211904,
        'md5' => 'ddad0b2298248ad4a06d9643b28abc2b'
    ];

    const BIN376 = [
        'hardware_version' => '3.7.6',
        'url' => 'test.cdn.sunmi.com/IOT-OTA/15329474768283.bin',
        'size' => 2211904,
        'md5' => '2c8dadb6b52fa93505e915dfa55ef11f'
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
        if ($data['bv'] != $machine_info['firmware_version'] || $data['appv'] != $machine_info['app_version']) {
            $machine = [
                'firmware_version' => $data['bv'],
                'app_version' => $data['appv'],
                'updated_at' => $now,
            ];
            $ret = $m_model->updateByMsn($msn, self::MODEL, $machine);
            $machine_info['firmware_version'] = $data['bv'];
            $machine_info['updated_at'] = $now;
        }
        $detail_model = new TestFirmwareDetailModel();
        $last_upgrade_info = $detail_model->getByMsn($msn, self::MODEL);
        $upgrade_state = 1;
        if ($last_upgrade_info && $last_upgrade_info['to_firmware_version'] != $data['bv']) {
            $upgrade_state = 0;
        }
        $test_ret = $detail_model->updateFirmwareUpgradeState($msn, self::MODEL, $data['bv'], $upgrade_state);
        if ($test_ret) {
            $ret = $m_model->incrementTestTimes($msn, self::MODEL);
        }
        $this->updateTestStat(self::MODEL);
        return $ret === false ? false : $machine_info;
    }

    public function upgrade($msn, $machine_info, $emqtt_instance)
    {
        $detail_model = new TestFirmwareDetailModel();
        $last_test = $detail_model->hasUpgradeFirmware($msn, self::MODEL);
        if ($last_test) {
            echo "{$msn} is upgrading ...\n";
            return '';
        }
        $will_grade_info = $this->getWillUpgradeInfo($machine_info);
        if (empty($will_grade_info)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $data = [
            'msn' => $msn,
            'model' => self::MODEL,
            'old_firmware_version' => $machine_info['firmware_version'],
            'to_firmware_version' => $will_grade_info['firmware_version'],
            'firmware_upgrade_start' => $now,
            'firmware_download_state' => -1,
            'firmware_upgrade_state' => -1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $detail_model->insert($data);
        $topic = $this->getSubTopic($msn, self::MODEL);
        self::$DOWNLOAD_FIRMWARE_COMMAND['c02'] = $will_grade_info['task'];
        $emqtt_instance->publish($topic, json_encode(self::$DOWNLOAD_FIRMWARE_COMMAND));
    }

    public function getWillUpgradeInfo($machine_info)
    {
        if (preg_match('/^1\./', $machine_info['firmware_version'])) {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_FIRMWARE_COMMAND['c02'] = [
                'url' => self::BIN276['url'],
                'size' => self::BIN276['size'],
                'md5' => self::BIN276['md5']
            ];
            $firmware = self::BIN276['hardware_version'];
        } elseif (preg_match('/^2\./', $machine_info['firmware_version'])) {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_FIRMWARE_COMMAND['c02'] = [
                'url' => self::BIN376['url'],
                'size' => self::BIN376['size'],
                'md5' => self::BIN376['md5']
            ];
            $firmware = self::BIN376['hardware_version'];
        } elseif (preg_match('/^3\./', $machine_info['firmware_version'])) {
            echo "Will Push Upgrade Task ...\n";
            $task = self::$DOWNLOAD_FIRMWARE_COMMAND['c02'] = [
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