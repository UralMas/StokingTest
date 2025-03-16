<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Exception;

class Messages extends ActiveRecord
{
    public const SCENARIO_NEW = 'new';

    private int $id;
    private int $user_id;
    private string $text;
    private string $created_at;

    public static function tableName(): string
    {
        return 'messages';
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_NEW] = ['user_id', 'text', 'created_at'];
        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['user_id', 'text'], 'required'],
        ];
    }

    /**
     * @throws Exception
     */
    public static function create(int $userId, string $text): self
    {
        $message = new self();
        $message->scenario = self::SCENARIO_NEW;
        $message->attributes = [
            'user_id' => $userId,
            'text' => $text,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $message->save();

        return $message;
    }
}