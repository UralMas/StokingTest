<?php

namespace app\classes;

use Workerman\Connection\TcpConnection;

class WebsocketConnection
{
    private ?TcpConnection $connection;

    private ?string $token;

    private ?string $userAgent;

    public function __construct(TcpConnection $connection, string $request, ?string $token)
    {
        $user = self::getUser($token);

        if (is_null($user)) {
            return;
        }

        $this->connection = $connection;
        $this->token = $token;

        $requestData = preg_split("/[\r\n]/", $request, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($requestData as $requestDataLine) {
            if (str_starts_with($requestDataLine, 'User-Agent:')) {
                $this->userAgent = substr($requestDataLine, strpos($requestDataLine, ' '));
                break;
            }
        }
    }

    // Проверка валидности подключения
    public function isValidConnection(): bool
    {
        return !is_null($this->connection);
    }

    // Формирование данных для сохранения
    public function getConnectionData(): ?array
    {
        return !is_null($this->connection)
            ? [
                'id' => $this->connection->id,
                'token' => $this->token,
                'user_agent' => $this->userAgent,
            ]
            : null;
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
    private static function getUser(?string $token)
    {
        return !is_null($token) ? $token : null;
    }
}