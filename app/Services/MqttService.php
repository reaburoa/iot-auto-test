<?php

namespace App\Services;

use App\Core\Service;
use Workerman\Mqtt\Client;
use Workerman\Worker;

class MqttService extends Service
{
    /**
     * 向主题发布消息
     * @param array $user Mqtt服务器的信息、用户
     * @param array $topic_commands 发布消息，topic => command
     */
    public function publish(array $user, array $topic_commands)
    {
        $worker = new Worker();
        $worker->onWorkerStart = function () use ($user, $topic_commands) {
            $host = $user['host'];
            $options = [
                'client_id' => $user['client_id'],
                'password' => $user['password'],
                "username" => $user['username']
            ];
            $client = new Client($host, $options);
            $client->onConnect = function ($client) use ($topic_commands) {
                foreach ($topic_commands as $topic => $command) {
                    $str_command = json_encode($command);
                    echo "Start publish to {$topic} with message {$str_command} ...\n";
                    $client->publish($topic, $str_command);
                }
            };
            $client->connect();
        };
        Worker::runAll();
    }

    /**
     * 订阅数据
     * @param array $user Mqtt服务器的信息、用户
     * @param array $topics 订阅主题
     * @param array $func 接收到消息后的处理逻辑
     */
    public function subscribe(array $user, array $topics, array $func)
    {
        $worker = new Worker();
        $worker->onWorkerStart = function () use ($user, $topics, $func) {
            $host = $user['host'];
            $options = [
                'client_id' => $user['client_id'],
                'password' => $user['password'],
                "username" => $user['username']
            ];
            $client = new Client($host, $options);
            $client->onConnect = function ($client) use ($topics) {
                foreach ($topics as $topic) {
                    $client->subscribe($topic);
                }
            };
            $client->onMessage = function ($topic, $content) use ($func, $client) {
                call_user_func_array($func, [$topic, $content, $client]);
            };
            $client->connect();
        };
        Worker::runAll();
    }
}