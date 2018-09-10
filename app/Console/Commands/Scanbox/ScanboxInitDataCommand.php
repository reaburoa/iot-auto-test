<?php

namespace App\Console\Commands\Scanbox;

use App\Services\IotService;
use App\Services\ScanBoxService;
use Illuminate\Console\Command;

class ScanboxInitDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan_box:init_machine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化测试机器数据';

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
            'N202D87S00127',
            'N202D87S00162',
            'N202D87S00147',
            'N202D87S00146',
            'N202D87S00159',
            'N202D87S00099',
            'N202D87S00142',
            'N202D87S00160',
            'N202D87S00105',
            'N202D87S00163',
            'N202D87S00052',
            'N202D87S00060',
            'N202D87S00026',
            'N202D87S00053',
            'N202D87S00153',
            'N202D87S00157',
            'N202D87S00040',
            'N202D87S00072',
            'N202D87S00110',
            'N202D87S00013',
            'N202D87S00069',
            'N202D87S00144',
            'N202D87S00155',
            'N202D87S00091',
            'N202D87S00119',
            'N202D87S00143',
            'N202D87S00094',
            'N202D87S00114',
            'N202D87S00131',
            'N202D87S00138',
            'N202D87S00104',
            'N202D87S00113',
            'N202D87S00107',
            'N202D87S00102',
            'N202D87S00112',
            'N202D87S00156',
            'N202D87S00125',
            'N202D87S00115',
            'N202D87S00120',
            'N202D87S00106',
            'N202D87S00097',
            'N202D87S00079',
            'N202D87S00082',
            'N202D87S00123',
            'N202D87S00101',
            'N202D87S00100',
            'N202D87S00088',
            'N202D87S00098',
            'N202D87S00087',
            'N202D87S00064',
            'N202D87S00001',
            'N202D87S00035',
            'N202D87S00038',
            'N202D87S00096',
            'N202D87S00050',
            'N202D87S00027',
            'N202D87S00129',
            'N202D87S00047',
            'N202D87S00058',
            'N202D87S00073',
        ];
        $total = IotService::getInstance()->initTestMachine($msn_list, ScanBoxService::MODEL);

        echo "Init data finish, all machine ".count($msn_list)." init ".$total." success.\n";
    }
}
