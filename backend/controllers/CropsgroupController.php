<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Crops;
use backend\models\Cropsgroup;
use backend\models\CropGroup;


/**
 * Dispatcher controller
 */
class CropsgroupController extends MainController
{
    public $modelClass = 'backend\models\Cropsgroup';

    /** @var Crops */
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
         //   $array = Cropsgroup::find()->asArray()->orderBy('name ASC')->all();
            $Cropsgroups = Cropsgroup::find()->with([
                'crops' => function ($query){
                    $query->select(['id','name']);
                }
            ])->all();
        } else {
            $Cropsgroups = Cropsgroup::find()->with([
                'crops' => function ($query){
                    $query->select(['id','name']);
                }
            ])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
        }
        if(count($Cropsgroups)>0){
            foreach ($Cropsgroups as $Cropsgroup) {
                $crops_in_groop[$Cropsgroup->id] = $Cropsgroup->crops;
            }
        } else {
            $crops_in_groop = [];
        }

        return [
           // 'cropsgroup' => $array,
            'cropsgroup' => $Cropsgroups,
            'cropsgrouplink' => ArrayHelper::map($Cropsgroups, 'id', 'name'),
            "crops_in_groop" => $crops_in_groop
        ];
    }

    public function actionCreate(){
        $user = Yii::$app->user;
        if ($user->can('cropsadd')OR $user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataCrop = json_decode($postData, true);

            $newdriver = new Cropsgroup();
            $newdriver->name =  $dataCrop['data']['name'];
            $newdriver->description = (empty($dataCrop['data']['description'])?'':$dataCrop['data']['description']);
            $newdriver->author = $user->identity->parenttree . $user->identity->id . "|";

          if($newdriver->save()) {
              if(count($dataCrop['cropsingroup'])>0){
                  $lastID = Cropsgroup::find()->where(['name' => $dataCrop['data']['name']])->orderBy(['id' => SORT_DESC])->one();
                  $Model = new CropGroup();
                  $modelatr = $Model->attributes();
                  CropGroup::deleteAll([$modelatr[0] => (int) $lastID->id]);
                  foreach ($dataCrop['cropsingroup'] as $driver){
                      $rows[] = [$lastID->id,$driver['id']];
                  }
                  if (Yii::$app->db->createCommand()->batchInsert(CropGroup::tableName(), $Model->attributes(), $rows)->execute()){
                      return [
                         'success' => true,
                      ];
                  } else {
                          $this->Model->addError('error', 'Ошибка. Не удалось добавить культурыу в группу');
                          return $this->Model;
                  }
              } else {
                  return [
                      'success' => true,
                  ];
              }

          } else {
                $this->Model->addError('error', 'Ошибка. Не удалось создать группу культуры');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('cropsedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataCrop = json_decode($postData, true);
            $id= (int) $dataCrop['data']['id'];
            if ( Cropsgroup::updateAll(
                [
                    'name' => $dataCrop['data']['name'],
                    'description' => (empty($dataCrop['data']['description']) ? '' : $dataCrop['data']['description']),
                    
                ], ['id' => $id])
            ){
                $Model = new CropGroup();
                $modelatr = $Model->attributes();
                CropGroup::deleteAll([$modelatr[0] => $id]);
                if(count($dataCrop['cropsingroup'])>0){
                    foreach ($dataCrop['cropsingroup'] as $driver){
                        $rows[] = [$id,$driver['id']];
                    }
                    if (Yii::$app->db->createCommand()->batchInsert(CropGroup::tableName(), $Model->attributes(), $rows)->execute()){
                        return [
                            'success' => true,
                        ];
                    }
                    else {
                        $this->Model->addError('error', 'Ошибка. Не удалось добавить культуры в группу');
                        return $this->Model;
                    }
                } else {
                    return [
                        'success' => true,
                    ];
                }

            } else {
                $this->Model->addError('error', 'Ошибка. Не удалось изменить группу культуры');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('cropsdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataCrop = json_decode($postData, true);
            $id= (int) $dataCrop['id'];
            if( Cropsgroup::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось удалить группу культуры');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));