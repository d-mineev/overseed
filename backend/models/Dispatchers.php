<?php

namespace backend\models;

use Yii;
use yii\db\Query;
use yii\db\Command;

class Dispatchers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dispatchers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],        
            [['name', 'description'], 'string', 'max' => 255]
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
        return $query->all();
    }

    public function updateColum($id, $data)
    {
        if(Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'name' => $data['data']['name'],
                'description' => (empty($data['data']['description']) ? '' : $data['data']['description']),
                'skype' => (empty($data['data']['skype']) ? '' : $data['data']['skype'])

            ], 'id =' . $id)->execute()){
            return true;
        } else {
            return false;
        }

    }
}
