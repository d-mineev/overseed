<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "crop_group".
 *
 * @property integer $id_group
 * @property integer $id_crop
 */
class CropGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'crop_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_group', 'id_crop'], 'required'],
            [['id_group', 'id_crop'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_group' => 'Id Group',
            'id_crop' => 'Id Crop',
        ];
    }
}
