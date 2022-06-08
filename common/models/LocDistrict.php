<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "loc_district".
 *
 * @property int $id
 * @property string $name
 * @property int $region_id
 * @property int $sort
 *
 * @property LocVillage[] $locVillages
 * @property LocRegion $region
 */
class LocDistrict extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'loc_district';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['region_id'], 'required'],
            [['region_id', 'sort'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['region_id'], 'exist', 'skipOnError' => true, 'targetClass' => LocRegion::className(), 'targetAttribute' => ['region_id' => 'id']],
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
            'region_id' => 'Region ID',
            'sort' => 'Sort',
        ];
    }

    /**
     * Gets query for [[LocVillages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocVillages()
    {
        return $this->hasMany(LocVillage::className(), ['district_id' => 'id']);
    }

    /**
     * Gets query for [[Region]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(LocRegion::className(), ['id' => 'region_id']);
    }
}
