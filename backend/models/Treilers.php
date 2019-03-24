<?php

namespace backend\models;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "treilers".
 *
 * @property integer $id
 * @property string $name
 * @property integer $width
 * @property string $equipment
 * @property string $work
 */
class Treilers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'treilers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['width'], 'integer'],
            [['name',  'externalid'], 'string', 'max' => 255]
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
            'width' => 'Width',
            'externalid' => 'Externalid'
        ];
    }
/*
    public function getAll()
    {

        $query  = new Query();
        $result = $query->createCommand()->setSql(
            '
        SELECT *
        FROM '.self::tableName().'
        '
        )->queryAll();

        return $result;
    }
*/

    public function getAll()
    {

        $user = Yii::$app->user;
        $query = new Query();
        $query->from(self::tableName())->select(['id','name','externalid','width']);
        if (!$user->can('superadmin')){
            if (!empty($user->identity->treilersrules)) {
                $objects =  explode("|",$user->identity->treilersrules);
                $ob = [];
                foreach ($objects as  $value){
                    if ($value){
                        $ob[] = (int) $value;
                    }
                }
                $query->andWhere(['id' => $ob]);
                return $query->orderBy('id ASC')->all();
            } else {
                return array();
            }

        } else {
            return $query->all();
        }

    }

    public function getOne($id)
    {
        $query  = new Query();
        $result = $query->createCommand()->setSql(
            '
        SELECT *
        FROM '.self::tableName().'
        WHERE id = '.(int) $id.'
        '
        )->queryOne();

        return $result;
    }

    public function updateColum($id, $data) {

        Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'name'        =>  $data['data']['name'],
                'width'      =>  $data['data']['width'],
                'techntype' =>  (empty($data['data']['techntype'])?0:$data['data']['techntype']),
                'externalid'     =>  (empty($data['data']['externalid'])?'':$data['data']['externalid']),

            ], 'id ='.$id)->execute();
    }

    public function findlastid($name){
        $query = new Query();
        $result = $query
            ->from(self::tableName())
            ->Where(['name' => $name])
            ->orderBy('id DESC')
            ->limit(1)
            ->all();
        return $result[0]['id'];
    }
    public function getObjectstack()
    {
        return $this->hasMany(ObjectTreiler::className(), ['id_treiler' => 'id']);
    }

    public function getTechnoper()
    {
        return $this->hasMany(Technologyoperation::className(), ['id' => 'id_technoper'])
            ->viaTable('treiler_technoper', ['id_treiler' => 'id']);
    }
}
