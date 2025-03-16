<?php

namespace app\commands;

use app\models\Users;
use Yii;
use yii\console\Controller;
use yii\db\Exception;

class UsersController extends Controller
{
    /**
     * @throws Exception|\Throwable
     */
    public function actionCreate(): void
    {
        $params = Yii::$app->request->getParams();

        if (!isset($params[1]) || !isset($params[2])) {
            throw new Exception('You don\'t put login or password');
        }

        if (Users::checkLogin($params[1])) {
            throw new Exception('User with this login already exists');
        }

        $user = Users::register($params[1], $params[2]);

        if ($user->hasErrors()) {
            throw new Exception($user->getErrorSummary(true)[0]);
        }

        print_r("User {$params[1]} successfully created");
    }
}