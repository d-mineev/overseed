<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "treiler_technoper".
 *
 * @property integer $id_treiler
 * @property integer $id_technoper
 */
class TreilerTechnoper extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'treiler_technoper';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_treiler', 'id_technoper'], 'required'],
            [['id_treiler', 'id_technoper'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_treiler' => 'Id Treiler',
            'id_technoper' => 'Id Technoper',
        ];
    }
}
