<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use DOMDocument;
use backend\models\Objects;
use backend\models\ObjectGroup;
use backend\models\Treilers;
use backend\models\TreilerGroup;
use backend\models\Fields;
use backend\models\FieldGroup;
use backend\models\Crops;
use backend\models\CropGroup;
use backend\models\Drivers;
use backend\models\DriverGroup;
use backend\models\DevicesData;
use yii\helpers\ArrayHelper;

/**
 * Site controller
 */
class KmlController extends Controller
{
    public function actionIndex()
    {
        echo 123;
    }

    public function actionTrack ($filename = null, $zum = null, $time = null)
    {

        if (!empty($filename)){
            $idobj = substr ($filename,  strpos($filename,"_")+1);
            $idobj = substr ($idobj, 0, strpos($idobj,"."));
            $objectmodel = new Objects();
            $treilermodel = new Treilers();
            $fullobject = $objectmodel->getExternal((int)$idobj);
            $treiler = $treilermodel->getOne($fullobject[0]['treilerid']);
            $meters = $treiler['width'];
        }

        if (!empty($fullobject[0]['color'])){
       $color1 = $fullobject[0]['color'];
        } else {
            $color1 ='#cccccc';
        }


        if (file_exists(Yii::$app->params['files']['data'] .'G'.$filename)){
            $coor = file_get_contents(Yii::$app->params['files']['data'] .'G'.$filename);


        } else {
            $coor = '';
        }


        $name = json_encode($fullobject[0]['name']. "\n");

        $kmlOutput = '{
         "type": "FeatureCollection",
         "features":
             [
                {
                    "type": "Feature",
                    "geometry": {
                                "type": "LineString",
                                 "coordinates": [ '.
                                    $coor.
                                ' ]
                    },
                     "properties": {
                        "width": "'.$meters.'",
                        "color": "'.$color1.'",
                        "Description": '.$name.'

                     }
                }
             ]
         }';
       // $kmlOutput = '{ "type": "LineString", "coordinates": [ '.$coor.' ]}';

//        file_put_contents(__DIR__.'/testinfo.txt', print_r($testinfo, true));
        file_put_contents(Yii::$app->params['files']['data'] .'G'.$filename.'.txt',$kmlOutput);
       // header('Content-type: application/vnd.google-earth.kml+xml');
        echo $kmlOutput;

    }

    private function p2m($m,$z)
    {
        if (($z < 0) || ($z > 19)) {
            $z = 14;
        }
        if ($m < 0.1) $m = 1;
        $s = array(
            19 => 0.16,
            18 => 0.31,
            17 => 0.63,
            16 => 1.25,
            15 => 2.50,
            14 => 5,
            13 => 10,
            12 => 20,
            11 => 40,
            10 => 80,
            9 => 160,
            8 => 307.69,
            7 => 625,
            6 => 1250,
            5 => 2500,
            4 => 5263.16,
            3 => 10526.32,
            2 => 21052.63,
            1 => 41666.67,
            0 => 83333.33
        );
//$s[15] = 7;
        return $m / $s[$z];
    }
    private function meters2pixels($meters, $lat, $zoomLevel)
    {
        $EARTH_RADIUS = 6371000;
        $TILE_DIM = 512;
        $map_width = $TILE_DIM * pow(2, $zoomLevel);
        $metersPerPixel = 2 * pi() * $EARTH_RADIUS * cos(deg2rad($lat)) / $map_width;
        return ( $metersPerPixel);
    }
    public function actionFields ($object=null,$type = "false", $ids = null)
    {
        $Center['cord1']['x'] = 0.0;
        $Center['cord1']['y'] = 0.0;
        $Center['cord2']['x'] = 0.0;
        $Center['cord2']['y'] = 0.0;
        $Center['cord3']['x'] = 0.0;
        $Center['cord3']['y'] = 0.0;
        $Center['cord4']['x'] = 0.0;
        $Center['cord4']['y'] = 0.0;
        switch ($object){
            case "geo":

                if((!empty($ids)) AND ($type == "false")) {
                    $answerArray = [];
                    $fields = Fields::find()->select('id,color,name,coordinates')->where(['id' => explode(",", $ids)])->asArray()->all();
                } else if((!empty($ids)) AND ($type == "true")) {
                    $answerArray = [];
                    $Model = new FieldGroup();
                    $modelatr = $Model->attributes();
                    $FieldsId = FieldGroup::find()
                        ->select([$modelatr[1]])
                        ->where([$modelatr[0] => explode(",", $ids)])
                        ->groupBy([$modelatr[1]])
                        ->asArray()
                        ->all();
                    if(count($FieldsId)>0){
                        $fields = Fields::find()->select('id,color,name,coordinates')->where(['id' => ArrayHelper::getColumn($FieldsId, $modelatr[1])])->asArray()->all();
                    } else {
                        $fields = [];
                    }

                }

                break;
            case "crop":
                if((!empty($ids)) AND ($type == "false")) {
                    $answerArray = [];
                    $Crops = Crops::find()->select('id,color,name')->where(['id' => explode(",", $ids)])->asArray()->all();
                    $Croplink = ArrayHelper::map($Crops, 'id', 'color');
                }else if((!empty($ids)) AND ($type == "true")) {
                    $answerArray = [];
                    $Model = new CropGroup();
                    $modelatr = $Model->attributes();
                    $CropsId = CropGroup::find()
                        ->select([$modelatr[1]])
                        ->where([$modelatr[0] => explode(",", $ids)])
                        ->groupBy([$modelatr[1]])
                        ->asArray()
                        ->all();
                    if(count($CropsId)>0){
                        $Crops = Crops::find()->select('id,color,name')->where(['id' => ArrayHelper::getColumn($CropsId, $modelatr[1])])->asArray()->all();
                        $Croplink = ArrayHelper::map($Crops, 'id', 'color');
                    } else {
                        $Crops = [];
                    }

                }
                if (count($Crops)>0){
                    $fields = Fields::find()->select('id,color,name,coordinates,crop')->where(['crop' => ArrayHelper::getColumn($Crops, 'id')])->asArray()->all();
                } else {
                    $fields = [];
                }
                break;

        }
        
            $answerArray["type"] = "FeatureCollection";
            $answerArray["centr"] = ["eto const"];
            $answerArray["features"] = [];
        if (count($fields)>0){


            foreach ($fields as $key => $value) {
                $answerArray["features"][$key]["type"] = "Feature";
                $answerArray["features"][$key]["properties"]["letter"] = "G";

                switch ($object) {
                    case "geo":
                        empty($value['color'])? $answerArray["features"][$key]["properties"]["color"] = "blue" : $answerArray["features"][$key]["properties"]["color"]=$value['color'];
                        break;
                    case "crop":
                        empty($value['crop'])? $answerArray["features"][$key]["properties"]["color"] = "blue" : $answerArray["features"][$key]["properties"]["color"]= $Croplink[$value['crop']];
                        break;
                }

                $answerArray["features"][$key]["properties"]["rank"] = "7";
                $answerArray["features"][$key]["geometry"]["type"] = "Polygon";
               // $answerArray["features"][$key]["geometry"]["coordinates"] = [];
                $value['coordinates'] = explode(",",$value['coordinates']);

                $ar1 = [];
                for ($i = 0; $i < (count($value['coordinates'])/3); $i++){
                    $ar1[] = [(real)$value['coordinates'][3*$i+1],(real)$value['coordinates'][3*$i]];

                    if ($Center['cord1']['y'] < (real)$value['coordinates'][3*$i]) { //max Y
                        $Center['cord1']['x'] = (real)$value['coordinates'][3*$i+1];
                        $Center['cord1']['y'] = (real)$value['coordinates'][3*$i];
                    }
                    if ($Center['cord2']['x'] < (real)$value['coordinates'][3*$i+1]) { //max X
                        $Center['cord2']['x'] = (real)$value['coordinates'][3*$i+1];
                        $Center['cord2']['y'] = (real)$value['coordinates'][3*$i];
                    }
                    if (($Center['cord3']['y'] == 0.0) || ($Center['cord3']['y'] > (real)$value['coordinates'][3*$i])) { //min Y
                        $Center['cord3']['x'] = (real)$value['coordinates'][3*$i+1];
                        $Center['cord3']['y'] = (real)$value['coordinates'][3*$i];
                    }
                    if (($Center['cord4']['x'] == 0.0) || ($Center['cord4']['x'] > (real)$value['coordinates'][3*$i+1])) { //min X
                        $Center['cord4']['x'] = (real)$value['coordinates'][3*$i+1];
                        $Center['cord4']['y'] = (real)$value['coordinates'][3*$i];
                    }


                }
                $answerArray["features"][0]["properties"]["centr"] = json_encode($Center);
             //   $ar1 = [[123.61, -22.14],[123.61,-22.14]];
                $answerArray["features"][$key]["geometry"]["coordinates"][] =$ar1;

            }
        }


          //  file_put_contents('/var/www/over.loc/answer.json', print_r(json_encode($answerArray), true ));

            echo json_encode($answerArray);

    }

    public function actionPoints ($object=null,$type = "false", $ids = null)
    {
        $Center['cord1']['x'] = 0.0;
        $Center['cord1']['y'] = 0.0;
        $Center['cord2']['x'] = 0.0;
        $Center['cord2']['y'] = 0.0;
        $Center['cord3']['x'] = 0.0;
        $Center['cord3']['y'] = 0.0;
        $Center['cord4']['x'] = 0.0;
        $Center['cord4']['y'] = 0.0;

        if((!empty($ids)) and (!empty($object))){
            $answerArray = [];
            switch ($object){
                case "Treilers":
                    if ($type == "false") {
                        $treiler_id = explode(",", $ids);
                    } elseif ($type == "true"){
                        $Model = new TreilerGroup();
                        $modelatr = $Model->attributes();
                        $FieldsId = TreilerGroup::find()
                            ->select([$modelatr[1]])
                            ->where([$modelatr[0] => explode(",", $ids)])
                            ->groupBy([$modelatr[1]])
                            ->asArray()
                            ->all();
                        if(count($FieldsId)>0){
                            $treiler_id = ArrayHelper::getColumn($FieldsId, $modelatr[1]);
                        } else {
                            $treiler_id = [];
                        }
                    }
                    $allTreilers = Treilers::find()->where(['id' => $treiler_id])
                        ->with([
                            'objectstack' => function ($query){
                                $query->where(['lastinfo'=> true]);
                            }
                        ])->asArray()->all();
                    $Answer = [];
                    foreach ($allTreilers as $treiler) {

                        //проверка есть ли упоминание об прицепном
                        if (!empty($treiler['objectstack'][0])){
                            (!isset($treiler['icon']) and empty($treiler['icon'])) ? $treiler['icon'] ='/img/treiler.png': null;
                            //проверка прицеплен ли прицеп
                            if($treiler['objectstack'][0]['stack']){
                                //тянем объект
                                $object = Objects::find()->where(['id' => $treiler['objectstack'][0]['id_object']])
                                    ->with([
                                        'devicesdata' => function ($query){
                                            $query->orderBy('receiving_date DESC')
                                                ->select(['device_id','lat1','lon1','receiving_date'])
                                                ->limit(1)
                                                ->one();
                                        }
                                    ])->asArray()->all();
                                if (!empty($object[0]['devicesdata'])) {
                                    $points = $object[0]['devicesdata'];
                                    $Coord_points =  $this->truecoordinats($points ,$Answer,$Center,$treiler);
                                    $Answer = $Coord_points['Answer'];
                                    $Center = $Coord_points['Center'];

                                }
                            } else {
                                //промежуточная таблица
                                $Coord_points =  $this->truecoordinats($treiler['objectstack'] ,$Answer,$Center,$treiler);
                                $Answer = $Coord_points['Answer'];
                                $Center = $Coord_points['Center'];
                            }
                        }
                    }
                  //  file_put_contents('/var/www/over.loc/answer.txt', print_r($Answer, true ));
                  //  die();
                    break;
                case "Drivers":
                    if ($type == "false") {
                        $driver_id = explode(",", $ids);
                    } elseif ($type == "true"){
                        $Model = new DriverGroup();
                        $modelatr = $Model->attributes();
                        $FieldsId = DriverGroup::find()
                            ->select([$modelatr[1]])
                            ->where([$modelatr[0] => explode(",", $ids)])
                            ->groupBy([$modelatr[1]])
                            ->asArray()
                            ->all();
                        if(count($FieldsId)>0){
                            $driver_id = ArrayHelper::getColumn($FieldsId, $modelatr[1]);
                        } else {
                            $driver_id = [];
                        }
                    }
                    $Answer = [];
                    $object_id = [];
                    $Drivers = Drivers::find()->where(['id' => $driver_id])
                        ->with(['objects'])->asArray()->all();

                    foreach ($Drivers as $Driver){
                        if (!empty($Driver["objects"]) ){
                            foreach ($Driver["objects"] as $object){
                                if(!in_array($object["id"],$object_id)){
                                    $object_id[] = $object["id"];
                                }
                            }
                        }
                    }

                    foreach ($object_id as $item) {
                        $object = Objects::find()->where(['id' => $item])
                            ->with([
                                'devicesdata' => function ($query){
                                    $query->orderBy('receiving_date DESC')
                                        ->select(['device_id','lat1','lon1','receiving_date'])
                                        ->limit(1)
                                        ->one();
                                }
                            ])->asArray()->all();
                        if (!empty($object[0]['devicesdata'])) {
                            (!isset($object[0]['icon']) and empty($object[0]['icon'])) ? $object[0]['icon'] ='/img/123.gif': null;
                            $points = $object[0]['devicesdata'];
                            $Coord_points =  $this->truecoordinats($points ,$Answer,$Center,$object[0]);
                            $Answer = $Coord_points['Answer'];
                            $Center = $Coord_points['Center'];

                        }

                    }

                    file_put_contents('/var/www/over.loc/answer.txt', print_r($Answer, true ));
                    break;
                case "Objects":
                    if ($type == "false") {
                        $object_id = explode(",", $ids);
                    } elseif ($type == "true"){
                        $Model = new ObjectGroup();
                        $modelatr = $Model->attributes();
                        $FieldsId = ObjectGroup::find()
                            ->select([$modelatr[1]])
                            ->where([$modelatr[0] => explode(",", $ids)])
                            ->groupBy([$modelatr[1]])
                            ->asArray()
                            ->all();
                        if(count($FieldsId)>0){
                              $object_id = ArrayHelper::getColumn($FieldsId, $modelatr[1]);
                        } else {
                            $object_id = [];
                        }
                    }
                    $Answer = [];
                    foreach ($object_id as $item) {
                        $object = Objects::find()->where(['id' => $item])
                            ->with([
                                'devicesdata' => function ($query){
                                    $query->orderBy('receiving_date DESC')
                                        ->select(['device_id','lat1','lon1','receiving_date'])
                                        ->limit(1)
                                        ->one();
                                }
                            ])->asArray()->all();
                        if (!empty($object[0]['devicesdata'])) {
                            (!isset($object[0]['icon']) and empty($object[0]['icon'])) ? $object[0]['icon'] ='/img/123.gif': null;
                            $points = $object[0]['devicesdata'];
                            $Coord_points =  $this->truecoordinats($points ,$Answer,$Center,$object[0]);
                            $Answer = $Coord_points['Answer'];
                            $Center = $Coord_points['Center'];

                        }

                    }
             //       file_put_contents('/var/www/over.loc/answer.txt', print_r($Answer, true ));
               //     die();

                    break;
            }
        }


        $answerArray["type"] = "FeatureCollection";
        $answerArray["centr"] = ["eto const"];
        $answerArray["features"] = [];
        if (count($Answer)>0){
            foreach ($Answer as $key => $value) {
                $answerArray["features"][$key]["type"] = "Feature";
                $answerArray["features"][$key]["geometry"]["type"] = "Point";
                $answerArray["features"][$key]["geometry"]["coordinates"] =$value['coordinates'];
                $answerArray["features"][$key]["properties"]["name"] = $value['name'];
                $answerArray["features"][$key]["properties"]["centr"] = json_encode($Center);
                $answerArray["features"][$key]["properties"]["icon"] = $value['icon'];
            }
        }
    //    file_put_contents('/var/www/over.loc/answer.json', print_r(json_encode($answerArray), true ));
        echo json_encode($answerArray);

    }

    private function truecoordinats($points ,$Answer,$Center,$object){
        (real)$points[0]['lat1'] > 99 ? $result['lat'] = (real)substr($points[0]['lat1'], 0, 2) + (real)substr($points[0]['lat1'], 2) / 60 : $result['lat'] = (real)$points[0]['lon1'];
        (real)$points[0]['lon1'] > 99 ? $result['lng'] = (real)substr($points[0]['lon1'], 0, 2) + (real)substr($points[0]['lon1'], 2) / 60 : $result['lng'] = (real)$points[0]['lat1'];

        $Answer[] = [
            'name' => $object['name'],
            'coordinates' => [$result['lng'], $result['lat']],
            'icon' =>$object['icon']
        ];
        if ($Center['cord1']['y'] < (real)$result['lat']) { //max Y
            $Center['cord1']['x'] = (real)$result['lng'];
            $Center['cord1']['y'] = (real)$result['lat'];
        }
        if ($Center['cord2']['x'] < (real)$result['lng']) { //max X
            $Center['cord2']['x'] = (real)$result['lng'];
            $Center['cord2']['y'] = (real)$result['lat'];
        }
        if (($Center['cord3']['y'] == 0.0) || ($Center['cord3']['y'] > (real)$result['lat'])) { //min Y
            $Center['cord3']['x'] = (real)$result['lng'];
            $Center['cord3']['y'] = (real)$result['lat'];
        }
        if (($Center['cord4']['x'] == 0.0) || ($Center['cord4']['x'] > (real)$result['lng'])) { //min X
            $Center['cord4']['x'] = (real)$result['lng'];
            $Center['cord4']['y'] = (real)$result['lat'];
        }
        return [
            'Answer' => $Answer,
            'Center' => $Center,
        ];
    }
}