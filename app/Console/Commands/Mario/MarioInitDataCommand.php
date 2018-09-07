<?php

namespace App\Console\Commands\Mario;

use App\Services\IotService;
use App\Services\MarioService;
use Illuminate\Console\Command;

class MarioInitDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mario:init_machine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化码里奥测试机器';

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
        echo "Start init machine data ...\n";
        $msn_list = [
            'F102P88H00154',
            'F102P88H00148',
            'F102P88H00111',
        ];
        $total = IotService::getInstance()->initTestMachine($msn_list, MarioService::MODEL);
        echo "Init data finish, all machine ".count($msn_list)." init ".$total." success.\n";
    }
}
