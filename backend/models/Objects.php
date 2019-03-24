<?php

namespace backend\models;


use Yii;
use yii\db\Query;

/**
 * This is the model class for table "drivers".
 *
 * @property integer $id
 * @property varchar $name
 * @property integer $mobile
 * @property text $description
 * @property varchar $fotosrc
 * @property integer $object

 */
class Objects extends AbstractActiveRecord
{
    public $error;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'objects';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            [['name', 'externalid'], 'string', 'max' => 255],
            [['type'], 'integer', 'max' => 11],
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
            'type' => 'Type',
            'odometer' => 'Odometer',
            'fuel' => 'Fuel',
            'externalid' => 'Externalid',
            'color' => 'Color'
        ];
    }

    /**
     * Возвращает данные обо всех полях
     *
     * @return Query
     */
    public function getAll()
    {
        $user = Yii::$app->user;
        $query = new Query();
        $query->from(self::tableName())->select(['id','name','externalid']);
        if (!$user->can('superadmin')){
            if (!empty($user->identity->objectsrules)) {
                $objects =  explode("|",$user->identity->objectsrules);
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
            return $query->orderBy('id ASC')->all();
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

    public function getOneExternal($id)
    {
        $query  = new Query();
        $result = $query->createCommand()->setSql(
            '
        SELECT *
        FROM '.self::tableName().'
        WHERE externalid = '. $id.'
        '
        )->queryOne();

        return $result;
    }

    public function getExternal( $id){
        $query = new Query();
        $query
        ->from(self::tableName())
       // ->andWhere(['externalid' => $id]);
        ->andWhere('externalid=:rec_d2',array(':rec_d2'=> (string) $id));
        return $query->all();
    }
    public static function getIDtoExternal( $id){
        $query = new Query();
        $query
            ->from(self::tableName())->select(['externalid'])
        // ->andWhere(['externalid' => $id]);
            ->andWhere(['id' => $id]);
        $ss =  $query->all();
        $rr = [];
        foreach($ss as $e){
            $rr[]= $e['externalid'];
        }
        return $rr;
    }

public function updateColum($id, $data) {

        Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'name'          =>  $data['data']['name'],
                'type'          =>  $data['data']['type'],
                'odometer'      =>  (empty($data['data']['odometer'])?0:$data['data']['odometer']),
                'fuel'          =>  (empty($data['data']['fuel'])?0:$data['data']['fuel']),
                'treilerid'     =>  (empty($data['data']['treilerid'])?0:$data['data']['treilerid']),
                'externalid'    =>  (empty($data['data']['externalid'])?'':$data['data']['externalid']),
                'color'         => (empty($data['data']['color'])?'':$data['data']['color']),

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

    public function getDevicesdata()
    {
        return $this->hasMany(DevicesData::className(), ['device_id' => 'externalid']);
    }

    public function getDrivers()
    {
        return $this->hasMany(Drivers::className(), ['id' => 'id_driver'])
            ->viaTable('object_driver', ['id_object' => 'id']);
    }
    public function getTreiler()
    {
        return $this->hasMany(ObjectTreiler::className(), ['id_object' => 'id']);
    }


}