<?php
namespace backend\models;

use backend\helpers\WlnParser;
use Yii;
use yii\base\Model;
use yii\db\Query;
use yii\web\UploadedFile;
use backend\models\Objects;
use backend\models\Treilers;
use backend\models\ObjectTreiler;


class ReportForm extends Model
{
    /** @var UploadedFile */
    public $file;
    /** @var integer */
    //  public $width;

    public $object;

    public $field;
    public $typereport;

    /**
     * @param string $filePath
     *
     * @return string
     */
    public function parseWlnFile($filePath)
    {
        $wlnParser = new WlnParser();
        $result = $wlnParser->parser($filePath);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        return $result;
    }

    /**
     * Генерирование отчета
     *
     * @param string $messagesJsonFile
     *
     * @return mixed
     */
    public function generate($messagesJsonFile = null)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $start = microtime(true);
        if (is_null($messagesJsonFile)) {
            $messagesJsonFile = $this->makeMessagesJsonFile();
            $center = $messagesJsonFile['center'];
            $messagesJsonFile = $messagesJsonFile['file'];
        }
        file_put_contents(Yii::$app->params['files']['data'] . 'log_time.txt',
            'makeMessagesJsonFile: ' . (microtime(true) - $start) . ' сек.'."\n\r", FILE_APPEND);
        $start = microtime(true);
        $fieldsJsonFile = $this->getFieldsJsonFile($this->field);
        file_put_contents(Yii::$app->params['files']['data'] . 'log_time.txt',
            'getFieldsJsonFile: ' . (microtime(true) - $start) . ' сек.'."\n\r", FILE_APPEND);
        $data = $this->getReportData($messagesJsonFile, $fieldsJsonFile); /// получаем массив ответов.
        $this->removeFiles($messagesJsonFile, $fieldsJsonFile);
        return [
            'reports' => $data,
            'coordn' => $messagesJsonFile,
            'fieldsC' => $fieldsJsonFile,
            'center' => $center
        ];
    }

    /**
     * @return bool|string
     */
    private function makeMessagesJsonFile()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $dateon = Yii::$app->getRequest()->getBodyParam('TimeAt');
        $dateto = Yii::$app->getRequest()->getBodyParam('TimeTo');
        $timestamp = strtotime($dateon);
        $timestamp2 = strtotime($dateto);
        $dateon = date("Y-m-d H:i:s", $timestamp);
        $dateto = date("Y-m-d H:i:s", $timestamp2);
        $start = microtime(true);
        $devicesData = DevicesData::getAllDate($dateon, $dateto, $this->object);
      // $devicesData = DevicesData::getAllDatePaket($dateon, $dateto, $this->object);
        file_put_contents(Yii::$app->params['files']['data'] . 'log_time.txt',
            'readDB: ' . (microtime(true) - $start) . ' сек.'."\n\r", FILE_APPEND);
        $jsonFileName = [];
        $handleJson = [];
        $getcoord = array();
        $temp = '';

        $Center = [];
        $Center['cord1']['x'] = 0.0;
        $Center['cord1']['y'] = 0.0;
        $Center['cord2']['x'] = 0.0;
        $Center['cord2']['y'] = 0.0;
        $Center['cord3']['x'] = 0.0;
        $Center['cord3']['y'] = 0.0;
        $Center['cord4']['x'] = 0.0;
        $Center['cord4']['y'] = 0.0;

        $start = microtime(true);
        foreach ($devicesData/*->each()*/ as $index => $item) {
            if (((real)$item['lon1'] > 0 && (real)$item['lat1'] > 0)) {
                $result = [];
                $result[$index]['t'] = intval(strtotime($item['receiving_date']));
                (real)$item['lon1'] > 99 ? $result[$index]['x'] = (real)substr($item['lon1'], 0, 2) + (real)substr($item['lon1'], 2) / 60 : $result[$index]['y'] = (real)$item['lon1'];

                (real)$item['lat1'] > 99 ? $result[$index]['y'] = (real)substr($item['lat1'], 0, 2) + (real)substr($item['lat1'], 2) / 60 : $result[$index]['x'] = (real)$item['lat1'];

                $result[$index]['z'] = doubleval($item['height']);
                $result[$index]['s'] = 0;
                $result[$index]['c'] = 0;
                $result[$index]['sc'] = 255;
                $temp1 = $temp;
                if ($temp1!='[' . $result[$index]['x'] . ',' . $result[$index]['y'] . "]") {
                    $handleJson[$item['device_id']][] = $result[$index];
                    $temp = '[' . $result[$index]['x'] . ',' . $result[$index]['y'] . "]";

                    if ($temp != $temp1) $getcoord[$item['device_id']][] = $temp;
                    if ($Center['cord1']['y'] < $result[$index]['y']) { //max Y
                        $Center['cord1']['x'] = $result[$index]['x'];
                        $Center['cord1']['y'] = $result[$index]['y'];
                    }
                    if ($Center['cord2']['x'] < $result[$index]['x']) { //max X
                        $Center['cord2']['x'] = $result[$index]['x'];
                        $Center['cord2']['y'] = $result[$index]['y'];
                    }
                    if (($Center['cord3']['y'] == 0.0) || ($Center['cord3']['y'] > $result[$index]['y'])) { //min Y
                        $Center['cord3']['x'] = $result[$index]['x'];
                        $Center['cord3']['y'] = $result[$index]['y'];
                    }
                    if (($Center['cord4']['x'] == 0.0) || ($Center['cord4']['x'] > $result[$index]['x'])) { //min X
                        $Center['cord4']['x'] = $result[$index]['x'];
                        $Center['cord4']['y'] = $result[$index]['y'];
                    }
                }
            }
        }
        file_put_contents(Yii::$app->params['files']['data'] . 'log_time.txt',
            'fereachDBsel: ' . (microtime(true) - $start) . ' сек.'."\n\r", FILE_APPEND);
        $start = microtime(true);
        foreach ($handleJson as $key => $e) {

            $jsonFileName[$key] = 'messages' . microtime(true) . '_' . "$key" . '.json';
            $handleJson[$key] = fopen(Yii::$app->params['files']['data'] . $jsonFileName[$key], 'w');
            $tempText = '';
            fwrite($handleJson[$key], json_encode($e));
            fclose($handleJson[$key]);
            $file2 = fopen(Yii::$app->params['files']['data'] . 'G' . $jsonFileName[$key], 'w');
            fwrite($file2, implode(",", $getcoord[$key]));
            fclose($file2);
            //file_put_contents(Yii::$app->params['files']['data'] . 'G' . $jsonFileName[$key], implode(",", $getcoord[$key]));
        }
        file_put_contents(Yii::$app->params['files']['data'] . 'log_time.txt',
            'saveFile: ' . (microtime(true) - $start) . ' сек.'."\n\r", FILE_APPEND);
        return array('file' => $jsonFileName, 'center' => $Center); // массив адрессов файлов
    }

    /**
     * Отправляет запрос на сервер для генерации отчета.
     * Возвращает сгенерированный отчет - массив данных по каждому полю
     *
     * @param $messagesJsonFile
     * @param $fieldsJsonFile
     *
     * @return mixed
     */
    private function getReportData($messagesJsonFile, $fieldsJsonFile)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $arrayResponse = [];
        $arrFields = [];
        $jsonFields = file_get_contents(realpath(Yii::$app->params['files']['data'] . $fieldsJsonFile));

        if (!empty($jsonFields)) {
            $jsonFields = json_decode($jsonFields, true);
        }
        $start = microtime(true);
        foreach ($messagesJsonFile as $idobj => $Nfile) {
            $objectsmodel = new Objects();
            $treiler = new Treilers();
            $fullobject = $objectsmodel->getExternal($idobj);

            $objectTreiler = ObjectTreiler::find()->where(['id_object' => $fullobject[0]['id']])->andWhere(['lastinfo'=> true])->all();
            if((isset($objectTreiler[0]["stack"])) and $objectTreiler[0]["stack"]){
                $treiler = $treiler->getOne($objectTreiler[0]["id_treiler"]);
            } else {
                $treiler = [];
                $treiler['width'] = 8;
            }
            ///03.03.2016 13:59
            $area_path = realpath(Yii::$app->params['files']['data'] . $messagesJsonFile[$idobj]);
            $reportF = [];
            foreach ($jsonFields as $key => $e) {

                $geofence_path = realpath(Yii::$app->params['files']['data'] . $fieldsJsonFile);

                $used = new_pointer_double();
                $not_used = new_pointer_double();
                $poly_used = new_pointer_double();

                $width = $treiler['width'];//8.0;
                $grid_size = $treiler['width'] / 2;//8.0;
                $square = json_polygon_size($geofence_path, $key);
                $perimeter = json_field_perimeter($geofence_path, $key);
                json_field_analyse($geofence_path, $key, $area_path, $square, $width, $grid_size, $used, $not_used, $poly_used);

                $reportF[$key] = array(
                    "area_field" => number_format($square, 3),
                    "area_intersection" => number_format(pointer_double_value($used), 3),
                    "area_track_overlay" => number_format(pointer_double_value($poly_used), 3),
                    "area_not_overlay" => number_format(pointer_double_value($not_used), 3),
                    "perimetr" => number_format($perimeter, 3),
                    "fieldname" => $e['name']
                );
                delete_pointer_double($used);
                delete_pointer_double($not_used);
                delete_pointer_double($poly_used);

            }
            $arrayResponse[$idobj] = $reportF;
        }
        file_put_contents(Yii::$app->params['files']['data'] . 'log_time.txt',
            'reportsFields:' . $key . ' ' . (microtime(true) - $start) . ' сек.'."\n\r", FILE_APPEND);
        return $arrayResponse; // массив ответов
    }

    /**
     * Возвращает имя сгенерированого json файла полей
     * @return string
     */
    private function getFieldsJsonFile($fields)
    {
        $fieldsJsonModel = new FieldsJson();
        $fieldsJsonFile = $fieldsJsonModel->makeJson($fields);

        return $fieldsJsonFile;
    }

    /**
     * @param $messagesJsonFile
     * @param $fieldsJsonFile
     */
    private function removeFiles($messagesJsonFile, $fieldsJsonFile)
    {
        if (file_exists(Yii::$app->params['files']['data'] . $fieldsJsonFile)) {
            unlink(Yii::$app->params['files']['data'] . $fieldsJsonFile);
        }
        //массив файлов
        foreach ($messagesJsonFile as $key => $value) {
            if (file_exists(Yii::$app->params['files']['data'] . $messagesJsonFile[$key])) {
                unlink(Yii::$app->params['files']['data'] . $messagesJsonFile[$key]);
            }
        }
    }

    /**
     * @param [] $fieldsIds
     *
     * @return array
     */
    private function getFields($fieldsIds)
    {
        $query = new Query();

        $fields = $query
            ->select(['id', 'name', 'perimeter'])
            ->from(Fields::tableName())
            ->where([
                'id' => $fieldsIds
            ])
            ->all();

        return $this->prepareFields($fields);
    }

    /**
     * @param Fields[] $fields
     *
     * @return array
     */
    private function prepareFields($fields)
    {
        $result = [];
        foreach ($fields as $field) {
            $query = new Query();
            $fieldsDriver = $query
                ->select(['name', 'mobile', 'object'])
                ->from('drivers')
                ->where([
                    'object' => $field['id']
                ])
                ->all();
            if (!empty($fieldsDriver)) {
                foreach ($fieldsDriver as $fieldDriver) {
                    $nameDriver = $fieldDriver['name'];
                    $mobileDriver = $fieldDriver['mobile'];
                    $objectDriver = $fieldDriver['object'];
                }
            } else {
                $nameDriver = "";
                $mobileDriver = "";
                $objectDriver = "";
            }
            $result[$field['id']] = ["name" => $field['name'], "perimeter" => $field['perimeter'], "nameDriver" => $nameDriver, "mobileDriver" => $mobileDriver, "objectDriver" => $objectDriver];
        }

        return $result;
    }

    public function getTreilerTehop($arrId)
    {
        $query = new Query();
        $query
            ->select(['id_treiler'])
            ->from('treiler_technoper');
        if (!empty($arrId)) {
            $query->andWhere(['id_technoper' => $arrId]);
        };
        $query->groupBy('id_treiler');
        $treilers = $query->all();
        $result = [];
        foreach ($treilers as $treiler) {

            $result[] = $treiler['id_treiler'];
        }
        return $result;
    }

    public function getObjectTreiler($arrId)
    {
        $query = new Query();
        $query
            ->select(['id_object'])
            ->from('object_treiler');
        $query->andWhere(['stack' => true]);
        if (!empty($arrId)) {
            $query->andWhere(['id_treiler' => $arrId]);
        };

        $objects = $query->all();
        $result = [];
        foreach ($objects as $treiler) {

            $result[] = $treiler['id_object'];
        }
        return $result;
    }

    public function getCultureFields($arrId)
    {
        $query = new Query();
        $query
            ->select(['id'])
            ->from('fields');
        if (!empty($arrId)) {
            $query->andWhere(['crop' => $arrId]);
        };

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['id'];
        }
        return $result;
    }

    public function getGroupCrops($arrId)
    {
        $query = new Query();
        $query
            ->select(['id_crop'])
            ->from('crop_group');
        if (!empty($arrId)) {
            $query->andWhere(['id_group' => $arrId]);
        };

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['id_crop'];
        }
        return $result;
    }

    public function getGroupTreilers($arrId)
    {
        $query = new Query();
        $query
            ->select(['id_treiler'])
            ->from('treiler_group');
        if (!empty($arrId)) {
            $query->andWhere(['id_group' => $arrId]);
        };

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['id_treiler'];
        }
        return $result;
    }

    public function getGroupObjects($arrId)
    {
        $query = new Query();
        $query
            ->select(['id_object'])
            ->from('object_group');
        if (!empty($arrId)) {
            $query->andWhere(['id_group' => $arrId]);
        };

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['id_object'];
        }
        return $result;
    }

    public function getGroupFields($arrId)
    {
        $query = new Query();
        $query
            ->select(['id_field'])
            ->from('field_group');
        if (!empty($arrId)) {
            $query->andWhere(['id_group' => $arrId]);
        };

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['id_field'];
        }
        return $result;
    }

    public function getObjects()
    {
        $query = new Query();
        $query
            ->select(['id'])
            ->from('objects');

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['id'];
        }
        return $result;
    }

    public function getDrivers($arrId)
    {
        $query = new Query();
        $query
            ->select(['object'])
            ->from('drivers');
        if (!empty($arrId)) {
            $query->andWhere(['id' => $arrId]);
        };

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['object'];
        }
        return $result;
    }

    public function getGroupDrivers($arrId)
    {
        $query = new Query();
        $query
            ->select(['id_driver'])
            ->from('driver_group');
        if (!empty($arrId)) {
            $query->andWhere(['id_group' => $arrId]);
        };

        $fields = $query->all();
        $result = [];
        foreach ($fields as $field) {

            $result[] = $field['id_driver'];
        }
        return $result;
    }

    public function getCentermap($id_fields)
    {
        $query = new Query();
        $query
            ->select(['coordinates'])
            ->from(Fields::tableName());
        if (!empty($id_fields)) {
            $query->andWhere(['id' => $id_fields]);
        };

        $fields = $query->all();

        foreach ($fields as $field) {
            $result = [];
            $result = $field['coordinates'];
        }
        $Arrcord = explode(",", $result);
        $count = count($Arrcord) / 3;
        for ($i = 0; $i < $count; $i++) {
            $lat[$i] = $Arrcord[$i * 3];
            $lng[$i] = $Arrcord[$i * 3 + 1];
        }
        $center[0] = array_sum($lat) / count($lat);
        $center[1] = array_sum($lng) / count($lng);
        return $center;

    }


    public function objectonline()
    {


        $id = Yii::$app->getRequest()->getBodyParam('id');
        $object = DevicesData::getObjectOnline($id);
        /*
                (real) $item['lon1']>99 ? $result[$index]['x'] = (real) substr($item['lon1'],0,2) + (real) substr($item['lon1'],2)/60 : $result[$index]['x'] =  (real) $item['lon1'];
                //      $result[$index]['y'] = $item['lon1']
                (real) $item['lat1']>99 ? $result[$index]['y'] = (real) substr($item['lat1'],0,2) + (real) substr($item['lat1'],2)/60 : $result[$index]['y'] = (real)  $item['lat1'];
                // $result[$index]['x'] = doubleval($item['lat1']);
        */
        if (count($object) > 0) {
            (real)$object[0]['lat1'] > 99 ? $result['lat'] = (real)substr($object[0]['lat1'], 0, 2) + (real)substr($object[0]['lat1'], 2) / 60 : $result['lat'] = (real)$object[0]['lon1'];
            (real)$object[0]['lon1'] > 99 ? $result['lng'] = (real)substr($object[0]['lon1'], 0, 2) + (real)substr($object[0]['lon1'], 2) / 60 : $result['lng'] = (real)$object[0]['lat1'];

            return $result;
        } else {
            return false;
        }

    }

    public function savetemplate($idTemplate, $nameTemplate, $strParam, $username)
    {
        $file = __DIR__ . '/../../templatesUsers/' . $username . '_templates';
        $fl = fopen($file, "a+");
        $strRes = $idTemplate . "#" . trim($nameTemplate) . "#" . $strParam . "\r\n";
        fwrite($fl, $strRes);
        fclose($fl);
        return "Шаблон добавлен!";
    }

    public function edittemplate($idTemplate, $nameTemplate, $strParam, $username)
    {

        $file = __DIR__ . '/../../templatesUsers/' . $username . '_templates';

        $contentFile = file($file);

        for ($i = 0; $i < count($contentFile); $i++) {
            list($id, $name,) = explode('#', $contentFile[$i]);
            if ($id == $idTemplate && $name == $nameTemplate) {
                $contentFile[$i] = $id . "#" . $name . "#" . $strParam . "\r\n";
            }
        }
        file_put_contents($file, $contentFile);
        return "Шаблон отредактирован!";
    }

    public function gettemplate($username)
    {
        $file = __DIR__ . '/../../templatesUsers/' . $username . '_templates';
        if (file_exists($file)) {
            $fl = fopen($file, "r");
            $line = fgets($fl, 1024);
            $arr = array();
            if (!$line) {
                return false;
            } else {
                $i = 0;
                while (!feof($fl)) {
                    list($id, $name) = explode('#', $line);
                    $arr[$i] = array();
                    $arr[$i]['id'] = $id;
                    $arr[$i]['name'] = $name;
                    $line = fgets($fl, 1024);
                    $i++;
                }
            }
            fclose($fl);
            return $arr;
        } else {
            return [];
        }
    }

    public function getdatafields($username, $nameTemplate)
    {
        $file = __DIR__ . '/../../templatesUsers/' . $username . '_templates';
        if (file_exists($file)) {
            $fl = fopen($file, "r");
            $line = fgets($fl, 1024);
            if (!$line) {
                return "";
            } else {

                while (!feof($fl)) {
                    list(, $name, $strParam) = explode('#', $line);
                    if ($nameTemplate == $name) {
                        fclose($fl);
                        return $strParam;
                    }
                    $line = fgets($fl, 1024);
                }

            }
            fclose($fl);
            return "";
        } else {
            return "";
        }
    }

    public function deletetemplate($idTemplate, $nameTemplate, $username)
    {
        $file = __DIR__ . '/../../templatesUsers/' . $username . '_templates';
        $arr = array();
        $contentFile = file($file);

        for ($i = 0; $i < count($contentFile); $i++) {
            list($id, $name,) = explode('#', $contentFile[$i]);
            if ($id == $idTemplate && $name == $nameTemplate) {
                unset($contentFile[$i]);
                sort($contentFile);
                for ($j = 0; $j < count($contentFile); $j++) {
                    if ($contentFile[$j] != '') {
                        list(, $name, $strParam) = explode('#', $contentFile[$j]);
                        $contentFile[$j] = ($j + 1) . "#" . $name . "#" . $strParam;
                        $arr[$j] = array();
                        $arr[$j]['id'] = ($j + 1);
                        $arr[$j]['name'] = $name;
                    }
                }

                file_put_contents($file, $contentFile);
                $result = ["Шаблон удален!", $arr];
                return $result;
            }
        }
        return false;

    }

}

