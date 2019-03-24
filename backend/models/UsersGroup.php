<?php

namespace backend\models;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "users_group".
 *
 * @property integer $id
 * @property string $name
 * @property string $objectsrules
 * @property string $fieldsrules
 */
class UsersGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['objectsrules', 'fieldsrules','treilersrules'], 'string'],
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
            'objectsrules' => 'Objectsrules',
            'fieldsrules' => 'Fieldsrules',
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
                'objectsrules' => (empty($data['data']['checkboxobject'])?'':$data['data']['checkboxobject']),
                'fieldsrules' => (empty($data['data']['checkboxfield'])?'':$data['data']['checkboxfield']),
                'treilersrules' => (empty($data['data']['checkboxtreiler'])?'':$data['data']['checkboxtreiler']),
                'driversrules' => (empty($data['data']['checkboxdriver'])?'':$data['data']['checkboxdriver'])


        ], 'id =' . $id)->execute()){
            return true;
        } else {
            return false;
        }

    }

}
