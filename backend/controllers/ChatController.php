<?php

namespace backend\controllers;


use common\models\Botusers;
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
            'url'=>'https://bot.e-murojaat.uz/api/chats/chat',
            'certificate'=>Yii::$app->basePath.'/backend/web/cert.crt',
        ]);
    }

    public function actionHookstatus(){
        return Yii::$app->telegram->getMe();
    }

    public function actionChat(){
        if(Yii::$app->request->isPost and $post = Yii::$app->request->post()){
            $log = new Logs();
            $log->log = json_encode(Yii::$app->request->post(),true);
            $log->save();
            $chatId = $post['message']['chat']['id'];
//            $message = isset($post['message']['text']) ? $post['message']['text'] : '050505';
            $start = false;
            if(array_key_exists('text',$post['message']) and $message = $post['message']['text']){
                if($message == '/start'){
                    $this->start($chatId);
                    $start = true;
                }else{
                    if($this->checkChat($chatId)){

                        $user = $this->getUser($chatId);
                        $position = $user->position;
                        if($position != 0){
                            if($message == ''){

                            }
                        }
                        if($position == 0){
                            // Bosh sahifa
                            $commond = [
                                1=>'Tuman mahallaga murojaat qilish',
                                2=>'Tuman hokimiga murojaat qilish',
                                3=>'Viloyat mahallaga murojaat qilish',
                                4=>'Viloyat hokimiga murojaat qilish',
                            ];
                            if($pos = array_search($message,$commond,true)){
                                $user->position = $pos;
                                $user->save(false);
                            }

                            $this->sendCommand($chatId,$commond[$user->position]);


                        }elseif($position == 1){
                            // tuman mahalla
                        }elseif($position == 2){
                            // tuman hokimi
                        }elseif($position == 3){
                            // Viloyat mahalla
                        }elseif($position == 4){
                            // Viloyat hokimi
                        }

                    }else{
                        $this->accessDeny($chatId);
                    }
                }

                exit;
            }elseif(array_key_exists('contact',$post['message'])){
                $log = new Logs();
                $log->log = "contact true";
                $log->save();
                $phone = $post['message']['contact']['phone_number'];
                $username = isset($post['message']['from']['username'])?$post['message']['from']['username']:'-';
                if($user = Botusers::findOne(['phone_number'=>$phone])){
                    $log = new Logs();
                    $log->log = "user found true";
                    $log->save();
                    $user->username = $username;
                    $user->chat_id = $chatId;
                    $user->status = 1;
                    if($user->save(false)){
                        $log = new Logs();
                        $log->log = "user save true";
                        $log->save();
                        $this->success($chatId,$user->type_id);
                    }
                }else{
                    $this->accessDeny($chatId);
                }
            }

            return $log;
        }else{
            return "null";
        }
    }

    public function getUser($chatId){
        return Botusers::findOne(['chat_id'=>$chatId]);
    }

    public function sendCommand($chatId,$position){
        $text = "Xabaringizni yozing:";
        $reply_markup = [
            'one_time_keyboard'=>true,
            'resize_keyboard'=>true,
            'keyboard'=>[
                [$position],
                ['Bosh menuga qaytish'],
            ]
        ];
        Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text'=>'Xabaringizni yozing:',
            'parse_mode'=>'Markdown',
            'reply_markup' => json_encode($reply_markup)
        ]);
        return true;
    }

    public function start($chatId){

        Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text'=>'Assalomu aleykum Xorazm viloyati hokimining Mahalla raislari uchun yaratilgan hokimga murojaat qilish botiga (@mfydanmurojaatbot) xush kelibsiz! 
Tizimdan foydalanish uchun telefon raqamingizni yuboring. (Pastdagi tugma bosiladi)',
            'parse_mode'=>'Markdown',
            'reply_markup' => json_encode([
                'one_time_keyboard'=>true,
                'resize_keyboard'=>true,
                'keyboard'=>[
                    [['text'=>'Telefon raqamni yuborish','request_contact'=>true]],
                ]
            ])
        ]);

    }


    public function accessDeny($chatId,$text=null){
        if(!$text){
            $text = "Bundan telefon raqamli foydalanuvchi topilmadi.
Bu botdan foydalanish uchun Mahalla raislariga ruhsat berilgan.

Tushinmovchilik yoki texnik xizmatlar uchun @mdg_admin ga murojaat qiling.";
        }

        Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text'=>$text,
        ]);
    }


    public function success($chatId,$type,$text = null){
        if($type == 1){
            $reply_markup = [
                'one_time_keyboard'=>true,
                'resize_keyboard'=>true,
                'keyboard'=>[
                    ['Tuman mahallaga murojaat qilish'],
                    ['Tuman hokimiga murojaat qilish'],
                    ['Viloyat mahallaga murojaat qilish'],
                    ['Viloyat hokimiga murojaat qilish'],
                ]
            ];
            $text = "Tizimdan foydalanish uchun ruhsat berildi. 
Endilikda siz bot yordamida murojaat yuborishingiz mumkin.";
            Yii::$app->telegram->sendMessage([
                'chat_id' => $chatId,
                'text'=>$text,
                'parse_mode'=>'Markdown',
                'reply_markup' => json_encode($reply_markup)
            ]);
        }else{
            $text = "Tizimdan foydalanish uchun ruhsat berildi. 
Endilikda sizga mahalla raislari tomonidan yuboriladigan murojaatlar kelib tushadi.";
            Yii::$app->telegram->sendMessage([
                'chat_id' => $chatId,
                'text'=>$text,
                'parse_mode'=>'Markdown',
                'reply_markup' => json_encode([
                    'remove_keyboard' => true
                ])
            ]);
        }


    }


    public function checkChat($id){

        if($bot = Botusers::findOne(['chat_id'=>$id])){
            return true;
        }
        return false;
    }

}