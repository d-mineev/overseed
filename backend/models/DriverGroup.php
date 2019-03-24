<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "driver_group".
 *
 * @property integer $id_group
 * @property integer $id_driver
 */
class DriverGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driver_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_group', 'id_driver'], 'required'],
            [['id_group', 'id_driver'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_group' => 'Id Group',
            'id_driver' => 'Id Driver',
        ];
    }
}
