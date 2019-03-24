<?php

namespace backend\models;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "positions".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 */
class Positions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'positions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description'], 'string'],
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
        ];
    }

    public function getAll(){
            $query = new Query();
            $query->from(self::tableName());
            return $query->orderBy('name ASC')->all();
    }

    public function updateColum($id, $data)
    {
        if(Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'name' => $data['data']['name'],
                'description' => (empty($data['data']['description']) ? '' : $data['data']['description'])

            ], 'id =' . $id)->execute()){
            return true;
        } else {
            return false;
        }

    }
     public function getDis(){
             $query = new Query();
             $query->from('user');
             $query->where(['position' => 2]);
             return $query->all();
         }

}
