<?php
namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use backend\models\Objects;
use backend\models\Objectsgroup;
use backend\models\ObjectGroup;


/**
 * Dispatcher controller
 */
class ObjectsgroupController extends MainController
{
    public $modelClass = 'backend\models\Objectsgroup';

    /** @var Objects */
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
            $Objectsgroups = Objectsgroup::find()->with([
                'objects' => function ($query){
                    $query->select(['id','name']);
                }
            ])->all();
        } else {
            $Objectsgroups = Objectsgroup::find()->with([
                'objects' => function ($query){
                    $query->select(['id','name']);
                }
            ])->where(['like', 'author', "|".Yii::$app->user->identity->id ."|" ])->all();
        }
        if(count($Objectsgroups)>0){
            foreach ($Objectsgroups as $Objectsgroup) {
                $fields_in_groop[$Objectsgroup->id] = $Objectsgroup->objects;
            }
        } else {
            $fields_in_groop = [];
        }

        return [
            'objectsgroup' => $Objectsgroups,
            'objectsgrouplink' => ArrayHelper::map($Objectsgroups, 'id', 'name'),
            "objects_in_groop" => $fields_in_groop
        ];
    }

    public function actionCreate(){
        $user = Yii::$app->user;
        if ($user->can('objectsadd')OR $user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);

            $newdriver = new Objectsgroup();
            $newdriver->name =  $dataDriver['data']['name'];
            $newdriver->description = (empty($dataDriver['data']['description'])?'':$dataDriver['data']['description']);
            $newdriver->author = $user->identity->parenttree . $user->identity->id . "|";

          if($newdriver->save()) {
              if(count($dataDriver['objectsingroup'])>0){
                  $lastID = Objectsgroup::find()->where(['name' => $dataDriver['data']['name']])->orderBy(['id' => SORT_DESC])->one();
                  $Model = new ObjectGroup();
                  $modelatr = $Model->attributes();
                  ObjectGroup::deleteAll([$modelatr[0] => (int) $lastID->id]);
                  foreach ($dataDriver['objectsingroup'] as $field){
                      $rows[] = [$lastID->id,$field['id']];
                  }
                  if (Yii::$app->db->createCommand()->batchInsert(ObjectGroup::tableName(), $Model->attributes(), $rows)->execute()){
                      return [
                         'success' => true,
                      ];
                  } else {
                          $this->Model->addError('error', 'Ошибка. Не удалось добавить объекты в группу');
                          return $this->Model;
                  }
              } else {
                  return [
                      'success' => true,
                  ];
              }

          } else {
                $this->Model->addError('error', 'Ошибка. Не удалось создать группу объектов');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


    public function actionEdit(){
        if (Yii::$app->user->can('objectsedit')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['data']['id'];
            if ( Objectsgroup::updateAll(
                [
                    'name' => $dataDriver['data']['name'],
                    'description' => (empty($dataDriver['data']['description']) ? '' : $dataDriver['data']['description']),
                    
                ], ['id' => $id])
            ){
                $Model = new ObjectGroup();
                $modelatr = $Model->attributes();
                ObjectGroup::deleteAll([$modelatr[0] => $id]);
                if(count($dataDriver['objectsingroup'])>0){
                    foreach ($dataDriver['objectsingroup'] as $field){
                        $rows[] = [$id,$field['id']];
                    }
                    if (Yii::$app->db->createCommand()->batchInsert(ObjectGroup::tableName(), $Model->attributes(), $rows)->execute()){
                        return [
                            'success' => true,
                        ];
                    }
                    else {
                        $this->Model->addError('error', 'Ошибка. Не удалось добавить объекты в группу');
                        return $this->Model;
                    }
                } else {
                    return [
                        'success' => true,
                    ];
                }

            } else {
                $this->Model->addError('error', 'Ошибка. Не удалось изменить группу объектов');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }


    }

    public function actionDelete(){
        if (Yii::$app->user->can('objectsdelite')OR Yii::$app->user->can('superadmin')){
            $postData = file_get_contents("php://input");
            $dataDriver = json_decode($postData, true);
            $id= (int) $dataDriver['id'];
            if( Objectsgroup::deleteAll(['id' => (int) $id])){
                return [
                    'success' => true,
                ];
            }
            else {
                $this->Model->addError('error', 'Ошибка. Не удалось удалить группу объектов');
                return $this->Model;
            }
        } else {
            throw new ForbiddenHttpException('Access denied');
        }

    }


}
///     file_put_contents('/var/www/over.loc/test123.txt', print_r($postData));