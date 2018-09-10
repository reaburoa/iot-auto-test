<?php

namespace App\Console\Commands\Mario;

use App\Services\ExportService;
use App\Services\MarioService;
use Illuminate\Console\Command;

class MarioExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mario:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '码里奥测试数据导出';

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
        $data = MarioService::getInstance()->getExportData();
        ExportService::getInstance()->export($data, MarioService::MODEL);
    }
}
