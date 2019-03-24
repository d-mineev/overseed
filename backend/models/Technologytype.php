<?php

namespace backend\models;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "technologytype".
 *
 * @property integer $id
 * @property string $name
 * @property string $typework
 * @property string $description
 */
class Technologytype extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'technologytype';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name',  'description'], 'string', 'max' => 64]
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
        ];
    }
}
