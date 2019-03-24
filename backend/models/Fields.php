<?php

namespace backend\models;

use backend\helpers\KmlParser;
use Yii;
use yii\db\Connection;
use yii\db\Exception;
use yii\db\Query;
use yii\web\UploadedFile;

/**
 * This is the model class for table "fields".
 *
 * @property integer $id
 * @property integer $type
 * @property string $name
 * @property string $description
 * @property string $coordinates
 * @property string $color
 * @property boolean $addr
 * @property boolean $ride_begin
 * @property boolean $ride_end
 * @property boolean $width
 */
class Fields extends AbstractActiveRecord
{
    const TYPE_POLYGON = 1;
    const TYPE_LINE    = 2;
    const TYPE_CIRCLE  = 3;

    /** @var UploadedFile */
    public $file;
    public $error;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fields';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'name', 'coordinates'], 'required'],
            [['type'], 'integer'],
            [['description', 'coordinates'], 'string'],
            [['addr', 'ride_begin', 'ride_end'], 'boolean'],
            [['name', 'externalid'], 'string', 'max' => 255],
            [['color'], 'string', 'max' => 8].
            [['perimeter' , 'width'], 'real']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'type'        => 'Type',
            'name'        => 'Name',
            'description' => 'Description',
            'coordinates' => 'Coordinates',
            'color'       => 'Color',
            'addr'        => 'Addr',
            'ride_begin'  => 'Ride Begin',
            'ride_end'    => 'Ride End',
            'width'       => 'Width',
            'perimeter'   => 'Perimeter',
            'externalid' => 'Externalid'
        ];
    }

    /**
     * Возвращает названия типа поля
     *
     * @param int $type
     *
     * @return string
     */
    public function getTypeLabel($type = 0)
    {
        $type = $this->type ?: $type;

        switch ($type) {
            case self::TYPE_POLYGON:
                return 'Полигон';
            case self::TYPE_LINE:
                return 'Линия';
            case self::TYPE_CIRCLE:
                return 'Круг';
            default:
                return 'Неизвестно';
        }
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
        $query->from(self::tableName())->select(['id','name','perimeter','type','externalid']);
        if (!$user->can('superadmin')){
            if (!empty($user->identity->fieldsrules)) {
                $objects = explode("|",$user->identity->fieldsrules);
                $ob = [];
                foreach ($objects as $value){
                    if ($value){
                        $ob[] = (int) $value;
                    }
                }
                $query->andWhere(['id' => $ob]);

                $result =  $query->orderBy('id ASC')->all();
                return $this->getType($result);
            } else {
                return array();
            }

        } else {
            $result =  $query->orderBy('id ASC')->all();
      //      file_put_contents('/var/www/over.loc/checkbox.txt', print_r($this->getType($result) , true ));
            return $this->getType($result);
        }

    }

    /**
     * Возвращает данные о поле
     *
     * @return Query
     */
    public function getOne($id)
    {

        $user = Yii::$app->user;
        if (!$user->can('superadmin')) {
            if (!empty($user->identity->fieldsrules)) {
                $objects = explode("|",$user->identity->fieldsrules);
                $ob = [];
                foreach ($objects as $key => $value) {
                    if ($value) {
                        $ob[] = (int)$key;
                    }
                }
                if (in_array($id, $ob)){
                    return $this->getOne2($id);
                } else {
                    return false;
                }

            } else {
                return false;
            }
        } else {
            return $this->getOne2($id);
        }


    }

    private function getOne2($id){

        $query  = new Query();
        $result = $query->createCommand()->setSql(
            '
        SELECT *, '.$this->getTypeLabelSelectQuery().' as typeLabel
        FROM '.self::tableName().'
        WHERE id = '.(int) $id.'
        '
        )->queryOne();

        return $result;
    }



    /**
     * Распарсивание kml файла и сохранение данных о полях в базу данных
     *
     * @param $fileName
     *
     * @return bool
     */
    public function saveFields($fileName)
    {
        $fields = $this->parseKmlFile($fileName);

        $this->removeKmlFile($fileName);

        if (empty($fields)) {
            return false;
        }

        return $this->handleFields($fields);
    }

    /**
     * @return string
     */
    private function getTypeLabelSelectQuery()
    {
        $query = sprintf(
            'CASE WHEN type=%d THEN \'%s\' WHEN type=%d THEN \'%s\' WHEN type=%d THEN \'%s\' ELSE \'%s\' END',
            self::TYPE_POLYGON,
            $this->getTypeLabel(self::TYPE_POLYGON),
            self::TYPE_LINE,
            $this->getTypeLabel(self::TYPE_LINE),
            self::TYPE_CIRCLE,
            $this->getTypeLabel(self::TYPE_CIRCLE),
            $this->getTypeLabel(0)
        );

        return trim($query);
    }

    /**
     * @param Fields[] $fields
     *
     * @return bool
     */
    private function handleFields($fields)
    {
        //array_slice - используется для удаления из массива колонки id
        $fieldsName = array_slice(array_keys($this->getAttributes()), 1);
        $values     = array_slice($this->getFieldsValues($fields, $fieldsName), 1);

        return $this->saveFieldsValuesToDb($fieldsName, $values);
    }

    /**
     * @param $fields
     * @param $fieldsName
     *
     * @return array
     */
    private function getFieldsValues($fields, $fieldsName)
    {
        $values = [];
        foreach ($fields as $field) {
            $value = [];
            foreach ($fieldsName as $fieldName) {
                $value[] = $field->{$fieldName};
            }

            $values[] = $value;
        }

        return $values;
    }

    /**
     * @param $fieldsName
     * @param $values
     *
     * @return bool
     * @throws Exception
     */
    private function saveFieldsValuesToDb($fieldsName, $values)
    {
        /** @var Connection $db */

        $db          = Yii::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()
               ->batchInsert(self::tableName(), $fieldsName, $values)
               ->execute();

            $transaction->commit();

            $lastid =  $this->findlastids(count($values));
            $idfield = [];
            foreach ($lastid as $value) {
                $idfield[] = $value['id'];
            }
            $idfield = implode("|", $idfield);

            $user = Yii::$app->user;
            if (!empty($user->identity->parenttree)) {
                $id = explode("|", $user->identity->parenttree);
                $id = array_reverse($id);
            } else {
                $id = [];
                $id[1] = 1;
            }
            $query  = new Query();
            if($query->createCommand()->setSql('
                UPDATE "user"
SET fieldsrules = (CASE WHEN fieldsrules = \'\' THEN \'|'.$idfield.'|\' ELSE CONCAT(fieldsrules ,\''.$idfield.'|\') END)
   WHERE  parenttree LIKE \'%|'.$id[1].'|%\'
        '
            )->execute())
            {
                return true;

            } else {
                return false;
            }

        } catch (Exception $e) {
            $transaction->rollBack();

            return false;
        }
    }

    /**
     * @param $fileName
     *
     * @return Fields[]|null
     */
    private function parseKmlFile($fileName)
    {
        $parser = new KmlParser();
        $fields = $parser->parser(Yii::$app->params['files']['data'] . $fileName);

        return $fields;
    }

    /**
     * @param $fileName
     */
    private function removeKmlFile($fileName)
    {
        if (file_exists(Yii::$app->params['files']['data'] . $fileName)) {
            unlink(Yii::$app->params['files']['data'] . $fileName);
        }
    }

    public function createColum ($data){
        if(Yii::$app->db->createCommand()->insert(self::tableName(),
            [
                'type' => (int) $data['data']['type'],
                'name' =>  $data['data']['name'],
                'description' => (empty($data['data']['description'])?'':$data['data']['description']),
                'crop' =>  (empty($data['data']['crop'])?0:$data['data']['crop']),
                'color' => (empty($data['data']['color'])?'':$data['data']['color']),
                'addr' => ( empty($data['data']['addr'])?false:$data['data']['addr']),
                'ride_begin' => ( empty($data['data']['ride_begin'])?false:$data['data']['ride_begin']),
                'ride_end' => ( empty($data['data']['ride_end'])?false:$data['data']['ride_end']),
                'width' =>  (empty($data['data']['width'])?0:$data['data']['width']),
                'area' =>  (empty($data['data']['area'])?0:$data['data']['area']),
                'perimeter' =>  (empty($data['data']['perimeter'])?0:$data['data']['perimeter']),
                'coordinates' => (empty($data['data']['coordinates'])?'':$data['data']['coordinates']),
                'externalid'     =>  (empty($data['data']['externalid'])?'':$data['data']['externalid']),

            ])->execute()){
            return true;
        } else {
            return false;
        }

    }

    public function updateColum($id, $data) {

        if(Yii::$app->db->createCommand()->update(self::tableName(),
            [
                'type' => (int) $data['data']['type'],
                'name' =>  $data['data']['name'],
                'description' => (empty($data['data']['description'])?'':$data['data']['description']),
                'crop' =>  (empty($data['data']['crop'])?0:$data['data']['crop']),
                'color' => (empty($data['data']['color'])?'':$data['data']['color']),
                'addr' => ( empty($data['data']['addr'])?false:$data['data']['addr']),
                'ride_begin' => ( empty($data['data']['ride_begin'])?false:$data['data']['ride_begin']),
                'ride_end' => ( empty($data['data']['ride_end'])?false:$data['data']['ride_end']),
                'width' =>  (empty($data['data']['width'])?0:$data['data']['width']),
                'area' =>  (empty($data['data']['area'])?0:$data['data']['area']),
                'perimeter' =>  (empty($data['data']['perimeter'])?0:$data['data']['perimeter']),
                'coordinates' => (empty($data['data']['coordinates'])?'':$data['data']['coordinates']),
                'externalid'     =>  (empty($data['data']['externalid'])?'':$data['data']['externalid']),

            ], 'id ='.$id)->execute()){
            return true;
        } else {
            return false;
        }
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

    public function findlastids($limit){
        $query = new Query();
        $result = $query
            ->select('id')
            ->from(self::tableName())
            ->orderBy('id DESC')
            ->limit($limit)
            ->all();
        return $result;
    }

    private function getType($arr){
        foreach ($arr as $ar){
            if ($ar['type'] ==1) { $ar['type'] = 'Полигон';}
            elseif ($ar['type'] ==2) {$ar['type'] = 'Линия';}
            elseif ($ar['type'] ==3) {$ar['type'] = 'Круг';}
            else {$ar['type'] = 'Неизвестно';}
        }
        return $arr;
    }



}

