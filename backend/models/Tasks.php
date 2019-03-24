<?php

namespace backend\models;

use Yii;
use yii\db\Query;
use yii\db\Command;
/**
 * This is the model class for table "tasks".
 *
 * @property integer $id
 * @property string $name
 * @property string $user_name
 * @property string $status
 * @property string $task
 * @property date $date_begin
 * @property date $date_end
 * @property integer $driver
 * @property integer $dispatcher
 * @property integer $field
 */

class Tasks extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tasks';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'user_name', 'status', 'task', 'date_begin', 'date_end', 'driver', 'dispatcer', 'object', 'field'], 'required'],         
            [['name', 'user_name', 'status', 'task'], 'string', 'max' => 255],
		[['date_begin', 'date_end'], 'date'],  
		[['driver', 'dispatcer', 'object', 'field'], 'integer'] 
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
            'user_name' => 'User',
            'status' => 'Status',
            'task' => 'Task',
            'date_begin' => 'Date_begin',
            'date_end' => 'Date_end',
            'driver' => 'Driver',		  
            'dispatcher' => 'Dispatcher',		  
            'object' => 'Object',
            'field' => 'Field',		  
        ];
    }

    public function getAll($type)
    {
//author
        if($type==0){
            $where = '';
        }else{
            $where = ' WHERE author = "'. Yii::$app->user->id.'"';
        }
        $sql = "SELECT * FROM ".self::tableName().$where;
        $query  = new Query();
        $result = $query->createCommand()->setSql($sql)->queryAll();

        return $result;
    }

    public function getOne($id)
    {
     
    } 
	
    public function createTask ($data){
        Yii::$app->db->createCommand()->insert(self::tableName(),
            [
                'name' 		=> (empty($data['data']['name'])?'':$data['data']['name']),
                'user_name' 	=> (empty($data['data']['user_name'])?'':$data['data']['user_name']),
                'status' 	=> (empty($data['data']['status'])?'':$data['data']['status']),
                'task' 		=> (empty($data['data']['task'])?'':$data['data']['task']),
                'date_begin' 	=> (empty($data['data']['date_begin'])?'':$data['data']['date_begin']),
                'date_end' 	=> (empty($data['data']['date_end'])?'':$data['data']['date_end']),
                'driver' 	=> (int)(empty($data['data']['driver'])?'':$data['data']['driver']),
                'dispatcher'	=> (int)(empty($data['data']['dispatcher'])?'':$data['data']['dispatcher']),
                'object' 	=> (int)(empty($data['data']['object'])?'':$data['data']['object']),
                'field'     	=> (int)(empty($data['data']['field'])?'':$data['data']['field']),
                'author'    => Yii::$app->user->id
            ])->execute();

    }	
	
    public function updateTask($id, $data) {

        Yii::$app->db->createCommand()->update(self::tableName(),
            [
			
                'name' 		=> (empty($data['data']['name'])?'':$data['data']['name']),
                'user_name' 	=> (empty($data['data']['user_name'])?'':$data['data']['user_name']),
                'status' 	=> (empty($data['data']['status'])?'':$data['data']['status']),
                'task' 		=> (empty($data['data']['task'])?'':$data['data']['task']),
                'date_begin' 	=> (empty($data['data']['date_begin'])?'':$data['data']['date_begin']),
                'date_end' 	=> (empty($data['data']['date_end'])?'':$data['data']['date_end']),
                'driver' 	=> (int)(empty($data['data']['driver'])?'':$data['data']['driver']),
                'dispatcher'	=> (int)(empty($data['data']['dispatcher'])?'':$data['data']['dispatcher']),
                'object' 	=> (int)(empty($data['data']['object'])?'':$data['data']['object']),
                'field'     	=> (int)(empty($data['data']['field'])?'':$data['data']['field']),

            ], 'id ='.$id)->execute();
    }	
	
}
