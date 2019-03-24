<?php

namespace backend\models;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "rolesbook".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $roles
 */
class Rolesbook extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rolesbook';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description', 'roles'], 'string'],
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
            'roles' => 'Roles',
        ];
    }
    public function getAll(){
        $query = new Query();
        $query->from(self::tableName());
        if (!Yii::$app->user->can('superadmin')) {
            $query->Where(['like', 'author', '|' . Yii::$app->user->identity->id . '|']);
        }
        return $query->all();
    }

    public function updateColum($id, $data)
    {
        if(Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'name' => $data['data']['name'],
                'description' => (empty($data['data']['description']) ? '' : $data['data']['description']),
                'roles' => (empty($data['data']['roles']) ? '' : json_encode($data['data']['roles']))

            ], 'id =' . $id)->execute()){
            return true;
        } else {
            return false;
        }

    }
}
