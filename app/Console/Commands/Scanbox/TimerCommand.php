<?php

namespace App\Console\Commands\Scanbox;

use App\Models\AutoTest\TestMachineModel;
use App\Models\AutoTest\TestTimesModel;
use App\Services\ScanBoxService;
use Illuminate\Console\Command;
use Workerman\Mqtt\Client;
use Workerman\Worker;

class TimerCommand extends Command
{
    public static $restart_times = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan_box:timer {op}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $command = $this->argument('op');
        global $argv;
        $argv[0] = 'scan_box:timer';
        $argv[1] = $command;
        unset($argv[2]);
        $user = ScanBoxService::getInstance()->getActiveUser();
        $worker = new Worker();
        $worker->onWorkerStart = function () use ($user) {
            $host = $user['host'];
            $options = [
                'client_id' => $user['client_id'],
                'password' => $user['password'],
                "username" => $user['username']
            ];
            $client = new Client($host, $options);
            $client->onConnect = function ($client) {
                while (true) {
                    $m_model = new TestMachineModel();
                    $test_turn = $m_model->getMachineTotalGroupByTestTurn(ScanBoxService::MODEL);
                    $test_turn = json_decode(json_encode($test_turn), true);
                    $keys = array_values(array_filter(array_column($test_turn, 'turn_times')));
                    if (empty($keys)) {
                        continue;
                    }
                    $restart_all = false;
                    if (count($keys) >= 2) {
                        echo "Has more than 2 turns is upgrading ...\n";
                        $restart_all = true;
                    } else {
                        $m_times = new TestTimesModel();
                        rsort($keys);
                        $times = $keys[0];
                        $ret = $m_times->getByTimes($times);
                        if ($ret && (time() - strtotime($ret->created_at)) >= 90 * 60) {
                            $restart_all = true;
                        }
                    }
                    if ($restart_all) {
                        if (isset(self::$restart_times[$times])) {
                            sleep(120);
                            continue;
                        }
                        $all_msn = $m_model->getAllMsn(ScanBoxService::MODEL);
                        $topics = array_map(function ($val) {
                            return ScanBoxService::getInstance()->getSubTopic($val, ScanBoxService::MODEL);
                        }, array_column($all_msn, 'msn'));
                        foreach ($topics as $t) {
                            echo "Push to topic {$t} command ".json_encode(ScanBoxService::RESTART_COMMAND)."\n";
                            $client->publish($t, json_encode(ScanBoxService::RESTART_COMMAND));
                        }
                        self::$restart_times[$times] = 1;
                        sleep(300);
                    }
                    sleep(60);
                }
            };
            $client->connect();
        };
        Worker::runAll();
    }
}
