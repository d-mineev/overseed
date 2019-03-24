<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "treiler_group".
 *
 * @property integer $id_group
 * @property integer $id_treiler
 */
class TreilerGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'treiler_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_group', 'id_treiler'], 'required'],
            [['id_group', 'id_treiler'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_group' => 'Id Group',
            'id_treiler' => 'Id Treiler',
        ];
    }
}
