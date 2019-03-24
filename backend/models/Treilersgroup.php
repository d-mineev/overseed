<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "treilersgroup".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $author
 */
class Treilersgroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'treilersgroup';
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
    public function getTreilers()
    {
        return $this->hasMany(Treilers::className(), ['id' => 'id_treiler'])
            ->viaTable('treiler_group', ['id_group' => 'id']);
    }
}
