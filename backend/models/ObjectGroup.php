<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "object_group".
 *
 * @property integer $id_group
 * @property integer $id_object
 */
class ObjectGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'object_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_group', 'id_object'], 'required'],
            [['id_group', 'id_object'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_group' => 'Id Group',
            'id_object' => 'Id Object',
        ];
    }
}
