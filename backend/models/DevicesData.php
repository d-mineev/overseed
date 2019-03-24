<?php

namespace backend\models;

use Yii;
use yii\db\Query;
use yii\db\Connection;

/**
 * This is the model class for table "devices_data".
 *
 * @property string $device_id
 * @property double $lat1
 * @property string $lat2
 * @property double $lon1
 * @property string $lon2
 * @property integer $speed
 * @property integer $course
 * @property double $height
 * @property integer $sats
 * @property double $hdop
 * @property integer $inputs
 * @property integer $outputs
 * @property string $adc
 * @property string $ibutton
 * @property string $params
 * @property string $receiving_date
 */
class DevicesData extends AbstractActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'devices_data';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lat1', 'lon1', 'height', 'hdop'], 'number'],
            [['speed', 'course', 'sats', 'inputs', 'outputs', 'objectid'], 'integer'],
            [['ibutton', 'params'], 'string'],
            [['receiving_date'], 'safe'],
            [['device_id', 'adc'], 'string', 'max' => 255],
            [['lat2', 'lon2'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'device_id' => 'Device ID',
            'lat1' => 'Lat1',
            'lat2' => 'Lat2',
            'lon1' => 'Lon1',
            'lon2' => 'Lon2',
            'speed' => 'Speed',
            'course' => 'Course',
            'height' => 'Height',
            'sats' => 'Sats',
            'hdop' => 'Hdop',
            'inputs' => 'Inputs',
            'outputs' => 'Outputs',
            'adc' => 'Adc',
            'ibutton' => 'Ibutton',
            'params' => 'Params',
            'receiving_date' => 'Receiving Date',
        ];
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        $query = new Query();

        return $query
            ->select(['lat1', 'lon1', 'height', 'receiving_date'])
            ->from(self::tableName())
            ->all();
    }

    public static function getAllDate($dateon = '', $dateto = '', $id_objects)
    {

        $query = new Query();
        $query
            ->select(['lat1', 'lon1', 'height', 'receiving_date', 'device_id'])
            ->from(self::tableName())
            ->andWhere('receiving_date>=:receivi_d', array(':receivi_d' => $dateon))
            ->andWhere('receiving_date<=:receivi_d2', array(':receivi_d2' => $dateto));
        if (!empty($id_objects)) {
            // $query->andWhere('device_id=:id',array(':id'=>$id_objects));
            $query->andWhere(['device_id' => $id_objects]);
        };

        $query->orderBy('receiving_date ASC');
        return $query->all();
    }

    public static function getAllDatePaket($dateon = '', $dateto = '', $id_objects)
    {
        $query = new Query();
        /*  $query = new Query();
           $query
              ->select(['lat1', 'lon1', 'height', 'receiving_date', 'device_id'])
              ->from(self::tableName())
              ->andWhere('receiving_date>=:receivi_d',array(':receivi_d'=>$dateon))
              ->andWhere('receiving_date<=:receivi_d2',array(':receivi_d2'=>$dateto));
          if (!empty($id_objects)) {
             // $query->andWhere('device_id=:id',array(':id'=>$id_objects));
              $query->andWhere(['device_id' => $id_objects]);
          };

          $query->orderBy('receiving_date ASC');
          return $query;*/
        $db = new Connection(Yii::$app->db);
        $sql = 'SELECT lat1, lon1,height,receiving_date,device_id FROM devices_data' .
            ' WHERE receiving_date>=:receivi_d AND receiving_date<=:receivi_d2 AND device_id IN (:ids)';
        $posts = $db->createCommand($sql)
            ->bindValue(':receivi_d', $dateon)
            ->bindValue(':receivi_d2', $dateto)
            ->bindValue(':ids', implode(',', $id_objects))
            ->queryAll();
        return $posts;
    }

    public static function getObjectOnline($id)
    {

        if (!empty($id)) {
            $query = new Query();
            $query
                ->select(['lat1', 'lon1'])
                ->from(self::tableName())
                ->andWhere(['device_id' => $id])
                ->orderBy('receiving_date DESC')
                ->limit(1);
        };
        //  file_put_contents('/var/www/over.loc/FileName.txt', print_r($query->all(), true ));
        //   file_put_contents('C:/test123.txt', print_r($id_fields , true));
        return $query->all();


    }

    /* пример запроса ввиде массива после implode... надо смотреть как неравенства записать в такую форму
     * // ...WHERE (`status` = 10) AND (`type` IS NULL) AND (`id` IN (4, 8, 15))
$query->where([
    'status' => 10,
    'type' => null,
    'id' => [4, 8, 15],
]);*/
}

