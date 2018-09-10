<?php

namespace App\Console\Commands\Scanbox;

use App\Models\AutoTest\TestMachineModel;
use App\Models\AutoTest\TestTimesModel;
use App\Services\ScanBoxService;
use Illuminate\Console\Command;
use Workerman\Mqtt\Client;
use Workerman\Worker;

class ScanboxTimerCommand extends Command
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
                        echo "There is no test turn machine,and will sleep 5 minutes ...\n";
                        sleep(300);
                        continue;
                    }
                    rsort($keys);
                    $m_times = new TestTimesModel();
                    $times = $keys[0];
                    $ret = $m_times->getByTimes($times);
                    if ($ret && (time() - strtotime($ret->created_at)) >= 90 * 60) {
                        $all_msn = $m_model->getAllMsn(ScanBoxService::MODEL);
                        $msn_list = array_column($all_msn, 'msn');
                        echo "Turn {$times} has timeout (time is: 90 minutes), will restart all and run new turn ...\n";
                    } else {
                        $not_current_turns = $m_model->getNotTurn($times, ScanBoxService::MODEL);
                        $msn_list = array_column($not_current_turns, 'msn');
                        echo "Not Turn {$times}, and machine will restart ...\n";
                    }
                    $topics = array_map(function ($val) {
                        return ScanBoxService::getInstance()->getSubTopic($val, ScanBoxService::MODEL);
                    }, $msn_list);
                    foreach ($topics as $t) {
                        echo "Push to topic {$t} command ".json_encode(ScanBoxService::RESTART_COMMAND)."\n";
                        $client->publish($t, json_encode(ScanBoxService::RESTART_COMMAND));
                    }
                    echo "Will sleep 5 minutes wait for machine restart ...\n";
                    sleep(300);
                }
            };
            $client->connect();
        };
        Worker::runAll();
    }
}
