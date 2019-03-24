<?php

namespace backend\models;

use Yii;
use backend\models\Fields;

/**
 * This is the model class for table "fieldsgroup".
 *
 * @property integer $id
 * @property string $description
 * @property string $name
 * @property string $author
 */
class Fieldsgroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fieldsgroup';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['description', 'author'], 'string'],
            [['name'], 'required'],
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
            'description' => 'Description',
            'name' => 'Name',
            'author' => 'Author',
        ];
    }

    public function getFields()
    {
        return $this->hasMany(Fields::className(), ['id' => 'id_field'])
            ->viaTable('field_group', ['id_group' => 'id']);
    }
}
