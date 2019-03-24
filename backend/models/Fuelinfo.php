<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "fuelinfo".
 *
 * @property integer $id
 * @property integer $objectid
 * @property integer $techproc
 * @property double $fuel
 * @property string $author
 */
class Fuelinfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fuelinfo';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['objectid', 'techproc'], 'required'],
            [['objectid', 'techproc'], 'integer'],
            [['fuel'], 'number'],
            [['author'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'objectid' => 'Objectid',
            'techproc' => 'Techproc',
            'fuel' => 'Fuel',
            'author' => 'Author',
        ];
    }
}
