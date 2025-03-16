<?php

namespace app\models;

use yii\db\ActiveRecord;

// Таблица пользователей
class Users extends ActiveRecord
{
    public const SCENARIO_REGISTER = 'register';

    private int $id;
    private string $login;
    private string $password;
    private string $token;
    private ?string $last_ip;
    private ?string $last_datetime;

    public static function tableName(): string
    {
        return 'users';
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['username', 'password', 'token'];
        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['login', 'password', 'token'], 'required'],
        ];
    }

    public function getId(): int
    {
        return $this->attributes['id'];
    }

    // Поиск пользвателя по токену
    public static function findByToken(string $token): self
    {
        return self::findOne(['token' => $token]);
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
        $user = new Users();
        $user->attributes = [
            'login' => $login,
            'password' => $password,
            'token' => sha1("$login:$password")
        ];

        $user->save();

        return $user;
    }
}