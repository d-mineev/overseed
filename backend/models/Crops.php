<?php

namespace backend\models;

use Yii;
use yii\db\Query;
/**
 * This is the model class for table "crops".
 *
 * @property integer $id
 * @property string $name
 * @property string $color
 * @property string $description
 * @property string $fotosrc
 */
class Crops extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'crops';
    }

    public function behaviors()
    {
        return [
            'image' => [
                'class' => 'rico\yii2images\behaviors\ImageBehave',
            ]
        ];
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 64],
            [['color'], 'string', 'max' => 8],
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
            'color' => 'Color',
            'description' => 'Description',
            
        ];
    }

    public function getAll(){
        
        $query = new Query();
        $query->from(self::tableName())->select(['id','name']);
        if (!Yii::$app->user->can('superadmin')) {
            $query->andWhere(['like', 'author', '|' . Yii::$app->user->identity->id . '|']);
        }
        return $query->orderBy('name ASC')->all();
        
    }

    public function findlastfoto($id){
        $query = new Query();
        $result = $query
            ->from(self::tableName())
            ->Where(['id' => $id])
            ->one();
        return $result['fotosrc'];
    }

    public function updateColum($id, $data)
    {
        if (Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'name' => $data['data']['name'],
                'description' => (empty($data['data']['description']) ? '' : $data['data']['description']),
                'color' => (empty($data['data']['color']) ? '' : $data['data']['color']),
                'fotosrc' => (empty($data['data']['fotosrc']) ? '' : $data['data']['fotosrc']),
                'cropgroup' => (empty($data['data']['cropgroup']) ? '' : $data['data']['cropgroup']),

            ], 'id =' . $id)->execute()
        ) {
            return true;
        } else {
            return false;
        }
    }
}
