<?php

namespace app\classes;

use app\models\Users;
use Workerman\Connection\TcpConnection;

class WebsocketConnection
{
    private ?TcpConnection $connection;

    private ?Users $user;

    private ?string $token;

    private ?string $userAgent;
    private ?string $createDate;
    private ?string $closedDate = null;

    public function __construct(TcpConnection $connection, string $request, ?string $token)
    {
        $user = self::getUser($token);

        if (is_null($user)) {
            return;
        }

        $this->createDate = date('Y-m-d H:i:s');
        $this->token = $token;
        $this->connection = $connection;
        $this->user = $user;

        $requestData = preg_split("/[\r\n]/", $request, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($requestData as $requestDataLine) {
            if (str_starts_with($requestDataLine, 'User-Agent: ')) {
                $this->userAgent = substr($requestDataLine, strpos($requestDataLine, ' '));
                break;
            }
        }
    }

    // Проверка валидности подключения
    public function isValidConnection(): bool
    {
        return !is_null($this->connection) && empty($this->closedDate);
    }

    // Формирование данных для сохранения
    public function getConnectionData(): ?array
    {
        return !is_null($this->connection)
            ? [
                'create_date' => $this->createDate,
                'token' => $this->token,
                'user_id' => $this->user->getId(),
                'user_agent' => $this->userAgent,
                'closed_date' => $this->closedDate,
            ]
            : null;
    }

    // Формирование данных для сохранения
    public function close(): void
    {
        $this->closedDate = date('Y-m-d H:i:s');
    }

    // Формирование строки данных законнекченного пользователя
    public function __toString(): string
    {
        $userData = [];

        foreach ($this->getConnectionData() as $key => $value) {
            $userData[] = "$key:$value";
        }

        return implode(';', $userData);
    }

    // Поиск пользвателя по токену
    private static function getUser(?string $token): ?Users
    {
        return !is_null($token) ? Users::findByToken($token) : null;
    }
}