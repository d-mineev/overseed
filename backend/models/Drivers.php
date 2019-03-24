<?php

namespace backend\models;

use Yii;
use yii\db\Query;
use yii\db\Command;
/**
 * This is the model class for table "drivers".
 *
 * @property integer $id
 * @property string $name
 * @property integer $mobile
 * @property string $description
 * @property string $fotosrc
 * @property string $object
 * @property integer $object_id
 */
class Drivers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'drivers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
         //   [['description'], 'string'],
            [['name',   'mobile'], 'string', 'max' => 255]
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
            'mobile' => 'Mobile',
            'description' => 'Description',
            'object' => 'Object'
        ];
    }


    public function getAll()
    {

        $user = Yii::$app->user;
        $query = new Query();
        $query->from(self::tableName())->select(['id','name','externalid']);
        if (!$user->can('superadmin')){
            if (!empty($user->identity->driversrules)) {
                $objects =  explode("|",$user->identity->driversrules);
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
	   
	 if(isset($data['data']['checkedDelPhoto'])) {			
		 if($data['data']['checkedDelPhoto'] == true) {
			 Yii::$app->db->createCommand()->update(self::tableName(),
			 [
				'fotosrc'      =>  '',
			 ], 'id ='.$id)->execute();
		 } else {
		 	if(isset($data['data']['fotosrc']) && $data['data']['fotosrc'] != '') {
				Yii::$app->db->createCommand()->update(self::tableName(),
				 [
					'fotosrc'      =>  $data['data']['fotosrc'],
				 ], 'id ='.$id)->execute();			
			}
		 }
		 
	 } else {	 		
		 Yii::$app->db->createCommand()->update(self::tableName(),
		 [
			'fotosrc'      =>  (empty($data['data']['fotosrc'])?'':$data['data']['fotosrc']),
		 ], 'id ='.$id)->execute();	
		
	 }	    	 	    
	     	
		 
	    
        Yii::$app->db->createCommand()->update(self::tableName(),
            [
             'name'         =>  $data['data']['name'],
             'mobile'       =>  (empty($data['data']['mobile'])?'':$data['data']['mobile']),
             'description'  =>  (empty($data['data']['description'])?'':$data['data']['description']),             
             'object'       =>  (empty($data['data']['object'])?0:$data['data']['object']),
             'externalid'     =>  (empty($data['data']['externalid'])?'':$data['data']['externalid']),

            ], 'id ='.$id)->execute();
    }

    public function findlastfoto($id){
        $query = new Query();
        $result = $query
            ->from(self::tableName())
            ->Where(['id' => $id])
            ->one();
        return $result['fotosrc'];
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

    public function getObjects()
    {
        return $this->hasMany(Objects::className(), ['id' => 'id_object'])
            ->viaTable('object_driver', ['id_driver' => 'id']);
    }
    
}
