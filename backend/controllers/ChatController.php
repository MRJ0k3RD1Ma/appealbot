<?php

namespace backend\controllers;


use aki\telegram\Telegram;
use common\models\Botusers;
use common\models\Logs;
use yii\base\BaseObject;
use yii\debug\models\search\Log;
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

//            $message = isset($post['message']['text']) ? $post['message']['text'] : '050505';
            $start = false;
            if(array_key_exists('message',$post)){

            if(array_key_exists('text',$post['message']) and $message = $post['message']['text']){
                $chatId = $post['message']['chat']['id'];
                if($message == '/start'){
                    $this->start($chatId);
                    $start = true;
                    exit;
                }else{
                    if($this->checkChat($chatId)){
                        $commond = [
                            1=>'Tuman mahallaga murojaat qilish',
                            2=>'Tuman hokimiga murojaat qilish',
                            3=>'Viloyat mahallaga murojaat qilish',
                            4=>'Viloyat hokimiga murojaat qilish',
                        ];
                        $user = $this->getUser($chatId);
                        $position = $user->position;
                        if($user->type_id == 1){
                            if(in_array($message,$commond)){
                                if($pos = array_search($message,$commond,true)){
                                    $user->position = $pos;
                                    $user->save(false);
                                    $this->sendCommand($chatId,$commond[$user->position]);
                                }
                                exit;
                            }
                            if($message == 'Bosh menuga qaytish'){
                                $this->sendHome($chatId);
                                exit;
                            }

                            $up_id = $post['update_id'];
                            $from = $post['message']['from']['id'];
                            $address = $user->village->district->name.' '.$user->village->name.' raisi **'.$user->name.'** dan kelgan xabar:';
                            switch ($user->position){
                                case 4: $this->toHokim($address,$up_id,$from,$message); break;
                            }
                            $this->sendSuccess($chatId);
                            exit;
                        }

                    }else{
                        $this->accessDeny($chatId);
                        exit;
                    }
                }

            }
            elseif(array_key_exists('contact',$post['message'])){
                $chatId = $post['message']['chat']['id'];
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

            }
            elseif(array_key_exists('callback_query',$post)){

                $chatId = $post['callback_query']['from']['id'];
                if($user = $this->getUser($chatId)){

                    $user->message_id = $post['callback_query']['data'];
                    $user->save(false);

                    try {

//                    $this->sendHokimAnswer($post['callback_query']['message']['text'],$post['callback_query']['message']['id'],$chatId);

                        $this->sendHokimReply($chatId,$post['callback_query']['message']['message_id']);
                        $this->sendHokimAnswer($post['callback_query']['id']);
                    }catch (\Exception $e){
                        Yii::$app->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text'=>json_encode($e->getMessage()),
                            'parse_mode'=>'Markdown',
                        ]);
                    }


                    exit;
                }else{
                    Yii::$app->telegram->sendMessage([
                        'chat_id'=>$chatId,
                        'text'=>'Foydalanuvchi topilmadi'
                    ]);
                }


                exit;
            }

            return $log;
        }else{
            return "null";
        }
    }

    public function sendHokimAnswer($cid){
        Yii::$app->telegram->answerCallbackQuery([
            'callback_query_id'=>$cid,
            'show_alert'=>false,
            'text'=>'Javob yozing',
            'cache_time'=>3
        ]);
    }


    /*public function sendHokimAnswer($message,$mess_id,$chatId){

        Yii::$app->telegram->editMessageText([
            'chat_id'=>$chatId,
            'message_id'=>$mess_id,
            'text'=>$message.' javobni yozing',
            'reply_markup' => json_encode([
                'force_reply' => true,
                'selective' => false
            ])
        ]);
    }*/


    public function sendHokimReply($chatId,$calback_mes_id){
        Yii::$app->telegram->sendMessage([
            'chat_id'=>$chatId,
            'text'=>'Ushbu xabarga javob yozing:',
            'reply_to_message_id'=>$calback_mes_id,
            'allow_sending_without_reply'=>true,
            'reply_markup' => json_encode([
                'force_reply' => true,
                'selective' => false
            ])
        ]);
    }


    public function toHokim($address,$upd_id,$from,$text){
// Viloyat hokimi
        $hokim = Botusers::find()->where(['type_id'=>5])->andWhere(['status'=>1])->all();
        foreach ($hokim as $item){
            Yii::$app->telegram->sendMessage([
                'chat_id' => $item->chat_id,
                'text'=>$address.'
'.$text,
                'parse_mode'=>'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => 'Javob yozish', 'callback_data' => $upd_id]
                        ]
                    ],
                ])
            ]);

        }

    }




    public function sendSuccess($chatId){
        Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text'=>'Sizning xabaringiz viloyat hokimiga yuborildi.',
            'parse_mode'=>'Markdown',
        ]);
    }


    public function getUser($chatId){
        return Botusers::findOne(['chat_id'=>$chatId]);
    }
    public function sendMenu($chatId){
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
        $text = "Menuni tanlang";
        Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text'=>$text,
            'parse_mode'=>'Markdown',
            'reply_markup' => json_encode($reply_markup)
        ]);
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

    public function sendHome($chatid){
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
        $text = "Bosh menu";
        Yii::$app->telegram->sendMessage([
            'chat_id' => $chatid,
            'text'=>$text,
            'parse_mode'=>'Markdown',
            'reply_markup' => json_encode($reply_markup)
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