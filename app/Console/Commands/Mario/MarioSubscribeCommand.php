<?php

namespace App\Console\Commands\Mario;

use App\Models\AutoTest\TestMachineModel;
use App\Services\MarioService;
use App\Services\MqttService;
use Illuminate\Console\Command;

class MarioSubscribeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mario:subscribe {op}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '码里奥接收数据';

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
        $argv[0] = 'mario:subscribe';
        $argv[1] = $command;
        unset($argv[2]);
        $data_user = MarioService::getInstance()->getDataUser();
        $msn_model = new TestMachineModel();
        $all_msn = $msn_model->getAllMsn(MarioService::MODEL);
        $m_topics = array_map(function ($val) {
            return MarioService::getInstance()->getPubTopic($val, MarioService::MODEL);
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
            if (isset($content['s01'])) { // 终端信息
                $ret = MarioService::getInstance()->dealS01($msn, $content['s01']);
                MarioService::getInstance()->upgrade($msn, $ret, $emqtt_instance);
            } elseif (isset($content['s02'])) {

            } elseif (isset($content['s03'])) { // 设置推送

            } elseif (isset($content['s04'])) {
                $ret = MarioService::getInstance()->dealS04($msn, $content, MarioService::MODEL);
            }
        } elseif ($ar_topic[0] == '$SYS') {
            $msn = $ar_topic[4];
            $online_state = $ar_topic[count($ar_topic) - 1] == 'connected' ? 1 : 0;
            MarioService::getInstance()->updateOnlineState($msn, $online_state, MarioService::MODEL);
        }
    }
}
