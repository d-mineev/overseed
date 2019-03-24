<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "driversgroup".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $author
 */
class Driversgroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driversgroup';
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
    public function getDrivers()
    {
        return $this->hasMany(Drivers::className(), ['id' => 'id_driver'])
            ->viaTable('driver_group', ['id_group' => 'id']);
    }
}
