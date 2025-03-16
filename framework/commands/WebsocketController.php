<?php

namespace app\commands;

use app\classes\WebsocketConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use yii\console\Controller;
use Workerman\Worker;

class WebsocketController extends Controller
{
    /**
     * @var WebsocketConnection[] $_connections список коннектов
     */
    private array $_connections = [];

    public function actionStart(): void
    {
        $wsWorker = new Worker('websocket://0.0.0.0:2345');

        // Сохраняем только те коннекты, которые передали правильный токен
        $wsWorker->onWebSocketConnect = function (TcpConnection $connection, string $request) {
            $newConnectionData = new WebsocketConnection(
                $connection,
                $request,
                $_GET['token'] ?? null
            );

            if ($newConnectionData->isValidConnection()) {
                $this->_connections[$connection->id] = $newConnectionData;

                echo "New connection: $newConnectionData" . PHP_EOL;
            }
        };

        // Обработка сообщений только от тех пользователей, которые передали валидный токен
        $wsWorker->onMessage = function ($connection, $data) {
            $currentConnection = $this->getConnection($connection->id);

            if (!is_null($currentConnection) && $currentConnection->isValidConnection()) {
                $currentConnection->saveMessage($data);
                echo "Message from connection {$connection->id}: $data" . PHP_EOL;
            }
        };

        // При дисконнекте надо удалить коннект из списка активных
        $wsWorker->onClose = function ($connection) {
            $currentConnection = $this->getConnection($connection->id);

            if (!is_null($currentConnection) && $currentConnection->isValidConnection()) {
                $currentConnection->close();
                echo "Connection closed: {$connection->id}" . PHP_EOL;
            }
        };

        // Ping до коннекта, чтобы не прервалось соединение
        $wsWorker->onWorkerStart = function($wsWorker) {
            $timeInterval = 10;

            Timer::add($timeInterval, function() use ($wsWorker) {
                foreach($this->_connections as $connection) {
                    $connection->connection->send(pack('H*', '890400000000'), true);
                }
            });
        };

        Worker::runAll();
    }

    // Поиск валидного коннекта по ID
    private function getConnection(int $id): ?WebsocketConnection
    {
        return $this->_connections[$id] ?? null;
    }
}