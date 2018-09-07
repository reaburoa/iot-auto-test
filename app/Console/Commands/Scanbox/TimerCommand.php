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
        $argv[0] = 'scan_box:subscribe';
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
                    $test_turn = $m_model->getMachineTotalGroupbyTestTurn(ScanBoxService::MODEL);
                    $test_turn = json_decode(json_encode($test_turn), true);
                    $keys = array_values(array_filter(array_keys($test_turn)));
                    $restart_all = false;
                    if (count($keys) >= 2) {
                        echo "Has more than 2 turns is upgrading ...\n";
                        $restart_all = true;
                    } else {
                        $m_times = new TestTimesModel();
                        $times = $keys[0];
                        $ret = $m_times->getByTimes($times);
                        if ($ret && (time() - strtotime($ret->created_at)) >= 90 * 60) {
                            echo "{$times} has test 90 minutes,than all will restart ...\n";
                            $restart_all = true;
                        }
                    }
                    if ($restart_all) {
                        $all_msn = $m_model->getAllMsn(ScanBoxService::MODEL);
                        $topics = array_map(function ($val) {
                            return ScanBoxService::getInstance()->getSubTopic($val, ScanBoxService::MODEL);
                        }, array_column($all_msn, 'msn'));
                        foreach ($topics as $t) {
                            $client->publish($t, json_encode(ScanBoxService::RESTART_COMMAND));
                        }
                    }
                    sleep(60);
                }
            };
            $client->connect();
        };
        Worker::runAll();
    }
}
