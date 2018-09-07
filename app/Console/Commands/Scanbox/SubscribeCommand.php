<?php

namespace App\Console\Commands\Scanbox;

use App\Models\AutoTest\TestMachineModel;
use App\Services\MqttService;
use App\Services\ScanBoxService;
use Illuminate\Console\Command;

class SubscribeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan_box:subscribe {op}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '小闪接收客户端消息';

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
        $data_user = ScanBoxService::getInstance()->getDataUser();
        $msn_model = new TestMachineModel();
        $all_msn = $msn_model->getAllMsn(ScanBoxService::MODEL);
        $m_topics = array_map(function ($val) {
            return ScanBoxService::getInstance()->getPubTopic($val, ScanBoxService::MODEL);
        }, array_column($all_msn, 'msn'));
        $topics = [
            '$SYS/brokers/+/clients/+/disconnected',
            '$SYS/brokers/+/clients/+/connected'
        ];
        $topics = array_merge($m_topics, $topics);
        MqttService::getInstance()->subscribe($data_user, $topics, [$this, 'dealData']);
    }

    public function dealData($topic, $content, $emqtt_instance)
    {
        echo "Receive from {$topic} with data {$content}\n";
        $ar_topic = explode('/', $topic);
        $content = json_decode($content, true);
        if (empty($ar_topic[0]) && $ar_topic[count($ar_topic) - 1] == 'pub') {
            $msn = $ar_topic[2];
            if (isset($content['s01'])) {
                $ret = ScanBoxService::getInstance()->dealS01($msn, $content['s01']);
                ScanBoxService::getInstance()->upgrade($msn, $ret, $emqtt_instance);
            } elseif (isset($content['s02'])) {

            } elseif (isset($content['s03'])) {
                ScanBoxService::getInstance()->dealS03($msn, $content);
            } elseif (isset($content['s04'])) {
                $ret = ScanBoxService::getInstance()->dealS04($msn, $content, ScanBoxService::MODEL);
                $t = ScanBoxService::getInstance()->getSubTopic($msn, ScanBoxService::MODEL);
                $emqtt_instance->publish($t, json_encode(ScanBoxService::RESTART_COMMAND));
            }
        } elseif ($ar_topic[0] == '$SYS') {
            $msn = $ar_topic[4];
            $online_state = $ar_topic[count($ar_topic) - 1] == 'connected' ? 1 : 0;
            ScanBoxService::getInstance()->updateOnlineState($msn, $online_state, ScanBoxService::MODEL);
        }
    }
}
