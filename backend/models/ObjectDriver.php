<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "object_driver".
 *
 * @property integer $id_object
 * @property integer $id_driver
 */
class ObjectDriver extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'object_driver';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_object', 'id_driver'], 'required'],
            [['id_object', 'id_driver'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_object' => 'Id Object',
            'id_driver' => 'Id Driver',
        ];
    }
}
