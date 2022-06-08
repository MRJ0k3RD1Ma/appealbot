<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string|null $username
 * @property string|null $password
 * @property string|null $name
 *
 * @property Botusers[] $botusers
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username'], 'string', 'max' => 255],
            [['password'], 'string', 'max' => 500],
            [['name'], 'string', 'max' => 50],
            [['username'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'name' => 'Name',
        ];
    }

    /**
     * Gets query for [[Botusers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBotusers()
    {
        return $this->hasMany(Botusers::className(), ['user_id' => 'id']);
    }
}
