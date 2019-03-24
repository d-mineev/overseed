<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "object_treiler".
 *
 * @property integer $id_object
 * @property integer $id_treiler
 * @property boolean $stack
 * @property double $lat1
 * @property double $lon1
 * @property string $receiving_date
 */
class ObjectTreiler extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'object_treiler';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_object', 'id_treiler'], 'integer'],
            [['stack'], 'boolean'],
            [['lat1', 'lon1'], 'number'],
            [['receiving_date'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_object' => 'Id Object',
            'id_treiler' => 'Id Treiler',
            'stack' => 'Stack',
            'lat1' => 'Lat1',
            'lon1' => 'Lon1',
            'receiving_date' => 'Receiving Date',
        ];
    }
}
