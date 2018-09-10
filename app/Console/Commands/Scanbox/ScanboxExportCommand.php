<?php

namespace App\Console\Commands\Scanbox;

use App\Services\ExportService;
use App\Services\ScanBoxService;
use Illuminate\Console\Command;

class ScanboxExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan_box:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动化测试报告导出';

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
        $data = ScanBoxService::getInstance()->getExportData();
        ExportService::getInstance()->export($data, ScanBoxService::MODEL);
    }
}
