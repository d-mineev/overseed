<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "technologyproc".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property integer $techntype
 * @property string $technoper
 * @property string $author
 */
class Technologyproc extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'technologyproc';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['techntype'], 'integer'],
            [['technoper', 'author'], 'string'],
            [['name'], 'string', 'max' => 64],
            [['description'], 'string', 'max' => 128]
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
            'techntype' => 'Techntype',
            'technoper' => 'Technoper',
            'author' => 'Author',
        ];
    }
}
