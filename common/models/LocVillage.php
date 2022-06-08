<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "loc_village".
 *
 * @property int $id
 * @property string $name
 * @property int $district_id
 * @property string $address Manzil
 * @property string|null $phone MFY tel raqami
 * @property int|null $sector_number Sektor raqami
 *
 * @property Botusers[] $botusers
 * @property LocDistrict $district
 */
class LocVillage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'loc_village';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['district_id'], 'required'],
            [['district_id', 'sector_number'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['address', 'phone'], 'string', 'max' => 255],
            [['district_id'], 'exist', 'skipOnError' => true, 'targetClass' => LocDistrict::className(), 'targetAttribute' => ['district_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'district_id' => 'District ID',
            'address' => 'Address',
            'phone' => 'Phone',
            'sector_number' => 'Sector Number',
        ];
    }

    /**
     * Gets query for [[Botusers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBotusers()
    {
        return $this->hasMany(Botusers::className(), ['village_id' => 'id']);
    }

    /**
     * Gets query for [[District]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDistrict()
    {
        return $this->hasOne(LocDistrict::className(), ['id' => 'district_id']);
    }
}
