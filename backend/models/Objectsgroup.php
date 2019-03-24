<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "objectsgroup".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $author
 */
class Objectsgroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'objectsgroup';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description', 'author'], 'string'],
            [['name'], 'string', 'max' => 255]
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
            'author' => 'Author',
        ];
    }

    public function getObjects()
    {
        return $this->hasMany(Objects::className(), ['id' => 'id_object'])
            ->viaTable('object_group', ['id_group' => 'id']);
    }
}
