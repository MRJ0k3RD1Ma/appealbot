<?php

namespace backend\controllers;


use common\models\Logs;
use yii\base\BaseObject;
use yii\filters\Cors;
use yii\rest\ActiveController;
use yii\web\Response;
use Yii;


class ChatController extends ActiveController {

    public $modelClass = "common\models\Botusers";

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] =[
            'class'=>Cors::className()
        ];

        $behaviors['formats'] = [
            'class'=>'yii\filters\ContentNegotiator',
            'formats'=>[
                'application/json'=>Response::FORMAT_JSON
            ]
        ];
        return $behaviors;
    }
    public function actions()
    {
        $actions = parent::actions();

        // disable the "delete" and "create" actions
        unset($actions['delete'], $actions['create'],$actions['update'],$actions['view'],$actions['index']);


        return $actions;
    }

    public function actionSethook(){
        return Yii::$app->telegram->setWebhook([
            'url'=>'https://bot.raqamli.uz/api/chats/chat',
            'certificate'=>Yii::$app->basePath.'/backend/web/cert.crt',
        ]);
    }

    public function actionHookstatus(){
        return Yii::$app->telegram->getMe();
    }

    public function actionChat(){
        if(Yii::$app->request->isPost){
            $log = new Logs();
            $log->log = json_encode(Yii::$app->request->post(),true);
            $log->save();
            return $log;
        }else{
            return "Post emas";
        }
    }

}