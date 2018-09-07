<?php

namespace App\Console\Commands\Mario;

use App\Models\AutoTest\TestMachineModel;
use App\Services\MarioService;
use App\Services\MqttService;
use Illuminate\Console\Command;

class MarioPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mario:publish {action} {op}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '码里奥推送数据';

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
        $arg = $this->argument('action');
        $command = $this->argument('op');
        global $argv;
        $argv[0] = 'mario:publish';
        $argv[1] = $command;
        $active_user = MarioService::getInstance()->getActiveUser();
        $msn_model = new TestMachineModel();
        $all_msn = $msn_model->getAllMsn(MarioService::MODEL);
        $topics = array_map(function ($val) {
            return MarioService::getInstance()->getSubTopic($val, MarioService::MODEL);
        }, array_column($all_msn, 'msn'));
        switch ($arg) {
            case 'restart':
                $topics_command = array_combine($topics, array_fill(0, count($topics), MarioService::RESTART_COMMAND));
                MqttService::getInstance()->publish($active_user, $topics_command);
                break;
        }
    }
}
