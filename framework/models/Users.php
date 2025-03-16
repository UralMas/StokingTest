<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Exception;

// Таблица пользователей
class Users extends ActiveRecord
{
    public const SCENARIO_REGISTER = 'register';
    public const SCENARIO_CONNECT = 'connect';

    private int $id;
    private string $login;
    private string $password;
    private string $token;
    private ?string $last_datetime;

    public static function tableName(): string
    {
        return 'users';
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['login', 'password', 'token'];
        $scenarios[self::SCENARIO_CONNECT] = ['last_datetime'];
        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['login', 'password', 'token'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['last_datetime'], 'required', 'on' => self::SCENARIO_CONNECT],
        ];
    }

    public function getId(): int
    {
        return $this->attributes['id'];
    }

    public function getToken(): string
    {
        return $this->attributes['token'];
    }

    // Поиск пользвателя по токену
    public static function findByToken(string $token): self
    {
        $user = Yii::$app->cache->get($token);

        if (!$user) {
            $user = self::findOne(['token' => $token]);
            Yii::$app->cache->set($token, $user, 60 * 60);
        }

        return $user;
    }

    // Определение - существует ли пользователь с таким же логином
    public static function checkLogin(string $login): bool
    {
        return !empty(self::findOne(['login' => $login]));
    }

    /**
     * Регистрация пользователя
     * @throws \Throwable
     */
    public static function register(string $login, string $password): self
    {
        $user = new self();
        $user->scenario = self::SCENARIO_REGISTER;
        $user->attributes = [
            'login' => $login,
            'password' => $password,
            'token' => sha1("$login:$password"),
        ];

        $user->save();

        return $user;
    }

    /**
     * Подключение поьзвателя к вебсокету
     * @throws Exception
     */
    public function connectToWebsocket(): void
    {
        $this->scenario = self::SCENARIO_CONNECT;
        $this->attributes = [
            'last_datetime' => date('Y-m-d H:i:s'),
        ];
        $this->save();
    }
}