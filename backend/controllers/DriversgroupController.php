<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Drivers;
use backend\models\Driversgroup;
use backend\models\DriverGroup;


/**
 * Dispatcher controller
 */
class DriversgroupController extends MainController
{
    public $modelClass = 'backend\models\Driversgroup';

    /** @var Drivers */
    private $Model;

    public function init ()
    {
        parent::init();

        $this->Model = new $this->modelClass();
    }

    /**
     * @inheritdoc
     */
    public function actions ()
    {
        $actions = parent::actions();

        unset($actions['index']);
        unset($actions['view']);
        unset($actions['delete']);
        unset($actions['create']);
	  unset($actions['edit']);

        return $actions;
    }


    public function behaviors ()
    {
        $behaviors                       = parent::behaviors();
        $behaviors['authenticator']      = ArrayHelper::merge(
            $behaviors['authenticator'],
            [
                'only' => ['upload'],
            ]
        );
        $behaviors['access']             = ArrayHelper::merge(
            $behaviors['access'],
            [
                'only' => ['upload'],
            ]
        );
        $behaviors['access']['rules'][0] = ArrayHelper::merge(
            $behaviors['access']['rules'][0],
            [
                'actions' => ['upload'],
            ]
        );

        return $behaviors;
    }

    public function actionIndex ()
    {
        if (Yii::$app->user->can('superadmin')) {
         //   $array = Driversgroup::find()->asArray()->orderBy('name ASC')->all();
            $Driversgroups = Driversgroup::find()->with([
                'drivers' => function ($query){
                    $query->select(['id','name']);
                }
            ])->all();
        } else {
            $Driversgroups = Driversgroup::find()->with([
                'drivers' => function ($query){
                    $query->select(['id','name']);
                }
            ])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
        }
        if(count($Driversgroups)>0){
            foreach ($Driversgroups as $Driversgroup) {
                $drivers_in_groop[$Driversgroup->id] = $Driversgroup->drivers;
            }
        } else {
            $drivers_in_groop = [];
        }

        return [
           // 'driversgroup' => $array,
            'driversgroup' => $Driversgroups,
            'driversgrouplink' => ArrayHelper::map($Driversgroups, 'id', 'name'),
            "drivers_in_groop" => $drivers_in_groop
        ];
    }

    public function actionCreate(){
        $user = Yii::$app->user;
        if ($user->can('driversadd')OR $user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);

            $newdriver = new Driversgroup();
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->author = $user->identity->parenttree . $user->identity->id . "|";

          if($newdriver->save()) {
              if(count($dataDriver['driversingroup'])>0){
                  $lastID = Driversgroup::find()->where(['name' => $dataDriver['data']['name']])->orderBy(['id' => SORT_DESC])->one();
                  $Model = new DriverGroup();
                  $modelatr = $Model->attributes();
                  DriverGroup::deleteAll([$modelatr[0] => (int) $lastID->id]);
                  foreach ($dataDriver['driversingroup'] as $driver){
                      $rows[] = [$lastID->id,$driver['id']];
                  }
                  if (Yii::$app->db->createCommand()->batchInsert(DriverGroup::tableName(), $Model->attributes(), $rows)->execute()){
                      return [
                         'success' => true,
                      ];
                  } else {
                          $this->Model->addError('error', 'Ошибка. Не удалось добавить водителейу в группу');
                          return $this->Model;
                  }
              } else {
                  return [
                      'success' => true,
                  ];
              }

          } else {
                $this->Model->addError('error', 'Ошибка. Не удалось создать группу водителей');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('driversedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if ( Driversgroup::updateAll(
                [
                    'name' => $dataDriver['data']['name'],
                    'description' => (empty($dataDriver['data']['description']) ? '' : $dataDriver['data']['description']),
                    
                ], ['id' => $id])
            ){
                $Model = new DriverGroup();
                $modelatr = $Model->attributes();
                DriverGroup::deleteAll([$modelatr[0] => $id]);
                if(count($dataDriver['driversingroup'])>0){
                    foreach ($dataDriver['driversingroup'] as $driver){
                        $rows[] = [$id,$driver['id']];
                    }
                    if (Yii::$app->db->createCommand()->batchInsert(DriverGroup::tableName(), $Model->attributes(), $rows)->execute()){
                        return [
                            'success' => true,
                        ];
                    }
                    else {
                        $this->Model->addError('error', 'Ошибка. Не удалось добавить водителей в группу');
                        return $this->Model;
                    }
                } else {
                    return [
                        'success' => true,
                    ];
                }

            } else {
                $this->Model->addError('error', 'Ошибка. Не удалось изменить группу водителей');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('driversdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( Driversgroup::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось удалить группу водителей');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));