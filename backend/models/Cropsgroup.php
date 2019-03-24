<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "cropsgroup".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $author
 */
class Cropsgroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cropsgroup';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description', 'author'], 'string'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'author' => 'Author',
        ];
    }

    public function getCrops()
    {
        return $this->hasMany(Crops::className(), ['id' => 'id_crop'])
            ->viaTable('crop_group', ['id_group' => 'id']);
    }
}
