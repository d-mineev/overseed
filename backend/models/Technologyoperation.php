<?php

namespace backend\models;

use Yii;
use yii\db\Query;
/**
 * This is the model class for table "technologyoperation".
 *
 * @property integer $id
 * @property string $name
 * @property string $color
 * @property string $description
 */
class Technologyoperation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'technologyoperation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name', 'description'], 'string', 'max' => 64],
            [['color'], 'string', 'max' => 8]
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
            'color' => 'Color',
            'description' => 'Description',
        ];
    }
    public function getAll(){
        $query = new Query();
        $query->from(self::tableName())->select(['id','name']);
        return $query->orderBy('name ASC')->all();
    }

    public function updateColum($id, $data)
    {
        if (Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'name' => $data['data']['name'],
                'description' => (empty($data['data']['description']) ? '' : $data['data']['description']),
                'color' => (empty($data['data']['color']) ? '' : $data['data']['color'])

            ], 'id =' . $id)->execute()
        ) {
            return true;
        } else {
            return false;
        }
    }
}
