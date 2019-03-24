<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "field_group".
 *
 * @property integer $id_group
 * @property integer $id_field
 */
class FieldGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'field_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_group', 'id_field'], 'required'],
            [['id_group', 'id_field'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_group' => 'Id Group',
            'id_field' => 'Id Field',
        ];
    }
}
