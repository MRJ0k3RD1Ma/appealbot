<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "botusers".
 *
 * @property int $id
 * @property string|null $chat_id
 * @property string $phone_number
 * @property int $village_id
 * @property string $address
 * @property string|null $name
 * @property int|null $user_id
 * @property int|null $type_id
 * @property int|null $status
 *
 * @property Type $type
 * @property Users $user
 * @property LocVillage $village
 */
class Botusers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'botusers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['village_id'], 'required'],
            [['village_id', 'user_id', 'type_id', 'status'], 'integer'],
            [['chat_id', 'phone_number', 'address'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 50],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => Type::className(), 'targetAttribute' => ['type_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['village_id'], 'exist', 'skipOnError' => true, 'targetClass' => LocVillage::className(), 'targetAttribute' => ['village_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_id' => 'Chat ID',
            'phone_number' => 'Phone Number',
            'village_id' => 'Village ID',
            'address' => 'Address',
            'name' => 'Name',
            'user_id' => 'User ID',
            'type_id' => 'Type ID',
            'status' => 'Status',
        ];
    }

    /**
     * Gets query for [[Type]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(Type::className(), ['id' => 'type_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Village]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVillage()
    {
        return $this->hasOne(LocVillage::className(), ['id' => 'village_id']);
    }
}
