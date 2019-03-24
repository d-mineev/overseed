<?php
namespace backend\helpers;

use backend\models\DevicesData;
use backend\models\Fields;
use Yii;
use yii\base\Exception;
use yii\db\Connection;
use yii\db\pgsql\QueryBuilder;
use yii\db\Query;

/**
 * Парсер wln файла треков
 */
class WlnParser
{

    /**
     * Парсер wln файла треков
     *
     * @param $filePath - путь к файлу с расширением wln
     *
     * @return Fields[]|null - массив объектов класса Fields или null в случае ошибки обработки
     */
    public function parser($filePath)
    {


        $result = [];
        $index = 0;
        $handle = fopen($filePath, 'r');
        while (($line = fgets($handle)) !== false) {
            $data = explode(';', $line);
            $result[$index]['t'] = intval($data[1]);
            $result[$index]['x'] = doubleval($data[2]);
            $result[$index]['y'] = doubleval($data[3]);

            $tmpData = explode(',', $data[6]);
            $result[$index]['z'] = doubleval(str_replace('ALT:', '', $tmpData[0]));
            $result[$index]['externalid'] = Yii::$app->getRequest()->getBodyParam('objectid');
            $index++;
        }

        if ($this->saveToDb($result)) {
            return true;
        } else {
            return false;
        }


    }
    /*
       public function parser ($filePath)
       {

           $jsonFileName = 'messages_' . microtime(true) . '.json';
           $handleJson   = fopen(Yii::$app->params['files']['data'] . $jsonFileName, 'w');
           if ($handleJson) {
               $result = [];
               $index  = 0;
               $handle = fopen($filePath, 'r');
               while (($line = fgets($handle)) !== false) {
                   $data                = explode(';', $line);
                   $result[$index]['t'] = intval($data[1]);
                   $result[$index]['x'] = doubleval($data[2]);
                   $result[$index]['y'] = doubleval($data[3]);

                   $tmpData             = explode(',', $data[6]);
                   $result[$index]['z'] = doubleval(str_replace('ALT:', '', $tmpData[0]));
                   $result[$index]['objectid'] = Yii::$app->getRequest()->getBodyParam('objectid');
                   $result[$index]['fieldid'] = Yii::$app->getRequest()->getBodyParam('fieldid');
                   $index++;
               }

               fclose($handle);

               fwrite($handleJson, json_encode($result));
               fclose($handleJson);

               $this->saveToDb($result);

               return $jsonFileName;
           } else {
               return false;
           }
       }
   */
    /**
     * @param [] $data
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    /*
    public function batchInsert($table, $columns, $rows)
    {
        $sql = parent::batchInsert($table, $columns, $rows);
        $sql .= 'ON DUPLICATE KEY UPDATE';
        return $sql;
    }
*/
    protected function saveToDb($data)
    {
        $values = $this->prepareValues($data);

        /** @var Connection $db */
        //  $db=Yii::$app->db;
        $db = Yii::$app->getDb();
        $transaction = $db->beginTransaction();
        try {

            if (!isset($values)) {
                $db->createCommand()
                    ->batchInsert(DevicesData::tableName(), ['lat1', 'lon1', 'height', 'receiving_date', 'device_id'], $values)
                    ->execute();
                $transaction->commit();
            }


            return true;
        } catch (Exception $e) {
            $transaction->rollBack();


            return false;
        }
    }


    /**
     * @param [] $data
     *
     * @return array
     */
    private function prepareValues($data)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $result = [];
//////////
        $base = DevicesData::getAllDate(date("Y-m-d H:i:s", $data[0]['t']),
            date("Y-m-d H:i:s", $data[count($data) - 1]['t']),
            $data[0]['externalid']);
        if (count($base) > 0) {
            $resb2 = [];
            foreach ($base as $b2) {
                $resb2[$b2['receiving_date']] = true;
            }
        }

        foreach ($data as $item) {


            if (!isset($resb2[date("Y-m-d H:i:s", $item['t'])])) {
                $result[] = [$item['x'], $item['y'], $item['z'], date("Y-m-d H:i:s", $item['t']), $item['externalid']];
            }

        }
        return $result;

        ///////


        /*
                foreach ($data as $item) {
                   $result[] = [$item['x'], $item['y'], $item['z'], date("Y-m-d H:i:s", $item['t']), $item['externalid']];

                }

                return $result;
        */
    }
}

