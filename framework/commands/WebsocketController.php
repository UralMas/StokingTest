<?php

namespace app\commands;

use app\classes\WebsocketConnection;
use Workerman\Connection\TcpConnection;
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
            if ($this->hasConnection($connection->id)) {
                echo "Message from connection {$connection->id}: $data" . PHP_EOL;
                $connection->send('Hello ' . $data);
            }
        };

        // При дисконнекте надо удалить коннект из списка активных
        $wsWorker->onClose = function ($connection) {
            if ($this->hasConnection($connection->id)) {
                unset($this->_connections[$connection->id]);
                echo "Connection closed: {$connection->id}" . PHP_EOL;
            }
        };

        Worker::runAll();
    }

    // Поиск валидного коннекта по ID
    private function getConnection(int $id): ?WebsocketConnection
    {
        return $this->_connections[$id] ?? null;
    }

    // Проверка существования валидного коннекта по ID
    private function hasConnection(int $id): bool
    {
        return !is_null($this->getConnection($id));
    }
}