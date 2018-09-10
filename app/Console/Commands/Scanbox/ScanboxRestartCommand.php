<?php

namespace App\Console\Commands\Scanbox;

use App\Models\AutoTest\TestMachineModel;
use App\Services\MqttService;
use App\Services\ScanBoxService;
use Illuminate\Console\Command;

class ScanboxRestartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan_box:restart {op}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送小闪设备重启';

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
        $argv[0] = 'scan_box:restart';
        $argv[1] = $command;
        $active_user = ScanBoxService::getInstance()->getActiveUser();
        $msn_model = new TestMachineModel();
        $all_msn = $msn_model->getAllMsn(ScanBoxService::MODEL);
        $topics = array_map(function ($val) {
            return ScanBoxService::getInstance()->getSubTopic($val, ScanBoxService::MODEL);
        }, array_column($all_msn, 'msn'));

        $topics_command = array_combine($topics, array_fill(0, count($topics), ScanBoxService::RESTART_COMMAND));
        MqttService::getInstance()->publish($active_user, $topics_command);
    }
}
